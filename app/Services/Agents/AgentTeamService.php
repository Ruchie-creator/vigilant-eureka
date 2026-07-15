<?php

namespace App\Services\Agents;

use App\Models\Agent;
use App\Models\AgentRun;
use App\Models\Website;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

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

    public function runAgent(Agent $agent, Website $website, string $runType = 'manual', array $metadata = []): AgentRun
    {
        if ($agent->status !== 'active' || ! isset(self::SERVICES[$agent->slug])) {
            throw new InvalidArgumentException('This agent is inactive or has no configured service.');
        }

        return app(self::SERVICES[$agent->slug])->run($agent, $website, $runType, $metadata);
    }

    public function runFullTeam(Website $website): Collection
    {
        $batch = (string) Str::uuid();
        $slugs = ['analytics-reporting', 'acquisition-growth', 'content-strategy', 'conversion'];

        if (in_array($website->primary_conversion_goal, ['saas_signup', 'saas_signup_and_subscription', 'trial_activation', 'paid_subscription', 'loyalty_retention'], true)) {
            $slugs[] = 'retention-lifecycle';
        }

        $slugs[] = 'marketing-director';
        $agents = Agent::whereIn('slug', $slugs)->get()->keyBy('slug');

        return collect($slugs)->map(function (string $slug) use ($agents, $website, $batch) {
            $agent = $agents->get($slug);

            return $agent ? $this->runAgent($agent, $website, 'full_team', ['team_batch' => $batch]) : null;
        })->filter()->values();
    }
}
