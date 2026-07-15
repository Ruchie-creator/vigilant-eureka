<?php

namespace App\Http\Controllers;

use App\Models\AgentAction;
use App\Models\AgentHandoff;
use App\Models\AgentRun;
use App\Models\AgentSchedule;
use App\Models\MarketingTask;
use App\Models\WeeklyMarketingPlan;
use App\Services\Agents\AgentRunSanitizer;
use App\Services\Agents\AgentScheduleService;
use App\Services\Agents\AgentTeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use RuntimeException;

class AgentOperationsController extends Controller
{
    public function index(AgentScheduleService $schedules): View
    {
        $runs = AgentRun::with(['website', 'agent'])->withCount('actions')->latest()->limit(30)->get();
        $pendingApprovals = AgentAction::whereIn('status', ['pending', 'reviewed'])->count() + MarketingTask::where('approval_status', 'pending')->count() + WeeklyMarketingPlan::where('status', 'draft')->count();
        return view('agent-operations.index', [
            'summary' => ['active_runs' => AgentRun::whereIn('status', ['pending', 'running'])->count(), 'completed_today' => AgentRun::where('status', 'completed')->whereDate('completed_at', today())->count(), 'failed_runs' => AgentRun::where('status', 'failed')->count(), 'pending_approvals' => $pendingApprovals, 'pending_handoffs' => AgentHandoff::whereIn('status', ['pending', 'accepted'])->count(), 'due_schedules' => $schedules->dueSchedules()->count(), 'pending_plans' => WeeklyMarketingPlan::where('status', 'draft')->count(), 'agent_tasks' => MarketingTask::whereIn('source_type', ['agent_action', 'weekly_marketing_plan'])->count()],
            'runs' => $runs,
            'upcomingSchedules' => AgentSchedule::with(['website', 'agent'])->where('enabled', true)->whereNotNull('next_run_at')->orderBy('next_run_at')->limit(8)->get(),
            'eventRuns' => AgentRun::with(['website', 'agent'])->where('trigger_type', 'gsc_sync')->latest()->limit(8)->get(),
            'failures' => AgentRun::with(['website', 'agent'])->where('status', 'failed')->latest()->limit(8)->get(),
            'handoffs' => AgentHandoff::with(['website', 'fromAgent', 'toAgent'])->whereIn('status', ['pending', 'accepted', 'failed'])->latest()->limit(8)->get(),
            'approvals' => AgentAction::with(['website', 'run.agent', 'reviewer'])->whereNotNull('reviewed_at')->latest('reviewed_at')->limit(8)->get(),
        ]);
    }

    public function show(AgentRun $agentRun, AgentRunSanitizer $sanitizer): View
    {
        $agentRun->load(['agent', 'website', 'parentRun.agent', 'childRuns.agent', 'actions.createdTask', 'handoffs.fromAgent', 'handoffs.toAgent', 'weeklyMarketingPlan']);
        return view('agent-operations.show', ['run' => $agentRun, 'safeInput' => $sanitizer->text($agentRun->input_summary), 'safeOutput' => $sanitizer->text($agentRun->output_summary), 'safeError' => $sanitizer->text($agentRun->error_message), 'safeActions' => $agentRun->actions->map(fn ($action) => ['model' => $action, 'title' => $sanitizer->text($action->title), 'description' => $sanitizer->text($action->description), 'expected' => $sanitizer->text($action->expected_result), 'task' => $sanitizer->text($action->createdTask?->title)]), 'safeHandoffs' => $agentRun->handoffs->map(fn ($handoff) => ['model' => $handoff, 'reason' => $sanitizer->text($handoff->reason), 'expected' => $sanitizer->text($handoff->expected_output)])]);
    }

    public function retry(AgentRun $agentRun, AgentTeamService $team): RedirectResponse
    {
        if ($agentRun->status !== 'failed') return back()->with('error', 'Only failed runs can be retried.');
        $lock = Cache::lock('agent-retry:workspace:'.$agentRun->website_id, 600);
        if (! $lock->get()) return back()->with('error', 'Another retry or orchestration is already active for this workspace.');
        try {
            $agentRun->increment('retry_count');
            $metadata = ['trigger_type' => 'retry', 'correlation_id' => $agentRun->correlation_id, 'retry_of_run_id' => $agentRun->id, 'trigger_reason' => 'Human retry of failed run'];
            if ($agentRun->run_type === 'full_team' && ! $agentRun->parent_run_id) {
                foreach ($agentRun->childRuns()->where('status', 'failed')->with(['agent', 'website'])->get() as $child) {
                    $child->increment('retry_count');
                    $team->runAgent($child->agent, $child->website, 'full_team', [...$metadata, 'parent_run_id' => $agentRun->id], $child);
                }
                if ($agentRun->childRuns()->where('status', 'failed')->doesntExist()) $team->runAgent($agentRun->agent, $agentRun->website, 'full_team', [...$metadata, 'orchestration_parent' => true], $agentRun);
            } else {
                $team->runAgent($agentRun->agent, $agentRun->website, $agentRun->run_type, $metadata, $agentRun);
            }
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        } finally { $lock->release(); }
        return back()->with('success', 'Run retried. Any generated action remains pending approval.');
    }

    public function cancel(AgentRun $agentRun): RedirectResponse
    {
        if ($agentRun->status !== 'pending') return back()->with('error', 'Only pending runs can be cancelled.');
        $agentRun->update(['status' => 'cancelled', 'completed_at' => now(), 'error_message' => 'Cancelled by an authenticated reviewer before execution.']);
        return back()->with('success', 'Pending run cancelled.');
    }
}
