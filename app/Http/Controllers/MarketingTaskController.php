<?php

namespace App\Http\Controllers;

use App\Models\AiInsight;
use App\Models\MarketingTask;
use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MarketingTaskController extends Controller
{
    public function index(): View
    {
        return view('marketing-tasks.index', [
            'tasks' => MarketingTask::with(['website', 'aiInsight'])->latest()->paginate(15),
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

    public function update(Request $request, MarketingTask $marketingTask): RedirectResponse
    {
        $marketingTask->update($this->validated($request));

        return redirect()->route('marketing-tasks.index')->with('success', 'Task updated.');
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
            'priority' => $aiInsight->priority,
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
}
