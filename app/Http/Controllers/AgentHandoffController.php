<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentHandoff;
use App\Models\Website;
use App\Services\Agents\AgentHandoffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AgentHandoffController extends Controller
{
    public function index(Request $request): View
    {
        $handoffs = AgentHandoff::with(['website', 'fromAgent', 'toAgent'])->when($request->agent_id, fn ($q) => $q->where(fn ($q) => $q->where('from_agent_id', $request->agent_id)->orWhere('to_agent_id', $request->agent_id)))->when($request->website_id, fn ($q) => $q->where('website_id', $request->website_id))->when($request->status, fn ($q) => $q->where('status', $request->status))->latest()->paginate(30)->withQueryString();

        return view('agents.handoffs', [
            'handoffs' => $handoffs,
            'agents' => Agent::where('status', 'active')->orderBy('name')->get(),
            'websites' => Website::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, AgentHandoff $agentHandoff, AgentHandoffService $service): RedirectResponse
    {
        $status = $request->validate(['status' => ['required', Rule::in(['accepted', 'completed', 'ignored'])]])['status'];
        match ($status) { 'accepted' => $service->acceptHandoff($agentHandoff), 'completed' => $service->completeHandoff($agentHandoff), 'ignored' => $service->ignoreHandoff($agentHandoff) };
        return back()->with('success', 'Agent handoff marked '.$status.'.');
    }
}
