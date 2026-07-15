<?php

namespace App\Services\Agents;

use App\Models\Agent;
use App\Models\AgentAction;
use App\Models\AgentRun;
use App\Models\Website;
use App\Services\ConversionGoalProfileService;
use Illuminate\Support\Str;
use Throwable;

abstract class AgentService
{
    public function __construct(protected readonly ConversionGoalProfileService $goalProfiles)
    {
    }

    public function run(Agent $agent, Website $website, string $runType = 'manual', array $runMetadata = []): AgentRun
    {
        $goal = $this->goalProfiles->forWebsite($website);
        $run = AgentRun::create([
            'agent_id' => $agent->id,
            'website_id' => $website->id,
            'run_type' => $runType,
            'status' => 'pending',
            'input_summary' => 'Analyze '.$website->name.' for '.$goal['label'].' using connected workspace evidence.',
            'metadata' => [...$runMetadata, 'conversion_goal' => $goal['key'], 'approval_required' => $goal['approval_required']],
        ]);

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

            $run->update([
                'status' => 'completed',
                'output_summary' => $action['found'].' Recommended: '.$action['recommended'],
                'completed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $run->update(['status' => 'failed', 'error_message' => Str::limit($exception->getMessage(), 2000), 'completed_at' => now()]);
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
            ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
            ->orderByDesc('score')
            ->first();
    }
}
