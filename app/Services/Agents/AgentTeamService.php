<?php

namespace App\Services\Agents;

use App\Models\Agent;
use App\Models\AgentRun;
use App\Models\Website;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class AgentTeamService
{
    private const SERVICES = [
        'marketing-director' => MarketingDirectorAgentService::class,
        'acquisition-growth' => AcquisitionGrowthAgentService::class,
        'content-strategy' => ContentStrategyAgentService::class,
        'conversion' => ConversionAgentService::class,
        'retention-lifecycle' => RetentionLifecycleAgentService::class,
        'analytics-reporting' => AnalyticsReportingAgentService::class,
        'task-manager' => TaskManagerAgentService::class,
    ];

    public function runAgent(Agent $agent, Website $website, string $runType = 'manual', array $metadata = [], ?AgentRun $existingRun = null): AgentRun
    {
        if ($agent->status !== 'active' || ! isset(self::SERVICES[$agent->slug])) {
            throw new InvalidArgumentException('This agent is inactive or has no configured service.');
        }

        return app(self::SERVICES[$agent->slug])->run($agent, $website, $runType, $metadata, $existingRun);
    }

    public function runFullTeam(Website $website, string $triggerType = 'manual', array $metadata = []): Collection
    {
        $lock = Cache::lock('agent-team:workspace:'.$website->id, 300);

        if (! $lock->get()) {
            throw new RuntimeException('A full-team run is already active for this workspace.');
        }

        try {
            return $this->runLockedTeam($website, $triggerType, $metadata);
        } finally {
            $lock->release();
        }
    }

    private function runLockedTeam(Website $website, string $triggerType, array $metadata): Collection
    {
        $timer = hrtime(true);
        $correlationId = (string) Str::uuid();
        $director = Agent::where('slug', 'marketing-director')->first();

        if (! $director || $director->status !== 'active') {
            throw new RuntimeException('The Marketing Director Agent must be active to coordinate a full-team run.');
        }

        $input = 'Coordinate required specialist analysis for workspace '.$website->id.'.';
        $parent = AgentRun::create([
            'agent_id' => $director->id,
            'website_id' => $website->id,
            'run_type' => 'full_team',
            'trigger_type' => $triggerType,
            'correlation_id' => $correlationId,
            'status' => 'pending',
            'input_summary' => $input,
            'input_hash' => hash('sha256', $input),
            'metadata' => [...$metadata, 'team_batch' => $correlationId, 'orchestration_parent' => true],
        ]);

        $slugs = ['analytics-reporting', 'acquisition-growth', 'content-strategy', 'conversion'];
        if ($this->supportsLifecycle($website)) {
            $slugs[] = 'retention-lifecycle';
        }

        $agents = Agent::whereIn('slug', $slugs)->get()->keyBy('slug');
        $runs = collect();
        $failed = false;

        foreach ($slugs as $slug) {
            $agent = $agents->get($slug);

            if (! $agent || $agent->status !== 'active') {
                if ($agent) {
                    $runs->push($this->recordUnavailableAgent($agent, $website, $parent, $correlationId, $triggerType));
                }
                $failed = true;
                continue;
            }

            $run = $this->runAgent($agent, $website, 'full_team', [
                'trigger_type' => $triggerType,
                'correlation_id' => $correlationId,
                'parent_run_id' => $parent->id,
                'team_batch' => $correlationId,
                ...$metadata,
            ]);
            $runs->push($run);
            $failed = $failed || $run->status === 'failed';
        }

        if ($failed || $runs->count() !== count($slugs)) {
            $duration = $this->duration($timer);
            $error = 'One or more required specialist agent runs failed.';
            $parent->update(['status' => 'failed', 'error_message' => $error, 'duration_ms' => $duration, 'completed_at' => now()]);
            $this->logCoordinator($director, $website, 'failed', $duration, $error);
        } else {
            $parent = $this->runAgent($director, $website, 'full_team', [
                'trigger_type' => $triggerType,
                'correlation_id' => $correlationId,
                'team_batch' => $correlationId,
                'orchestration_parent' => true,
                ...$metadata,
            ], $parent);
            $duration = $this->duration($timer);
            $parent->update(['duration_ms' => $duration]);
            $this->logCoordinator($director, $website, $parent->status, $duration, $parent->error_message);
        }

        return $runs->push($parent->fresh(['agent', 'website', 'actions']))->values();
    }

    private function supportsLifecycle(Website $website): bool
    {
        return in_array($website->primary_conversion_goal, ['saas_signup', 'saas_signup_and_subscription', 'trial_activation', 'paid_subscription', 'loyalty_retention'], true);
    }

    private function recordUnavailableAgent(Agent $agent, Website $website, AgentRun $parent, string $correlationId, string $triggerType): AgentRun
    {
        $error = 'Required specialist agent is inactive.';
        $run = AgentRun::create([
            'agent_id' => $agent->id,
            'website_id' => $website->id,
            'run_type' => 'full_team',
            'trigger_type' => $triggerType,
            'correlation_id' => $correlationId,
            'parent_run_id' => $parent->id,
            'status' => 'failed',
            'error_message' => $error,
            'duration_ms' => 0,
            'started_at' => now(),
            'completed_at' => now(),
        ]);
        $this->logCoordinator($agent, $website, 'failed', 0, $error);

        return $run;
    }

    private function duration(int $startedAt): int
    {
        return max(0, (int) round((hrtime(true) - $startedAt) / 1_000_000));
    }

    private function logCoordinator(Agent $agent, Website $website, string $status, int $duration, ?string $error = null): void
    {
        Log::log($status === 'failed' ? 'error' : 'info', 'Agent run finished.', [
            'agent' => $agent->slug,
            'workspace' => $website->id,
            'run_type' => 'full_team',
            'status' => $status,
            'duration_ms' => $duration,
            'error_summary' => $error,
        ]);
    }
}
