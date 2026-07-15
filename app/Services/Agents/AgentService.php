<?php

namespace App\Services\Agents;

use App\Models\Agent;
use App\Models\AgentAction;
use App\Models\AgentRun;
use App\Models\Website;
use App\Services\ConversionGoalProfileService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

abstract class AgentService
{
    public function __construct(protected readonly ConversionGoalProfileService $goalProfiles)
    {
    }

    public function run(Agent $agent, Website $website, string $runType = 'manual', array $runMetadata = [], ?AgentRun $existingRun = null): AgentRun
    {
        $goal = $this->goalProfiles->forWebsite($website);
        $inputSummary = 'Analyze '.$website->name.' for '.$goal['label'].' using connected workspace evidence.';
        $attributes = [
            'agent_id' => $agent->id,
            'website_id' => $website->id,
            'run_type' => $runType,
            'trigger_type' => $runMetadata['trigger_type'] ?? 'manual',
            'correlation_id' => $runMetadata['correlation_id'] ?? (string) Str::uuid(),
            'parent_run_id' => $runMetadata['parent_run_id'] ?? null,
            'status' => 'pending',
            'input_summary' => $inputSummary,
            'input_hash' => hash('sha256', $inputSummary),
            'metadata' => [...$runMetadata, 'conversion_goal' => $goal['key'], 'approval_required' => $goal['approval_required']],
        ];
        $run = $existingRun ?: AgentRun::create($attributes);

        if ($existingRun) {
            $run->update($attributes);
        }

        $timer = hrtime(true);

        try {
            $run->update(['status' => 'running', 'started_at' => now()]);
            $action = $this->action($website, $goal);
            $metadata = [
                'what_i_found' => $action['found'],
                'why_it_matters' => $action['why'],
                'recommended_action' => $action['recommended'],
                'affected_audience' => $action['audience'] ?? $goal['target_audience'],
                'suggested_task' => $action['task'],
                'approval_required' => (bool) ($action['approval_required'] ?? true),
                'data_sources' => $action['data_sources'] ?? $goal['data_sources'],
                'conversion_goal' => $goal['key'],
                ...($action['metadata'] ?? []),
            ];

            AgentAction::create([
                'agent_run_id' => $run->id,
                'website_id' => $website->id,
                'action_type' => $action['type'],
                'title' => Str::limit($action['title'], 255, ''),
                'description' => $action['recommended'],
                'priority' => $action['priority'],
                'status' => 'pending',
                'related_page_url' => $action['page'] ?? null,
                'related_query' => $action['query'] ?? null,
                'expected_result' => $action['expected'],
                'metadata' => $metadata,
            ]);

            $outputSummary = $action['found'].' Recommended: '.$action['recommended'];
            $duration = $this->duration($timer);
            $run->update([
                'status' => 'completed',
                'output_summary' => $outputSummary,
                'output_hash' => hash('sha256', $outputSummary),
                'duration_ms' => $duration,
                'completed_at' => now(),
            ]);
            if (! ($runMetadata['orchestration_parent'] ?? false)) {
                $this->logRun($agent, $website, $runType, 'completed', $duration);
            }
        } catch (Throwable $exception) {
            $duration = $this->duration($timer);
            $error = $this->safeError($exception->getMessage());
            $run->update(['status' => 'failed', 'error_message' => Str::limit($error, 2000), 'duration_ms' => $duration, 'completed_at' => now()]);
            if (! ($runMetadata['orchestration_parent'] ?? false)) {
                $this->logRun($agent, $website, $runType, 'failed', $duration, $error);
            }
        }

        return $run->fresh(['agent', 'website', 'actions']);
    }

    abstract protected function action(Website $website, array $goal): array;

    protected function baseAction(array $goal, string $title, string $found, string $why, string $recommended, string $expected, string $task, string $priority = 'medium'): array
    {
        return compact('title', 'found', 'why', 'recommended', 'expected', 'task', 'priority') + [
            'type' => 'recommendation',
            'audience' => $goal['target_audience'] ?: $goal['audience_label'],
            'approval_required' => true,
        ];
    }

    protected function topOpportunity(Website $website, array $categories = []): ?object
    {
        return $website->growthOpportunities()
            ->whereIn('status', ['open', 'reviewed', 'in_progress'])
            ->when($categories !== [], fn ($query) => $query->whereIn('opportunity_category', $categories))
            ->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")
            ->orderByDesc('score')
            ->first();
    }

    private function duration(int $startedAt): int
    {
        return max(0, (int) round((hrtime(true) - $startedAt) / 1_000_000));
    }

    private function safeError(string $message): string
    {
        $message = preg_replace('/(Bearer\s+)[^\s]+/i', '$1[redacted]', $message) ?? 'Agent run failed.';

        return preg_replace('/(api[_-]?key|access[_-]?token|refresh[_-]?token|authorization|password|secret)\s*[:=]\s*[^\s,;]+/i', '$1=[redacted]', $message) ?? 'Agent run failed.';
    }

    private function logRun(Agent $agent, Website $website, string $runType, string $status, int $duration, ?string $error = null): void
    {
        Log::log($status === 'failed' ? 'error' : 'info', 'Agent run finished.', [
            'agent' => $agent->slug,
            'workspace' => $website->id,
            'run_type' => $runType,
            'status' => $status,
            'duration_ms' => $duration,
            'error_summary' => $error ? Str::limit($error, 500) : null,
        ]);
    }
}
