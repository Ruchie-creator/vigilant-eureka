<?php

namespace App\Http\Controllers;

use App\Models\AiInsight;
use App\Models\MarketingTask;
use App\Models\Website;
use App\Models\AgentAction;
use App\Services\Agents\AgentMemoryService;
use App\Services\Agents\ActionOutcomeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MarketingTaskController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'website_id' => $request->query('website_id'),
            'priority' => $request->query('priority'),
            'category' => $request->query('category'),
            'status' => $request->query('status'),
            'source' => $request->query('source'),
            'origin' => $request->query('origin'),
        ];

        $query = MarketingTask::with(['website', 'aiInsight', 'growthOpportunity', 'outcome'])
            ->when(filled($filters['website_id']), fn ($query) => $query->where('website_id', $filters['website_id']))
            ->when(filled($filters['priority']), fn ($query) => $query->where('priority', $filters['priority']))
            ->when(filled($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(filled($filters['source']), function ($query) use ($filters) {
                $term = '%'.$filters['source'].'%';

                $query->where(function ($query) use ($term) {
                    $query->where('source_value', 'like', $term)
                        ->orWhere('related_page_url', 'like', $term);
                });
            })
            ->when(filled($filters['category']), function ($query) use ($filters) {
                $query->where(function ($query) use ($filters) {
                    $query->whereHas('growthOpportunity', fn ($query) => $query->where('opportunity_category', $filters['category']))
                        ->orWhereHas('aiInsight', fn ($query) => $query->where('category', $filters['category']));
                });
            })
            ->when($filters['origin'] === 'opportunity', fn ($query) => $query->whereNotNull('growth_opportunity_id'))
            ->when($filters['origin'] === 'ai_insight', fn ($query) => $query->whereNotNull('ai_insight_id')->whereNull('growth_opportunity_id'))
            ->when($filters['origin'] === 'agent_action', fn ($query) => $query->where('source_type', 'agent_action'))
            ->when($filters['origin'] === 'manual', fn ($query) => $query->whereNull('growth_opportunity_id')->whereNull('ai_insight_id')->where(fn ($query) => $query->whereNull('source_type')->orWhere('source_type', '!=', 'agent_action')))
            ->latest();

        $tasks = $query->get();

        return view('marketing-tasks.index', [
            'tasks' => $tasks,
            'tasksByStatus' => $tasks->groupBy('status'),
            'filters' => $filters,
            'websites' => Website::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('marketing-tasks.form', [
            'task' => new MarketingTask(),
            'websites' => Website::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        MarketingTask::create($this->validated($request));

        return redirect()->route('marketing-tasks.index')->with('success', 'Task created.');
    }

    public function edit(MarketingTask $marketingTask): View
    {
        return view('marketing-tasks.form', [
            'task' => $marketingTask,
            'websites' => Website::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, MarketingTask $marketingTask, AgentMemoryService $memories, ActionOutcomeService $outcomes): RedirectResponse
    {
        $marketingTask->update($this->validated($request));
        $this->rememberCompletedAgentTask($marketingTask, $memories);
        $outcomes->createForCompletedTask($marketingTask);

        return redirect()->route('marketing-tasks.index')->with('success', 'Task updated.');
    }

    public function updateStatus(Request $request, MarketingTask $marketingTask, AgentMemoryService $memories, ActionOutcomeService $outcomes): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'in_progress', 'completed', 'ignored'])],
        ]);

        $marketingTask->update($data);
        $this->rememberCompletedAgentTask($marketingTask, $memories);
        $outcomes->createForCompletedTask($marketingTask);

        return back()->with('success', 'Task status updated.');
    }

    public function destroy(MarketingTask $marketingTask): RedirectResponse
    {
        $marketingTask->delete();

        return back()->with('success', 'Task deleted.');
    }

    public function storeFromInsight(AiInsight $aiInsight): RedirectResponse
    {
        MarketingTask::create([
            'website_id' => $aiInsight->website_id,
            'ai_insight_id' => $aiInsight->id,
            'title' => $aiInsight->title,
            'description' => $aiInsight->recommendation,
            'expected_result' => $aiInsight->expected_result,
            'priority' => $aiInsight->priority,
            'source_type' => 'ai_insight',
            'source_value' => $aiInsight->affected_source_value ?: $aiInsight->title,
            'related_page_url' => ($aiInsight->data_used['related_page'] ?? null),
            'status' => 'pending',
        ]);

        $aiInsight->update(['status' => 'reviewed']);

        return back()->with('success', 'Insight converted into a task.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'website_id' => ['required', 'exists:websites,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])],
            'expected_result' => ['nullable', 'string', 'max:5000'],
            'source_type' => ['nullable', 'string', 'max:100'],
            'source_value' => ['nullable', 'string', 'max:512'],
            'related_page_url' => ['nullable', 'string', 'max:512'],
            'status' => ['required', Rule::in(['pending', 'in_progress', 'completed', 'ignored'])],
            'due_date' => ['nullable', 'date'],
        ]);
    }

    private function rememberCompletedAgentTask(MarketingTask $task, AgentMemoryService $memories): void
    {
        if ($task->status !== 'completed' || $task->source_type !== 'agent_action') return;
        $action = AgentAction::with(['run.agent', 'website'])->where('created_task_id', $task->id)->first();
        if ($action) $memories->updateOrRemember($action->run->agent, $action->website, 'completed_task', 'marketing-task:'.$task->id, 'Completed task: '.$task->title, ['confidence' => 1, 'source_type' => 'marketing_task', 'source_id' => $task->id]);
    }
}
