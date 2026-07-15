<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\Website;
use App\Services\Agents\AgentTeamService;
use App\Services\Agents\WeeklyMarketingPlanService;
use Illuminate\Console\Command;

class GenerateWeeklyMarketingPlan extends Command
{
    protected $signature = 'agents:generate-weekly-plan {website}';
    protected $description = 'Run the Marketing Director and generate a draft weekly marketing plan.';
    public function handle(AgentTeamService $team, WeeklyMarketingPlanService $plans): int
    {
        $website = Website::findOrFail($this->argument('website'));
        $director = Agent::where('slug', 'marketing-director')->where('status', 'active')->firstOrFail();
        $run = $team->runAgent($director, $website, 'command', ['trigger_type' => 'command', 'trigger_reason' => 'weekly_marketing_plan']);
        if ($run->status !== 'completed') return self::FAILURE;
        $plan = $plans->generate($website, $run);
        $this->info('Generated plan '.$plan->id.'.');
        return self::SUCCESS;
    }
}
