<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Website;
use App\Services\Agents\AgentTeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AgentRunController extends Controller
{
    public function store(Request $request, Agent $agent, AgentTeamService $team): RedirectResponse
    {
        $data = $request->validate(['website_id' => ['required', 'exists:websites,id']]);
        $run = $team->runAgent($agent, Website::findOrFail($data['website_id']));

        return back()->with($run->status === 'completed' ? 'success' : 'error', $run->status === 'completed' ? $agent->name.' completed its analysis.' : $agent->name.' could not complete: '.$run->error_message);
    }

    public function fullTeam(Website $website, AgentTeamService $team): RedirectResponse
    {
        try {
            $runs = $team->runFullTeam($website);
        } catch (RuntimeException $exception) {
            return redirect()->route('websites.agents.index', $website)->with('error', $exception->getMessage());
        }
        $completed = $runs->where('status', 'completed')->count();

        return redirect()->route('websites.agents.index', $website)->with($completed === $runs->count() ? 'success' : 'error', $completed.' of '.$runs->count().' agent runs completed. All actions are pending review.');
    }
}
