<?php

namespace App\Http\Controllers;

use App\Models\ActionOutcome;
use App\Models\Agent;
use App\Models\Website;
use App\Services\Agents\ActionOutcomeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ActionOutcomeController extends Controller
{
    public function index(Request $request): View
    {
        $query = ActionOutcome::with(['website', 'agentAction.run.agent', 'marketingTask'])->latest();
        $query->when($request->filled('website_id'), fn ($q) => $q->where('website_id', $request->integer('website_id')))
            ->when($request->filled('agent_id'), fn ($q) => $q->whereHas('agentAction.run', fn ($run) => $run->where('agent_id', $request->integer('agent_id'))))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('conversion_goal'), fn ($q) => $q->whereHas('website', fn ($website) => $website->where('primary_conversion_goal', $request->string('conversion_goal'))))
            ->when($request->filled('evaluation_type'), fn ($q) => $q->where('evaluation_type', $request->string('evaluation_type')))
            ->when($request->filled('date_start'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('date_start')))
            ->when($request->filled('date_end'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('date_end')));

        return view('action-outcomes.index', ['outcomes' => $query->paginate(20)->withQueryString(), 'websites' => Website::orderBy('name')->get(), 'agents' => Agent::orderBy('name')->get(), 'conversionGoals' => Website::whereNotNull('primary_conversion_goal')->distinct()->orderBy('primary_conversion_goal')->pluck('primary_conversion_goal')]);
    }

    public function show(ActionOutcome $actionOutcome): View
    {
        return view('action-outcomes.show', ['outcome' => $actionOutcome->load(['website', 'agentAction.run.agent', 'marketingTask'])]);
    }

    public function capture(ActionOutcome $actionOutcome, ActionOutcomeService $service): RedirectResponse
    {
        $service->captureBaseline($actionOutcome);
        return back()->with('success', 'Baseline captured and the equivalent evaluation window scheduled.');
    }

    public function evaluate(ActionOutcome $actionOutcome, ActionOutcomeService $service): RedirectResponse
    {
        $service->evaluate($actionOutcome);
        return back()->with('success', $actionOutcome->fresh()->status === 'waiting' ? 'The post-action period is incomplete, so evaluation remains waiting.' : 'Outcome evaluated against its equivalent baseline.');
    }

    public function update(Request $request, ActionOutcome $actionOutcome, ActionOutcomeService $service): RedirectResponse
    {
        $data = $request->validate(['operation' => ['required', Rule::in(['window', 'inconclusive', 'notes'])], 'window_days' => ['nullable', 'integer', 'min:7', 'max:90'], 'review_notes' => ['nullable', 'string', 'max:5000']]);
        if ($data['operation'] === 'window') $service->captureBaseline($actionOutcome, (int) ($data['window_days'] ?? 14));
        elseif ($data['operation'] === 'inconclusive') $actionOutcome->update(['status' => 'inconclusive', 'confidence' => 'low', 'review_notes' => $data['review_notes'] ?? $actionOutcome->review_notes, 'outcome_summary' => 'Marked inconclusive by a human reviewer; no causal claim is made.', 'evaluated_at' => now()]);
        else $actionOutcome->update(['review_notes' => $data['review_notes'] ?? null]);
        return back()->with('success', 'Outcome review updated.');
    }
}
