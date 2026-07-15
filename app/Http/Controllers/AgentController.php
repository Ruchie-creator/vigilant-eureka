<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentAction;
use App\Models\AgentHandoff;
use App\Models\AgentMemory;
use App\Models\Website;
use App\Models\MarketingTask;
use App\Models\WeeklyMarketingPlan;
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

        return view('agents.show', [
            'agent' => $agent, 'runs' => $runs, 'websites' => Website::where('status', 'active')->orderBy('name')->get(),
            'memories' => $agent->memories()->with('website')->latest()->limit(8)->get(),
            'handoffs' => AgentHandoff::with(['website', 'fromAgent', 'toAgent'])->where(fn ($query) => $query->where('from_agent_id', $agent->id)->orWhere('to_agent_id', $agent->id))->latest()->limit(8)->get(),
        ]);
    }

    public function website(Website $website, ConversionGoalProfileService $goalProfiles): View
    {
        $agents = Agent::where('status', 'active')->orderBy('id')->get();
        $actions = AgentAction::with(['run.agent', 'createdTask'])->where('website_id', $website->id)->latest()->limit(30)->get();
        $memories = AgentMemory::with('agent')->where('website_id', $website->id)->latest()->limit(8)->get();
        $handoffs = AgentHandoff::with(['fromAgent', 'toAgent'])->where('website_id', $website->id)->latest()->limit(20)->get();
        $schedules = $website->agentSchedules()->with('agent')->get();
        $latestPlan = $website->weeklyMarketingPlans()->latest('period_start')->first();

        return view('agents.website', [
            'website' => $website,
            'goalProfile' => $goalProfiles->forWebsite($website),
            'agents' => $agents,
            'latestActions' => $actions->unique(fn ($action) => $action->run->agent->slug)->keyBy(fn ($action) => $action->run->agent->slug),
            'actions' => $actions,
            'memories' => $memories,
            'pendingHandoffs' => $handoffs->where('status', 'pending')->take(5),
            'completedHandoffs' => $handoffs->where('status', 'completed')->take(5),
            'unresolvedHandoffs' => $handoffs->whereIn('status', ['accepted', 'failed', 'ignored'])->take(5),
            'scheduleSummary' => [
                'full_team' => $schedules->firstWhere('schedule_type', 'weekly_full_team'),
                'analytics' => $schedules->firstWhere('schedule_type', 'daily_analytics'),
                'weekly_plan' => $schedules->firstWhere('schedule_type', 'weekly_marketing_plan'),
                'last' => $schedules->sortByDesc('last_run_at')->first(),
            ],
            'latestTriggerReason' => $website->agentRuns()->whereNotNull('metadata')->latest()->get()->first(fn ($run) => filled(data_get($run->metadata, 'trigger_reason')))?->metadata['trigger_reason'] ?? null,
            'operationsSummary' => [
                'pending_approvals' => AgentAction::where('website_id', $website->id)->whereIn('status', ['pending', 'reviewed'])->count() + MarketingTask::where('website_id', $website->id)->where('approval_status', 'pending')->count() + WeeklyMarketingPlan::where('website_id', $website->id)->where('status', 'draft')->count(),
                'active_runs' => $website->agentRuns()->whereIn('status', ['pending', 'running'])->count(),
                'next_schedule' => $schedules->where('enabled', true)->whereNotNull('next_run_at')->sortBy('next_run_at')->first()?->next_run_at,
                'latest_plan' => $latestPlan,
                'unresolved_handoffs' => $handoffs->whereIn('status', ['pending', 'accepted', 'failed'])->count(),
                'recent_failure' => $website->agentRuns()->with('agent')->where('status', 'failed')->latest()->first(),
            ],
        ]);
    }
}
