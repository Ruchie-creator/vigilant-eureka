<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentAction;
use App\Models\AgentHandoff;
use App\Models\MarketingTask;
use App\Models\Website;
use App\Models\WeeklyMarketingPlan;
use App\Services\Agents\ApprovalWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use Illuminate\Support\Carbon;

class ApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $items = collect()
            ->concat(AgentAction::with(['website', 'run.agent'])->whereIn('status', ['pending', 'reviewed'])->latest()->get()->map(fn ($item) => $this->actionItem($item)))
            ->concat(MarketingTask::with(['website', 'agentAction.run.agent'])->where('approval_status', 'pending')->latest()->get()->map(fn ($item) => $this->taskItem($item)))
            ->concat(WeeklyMarketingPlan::with(['website', 'agentRun.agent'])->where('status', 'draft')->latest()->get()->map(fn ($item) => $this->planItem($item)))
            ->concat($this->reviewableHandoffs());
        $items = $this->filter($items, $request)->sortByDesc('created_at')->values();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginated = new LengthAwarePaginator($items->forPage($page, 20), $items->count(), 20, $page, ['path' => $request->url(), 'query' => $request->query()]);

        return view('approvals.index', ['items' => $paginated, 'websites' => Website::orderBy('name')->get(), 'agents' => Agent::where('status', 'active')->orderBy('name')->get(), 'conversionGoals' => Website::whereNotNull('primary_conversion_goal')->distinct()->orderBy('primary_conversion_goal')->pluck('primary_conversion_goal')]);
    }

    public function update(Request $request, string $type, int $id, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $request->validate(['operation' => ['required', Rule::in(['approve', 'approve_task', 'request_revision', 'ignore'])], 'review_notes' => ['nullable', 'string', 'max:5000'], 'revision_reason' => ['nullable', 'required_if:operation,request_revision', 'string', 'max:5000']]);
        try { $workflow->review($this->resolve($type, $id), $data['operation'], $request->user(), $data['review_notes'] ?? null, $data['revision_reason'] ?? null); }
        catch (InvalidArgumentException $exception) { return back()->with('error', $exception->getMessage()); }
        return back()->with('success', match ($data['operation']) { 'approve' => 'Item approved. No external action was executed.', 'approve_task' => 'Item approved and a duplicate-safe task was linked.', 'request_revision' => 'Revision requested. The revised action remains pending approval.', default => 'Item ignored and retained in the audit history.' });
    }

    private function resolve(string $type, int $id)
    {
        return match ($type) { 'action' => AgentAction::findOrFail($id), 'task' => MarketingTask::findOrFail($id), 'plan' => WeeklyMarketingPlan::findOrFail($id), 'handoff' => AgentHandoff::findOrFail($id), default => abort(404) };
    }

    private function actionItem(AgentAction $item): array
    {
        return $this->base('action', $item, $item->website, $item->run->agent, $item->action_type, $item->priority, $item->related_page_url ?: ($item->related_query ?: data_get($item->metadata, 'affected_audience')), data_get($item->metadata, 'what_i_found', data_get($item->metadata, 'data_sources')), $item->description, $item->expected_result, $item->status);
    }

    private function taskItem(MarketingTask $item): array
    {
        return $this->base('task', $item, $item->website, $item->agentAction?->run?->agent, 'suggested_task', $item->priority, $item->related_page_url ?: $item->source_value, $item->description, $item->title, $item->expected_result, $item->approval_status);
    }

    private function planItem(WeeklyMarketingPlan $item): array
    {
        return $this->base('plan', $item, $item->website, $item->agentRun?->agent, 'weekly_marketing_plan', 'high', 'Workspace plan for '.$item->period_start->format('M j').' - '.$item->period_end->format('M j'), $item->performance_summary, $item->executive_summary, collect($item->expected_results)->filter()->implode(' '), $item->status);
    }

    private function reviewableHandoffs(): Collection
    {
        return AgentHandoff::with(['website', 'fromAgent'])->whereIn('status', ['pending', 'accepted', 'failed'])->latest()->get()->map(function ($item) {
            $action = AgentAction::find(data_get($item->context, 'originating_action_id'));
            if (! $action || ! in_array($action->priority, ['high', 'critical'], true)) return null;
            return $this->base('handoff', $item, $item->website, $item->fromAgent, 'high_priority_handoff', $action->priority, data_get($item->context, 'related_page_url') ?: data_get($item->context, 'related_query'), data_get($item->context, 'source_data'), $item->reason, $item->expected_output, $item->status);
        })->filter()->values();
    }

    private function base(string $type, $model, Website $website, ?Agent $agent, string $actionType, string $priority, ?string $affected, mixed $evidence, ?string $recommendation, ?string $expected, string $status): array
    {
        return ['type' => $type, 'model' => $model, 'id' => $model->id, 'title' => $model->title ?? $model->executive_summary ?? $recommendation, 'website' => $website, 'agent' => $agent, 'action_type' => $actionType, 'priority' => $priority, 'affected' => $affected, 'evidence' => $evidence, 'recommendation' => $recommendation, 'expected_result' => $expected, 'status' => $status, 'created_at' => $model->created_at, 'conversion_goal' => $website->primary_conversion_goal];
    }

    private function filter(Collection $items, Request $request): Collection
    {
        return $items
            ->when($request->website_id, fn ($items) => $items->where('website.id', (int) $request->website_id))
            ->when($request->agent_id, fn ($items) => $items->where('agent.id', (int) $request->agent_id))
            ->when($request->item_type, fn ($items) => $items->where('type', $request->item_type))
            ->when($request->priority, fn ($items) => $items->where('priority', $request->priority))
            ->when($request->status, fn ($items) => $items->where('status', $request->status))
            ->when($request->conversion_goal, fn ($items) => $items->where('conversion_goal', $request->conversion_goal))
            ->when($request->date_start, fn ($items) => $items->filter(fn ($item) => $item['created_at']->gte($request->date_start)))
            ->when($request->date_end, fn ($items) => $items->filter(fn ($item) => $item['created_at']->lte(Carbon::parse($request->date_end)->endOfDay())));
    }
}
