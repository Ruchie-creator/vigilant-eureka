<?php

namespace App\Console\Commands;

use App\Models\Website;
use App\Services\Agents\AgentTeamService;
use Illuminate\Console\Command;

class RunAgentTeam extends Command
{
    protected $signature = 'agents:run-team {website}';
    protected $description = 'Run the full agent team for a workspace.';
    public function handle(AgentTeamService $team): int
    {
        $runs = $team->runFullTeam(Website::findOrFail($this->argument('website')), 'command');
        $this->info($runs->where('status', 'completed')->count().' of '.$runs->count().' runs completed.');
        return $runs->contains(fn ($run) => $run->status === 'failed') ? self::FAILURE : self::SUCCESS;
    }
}
