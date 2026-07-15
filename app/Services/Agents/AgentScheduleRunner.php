<?php

namespace App\Services\Agents;

use App\Models\AgentSchedule;
use App\Models\AgentRun;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;

class AgentScheduleRunner
{
    public function __construct(private readonly AgentTeamService $team, private readonly WeeklyMarketingPlanService $plans) {}

    public function run(AgentSchedule $schedule): AgentRun|array
    {
        $lock = Cache::lock('agent-schedule:workspace:'.$schedule->website_id.':'.$schedule->schedule_type, 600);
        if (! $lock->get()) throw new RuntimeException('This workspace schedule is already running.');

        try {
            $website = $schedule->website;
            $completedAttempt = AgentRun::where('website_id', $website->id)->where('status', 'completed')->where('metadata->schedule_id', $schedule->id)->when($schedule->last_run_at, fn ($query) => $query->where('created_at', '>=', $schedule->last_run_at))->latest()->first();
            if ($schedule->last_status === 'running' && $completedAttempt) return $completedAttempt;
            $correlationId = (string) Str::uuid();
            return match ($schedule->schedule_type) {
                'weekly_full_team' => $this->runTeamAndPlan($schedule),
                'weekly_marketing_plan' => $this->runDirectorPlan($schedule, $correlationId),
                'daily_analytics', 'custom_agent' => $this->team->runAgent($schedule->agent, $website, 'scheduled', ['trigger_type' => 'scheduled', 'correlation_id' => $correlationId, 'schedule_id' => $schedule->id, 'trigger_reason' => $schedule->schedule_type]),
                default => throw new RuntimeException('This schedule type cannot be run on a timer.'),
            };
        } finally {
            $lock->release();
        }
    }

    private function runTeamAndPlan(AgentSchedule $schedule): array
    {
        $website = $schedule->website;
        $runs = $this->team->runFullTeam($website, 'scheduled', ['schedule_id' => $schedule->id, 'trigger_reason' => 'weekly_full_team']);
        $director = $runs->first(fn (AgentRun $run) => $run->agent->slug === 'marketing-director');
        if ($director?->status === 'completed') $this->plans->generate($website, $director);
        return $runs->all();
    }

    private function runDirectorPlan(AgentSchedule $schedule, string $correlationId): AgentRun
    {
        $run = $this->team->runAgent($schedule->agent, $schedule->website, 'scheduled', ['trigger_type' => 'scheduled', 'correlation_id' => $correlationId, 'schedule_id' => $schedule->id, 'trigger_reason' => 'weekly_marketing_plan']);
        if ($run->status === 'completed') $this->plans->generate($schedule->website, $run);
        return $run;
    }
}
