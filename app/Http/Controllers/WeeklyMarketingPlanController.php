<?php

namespace App\Http\Controllers;

use App\Models\AgentAction;
use App\Models\MarketingTask;
use App\Models\Website;
use App\Models\WeeklyMarketingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WeeklyMarketingPlanController extends Controller
{
    public function index(Website $website): View
    {
        return view('weekly-marketing-plans.index', ['website' => $website, 'plans' => $website->weeklyMarketingPlans()->latest('period_start')->paginate(12)]);
    }

    public function update(Request $request, WeeklyMarketingPlan $weeklyMarketingPlan): RedirectResponse
    {
        $status = $request->validate(['status' => ['required', Rule::in(['approved', 'in_progress', 'completed'])]])['status'];
        $weeklyMarketingPlan->update(['status' => $status, 'approved_at' => $status === 'approved' ? now() : $weeklyMarketingPlan->approved_at, 'completed_at' => $status === 'completed' ? now() : null]);
        return back()->with('success', 'Weekly marketing plan marked '.str_replace('_', ' ', $status).'.');
    }

    public function task(WeeklyMarketingPlan $weeklyMarketingPlan, int $priority): RedirectResponse
    {
        $item = data_get($weeklyMarketingPlan->top_priorities, $priority);
        abort_unless(is_array($item), 404);
        $action = isset($item['action_id']) ? AgentAction::find($item['action_id']) : null;
        $title = $item['recommended_task'] ?? $item['title'];
        $task = MarketingTask::firstOrCreate(['website_id' => $weeklyMarketingPlan->website_id, 'title' => $title, 'related_page_url' => $item['related_page_url'] ?? null], ['description' => $item['supporting_data'] ?? null, 'expected_result' => $item['expected_result'] ?? null, 'priority' => in_array($action?->priority, ['high', 'medium', 'low'], true) ? $action->priority : 'medium', 'source_type' => 'weekly_marketing_plan', 'source_value' => 'plan:'.$weeklyMarketingPlan->id, 'status' => 'pending']);
        if ($action) $action->update(['created_task_id' => $task->id, 'status' => $action->status === 'pending' ? 'reviewed' : $action->status]);
        return back()->with('success', $task->wasRecentlyCreated ? 'Task created from weekly priority.' : 'Existing matching task linked.');
    }
}
