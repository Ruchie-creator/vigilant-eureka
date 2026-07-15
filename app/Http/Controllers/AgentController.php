<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentAction;
use App\Models\Website;
use App\Services\ConversionGoalProfileService;
use Illuminate\View\View;

class AgentController extends Controller
{
    public function index(): View
    {
        $agents = Agent::with(['latestRun.website', 'latestRun.actions' => fn ($query) => $query->latest()->limit(1)])->orderBy('id')->get();

        return view('agents.index', ['agents' => $agents, 'websites' => Website::where('status', 'active')->orderBy('name')->get()]);
    }

    public function show(Agent $agent): View
    {
        $runs = $agent->runs()->with(['website', 'actions.createdTask'])->latest()->paginate(10);

        return view('agents.show', ['agent' => $agent, 'runs' => $runs, 'websites' => Website::where('status', 'active')->orderBy('name')->get()]);
    }

    public function website(Website $website, ConversionGoalProfileService $goalProfiles): View
    {
        $agents = Agent::where('status', 'active')->orderBy('id')->get();
        $actions = AgentAction::with(['run.agent', 'createdTask'])->where('website_id', $website->id)->latest()->limit(30)->get();

        return view('agents.website', [
            'website' => $website,
            'goalProfile' => $goalProfiles->forWebsite($website),
            'agents' => $agents,
            'latestActions' => $actions->unique(fn ($action) => $action->run->agent->slug)->keyBy(fn ($action) => $action->run->agent->slug),
            'actions' => $actions,
        ]);
    }
}
