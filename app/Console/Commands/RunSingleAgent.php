<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\Website;
use App\Services\Agents\AgentTeamService;
use Illuminate\Console\Command;

class RunSingleAgent extends Command
{
    protected $signature = 'agents:run-agent {agent} {website}';
    protected $description = 'Run one agent for a workspace.';
    public function handle(AgentTeamService $team): int
    {
        $value = $this->argument('agent');
        $agent = Agent::where('slug', $value)->orWhere('id', $value)->firstOrFail();
        $run = $team->runAgent($agent, Website::findOrFail($this->argument('website')), 'command', ['trigger_type' => 'command']);
        $this->info($run->status);
        return $run->status === 'completed' ? self::SUCCESS : self::FAILURE;
    }
}
