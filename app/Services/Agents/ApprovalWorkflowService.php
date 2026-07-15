<?php

namespace App\Services\Agents;

use App\Models\Agent;
use App\Models\AgentAction;
use App\Models\AgentHandoff;
use App\Models\MarketingTask;
use App\Models\User;
use App\Models\WeeklyMarketingPlan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ApprovalWorkflowService
{
    public function __construct(private readonly AgentMemoryService $memories, private readonly AgentHandoffService $handoffs, private readonly AgentTeamService $team) {}

    public function review(Model $item, string $operation, User $reviewer, ?string $notes = null, ?string $revisionReason = null): Model
    {
        return match (true) {
            $item instanceof AgentAction => $this->reviewAction($item, $operation, $reviewer, $notes, $revisionReason),
            $item instanceof MarketingTask => $this->reviewTask($item, $operation, $reviewer, $notes, $revisionReason),
            $item instanceof WeeklyMarketingPlan => $this->reviewPlan($item, $operation, $reviewer, $notes, $revisionReason),
            $item instanceof AgentHandoff => $this->reviewHandoff($item, $operation, $reviewer, $notes, $revisionReason),
            default => throw new InvalidArgumentException('Unsupported approval item.'),
        };
    }

    public function createTaskFromAction(AgentAction $action, ?User $reviewer = null, bool $approved = false): MarketingTask
    {
        $title = data_get($action->metadata, 'suggested_task', $action->title);
        $query = MarketingTask::where('website_id', $action->website_id)->where('title', $title);
        if ($action->related_page_url) $query->where('related_page_url', $action->related_page_url);
        if ($action->related_query) $query->where('source_value', $action->related_query);
        $task = $query->first() ?: MarketingTask::create(['website_id' => $action->website_id, 'title' => $title, 'description' => $action->description, 'expected_result' => $action->expected_result, 'priority' => $action->priority === 'critical' ? 'high' : $action->priority, 'source_type' => 'agent_action', 'source_value' => $action->related_query ?: $action->title, 'related_page_url' => $action->related_page_url, 'status' => 'pending', 'approval_status' => $approved ? 'approved' : 'pending', 'reviewed_by' => $approved ? $reviewer?->id : null, 'reviewed_at' => $approved ? now() : null]);
        if ($approved && $task->approval_status !== 'approved') $task->update(['approval_status' => 'approved', 'reviewed_by' => $reviewer?->id, 'reviewed_at' => now()]);
        $action->update(['created_task_id' => $task->id]);
        return $task;
    }

    private function reviewAction(AgentAction $action, string $operation, User $reviewer, ?string $notes, ?string $reason): AgentAction
    {
        $action->loadMissing(['run.agent', 'website']);
        if ($operation === 'request_revision') return $this->requestRevision($action, $reviewer, $notes, $reason);
        $status = $operation === 'ignore' ? 'ignored' : 'approved';
        $action->update($this->audit($reviewer, $notes) + ['status' => $status]);
        if ($status === 'approved') {
            $this->memories->updateOrRemember($action->run->agent, $action->website, 'approved_action', 'agent-action:'.$action->id, 'Approved: '.$action->title.'. Do not recreate while its task is open.', ['confidence' => 1, 'source_type' => 'agent_action', 'source_id' => $action->id]);
            if ($action->run->agent->slug === 'marketing-director' && ($taskManager = Agent::where('slug', 'task-manager')->first())) $this->handoffs->createHandoff($action->website, $action->run->agent, $taskManager, 'Marketing Director action approved for implementation.', ['originating_action_id' => $action->id, 'related_page_url' => $action->related_page_url, 'related_query' => $action->related_query], 'Create or link one duplicate-safe implementation task.', $action->run);
            if ($operation === 'approve_task') $this->createTaskFromAction($action, $reviewer, true);
        } else {
            $this->memories->updateOrRemember($action->run->agent, $action->website, 'ignored_action', 'agent-action:'.$action->id, 'Ignored recommendation: '.$action->title, ['confidence' => 1, 'source_type' => 'agent_action', 'source_id' => $action->id]);
        }
        return $action->fresh();
    }

    private function requestRevision(AgentAction $action, User $reviewer, ?string $notes, ?string $reason): AgentAction
    {
        $reason = trim((string) ($reason ?: $notes));
        if ($reason === '') throw new InvalidArgumentException('A revision reason is required.');
        $action->update($this->audit($reviewer, $notes) + ['status' => 'revision_requested', 'revision_requested_at' => now(), 'revision_reason' => $reason]);
        if (! AgentAction::where('original_action_id', $action->id)->exists()) {
            $this->team->runAgent($action->run->agent, $action->website, 'revision', ['trigger_type' => 'revision', 'correlation_id' => $action->run->correlation_id ?: (string) Str::uuid(), 'trigger_reason' => 'Human revision requested', 'trigger_evidence' => ['revision_reason' => $reason, 'original_action_id' => $action->id], 'original_action_id' => $action->id]);
        }
        return $action->fresh();
    }

    private function reviewTask(MarketingTask $task, string $operation, User $reviewer, ?string $notes, ?string $reason): MarketingTask
    {
        if ($operation === 'request_revision') {
            $task->update($this->audit($reviewer, $notes) + ['approval_status' => 'revision_requested', 'revision_requested_at' => now(), 'revision_reason' => $reason ?: $notes]);
            if ($action = AgentAction::with(['run.agent', 'website'])->where('created_task_id', $task->id)->first()) {
                $this->requestRevision($action, $reviewer, $notes, $reason);
            } elseif ($task->source_type === 'weekly_marketing_plan' && str_starts_with((string) $task->source_value, 'plan:')) {
                $plan = WeeklyMarketingPlan::find((int) str($task->source_value)->after('plan:')->toString());
                if ($plan) $this->reviewPlan($plan, 'request_revision', $reviewer, $notes, $reason);
            }
        } else {
            $task->update($this->audit($reviewer, $notes) + ['approval_status' => $operation === 'ignore' ? 'ignored' : 'approved', 'status' => $operation === 'ignore' ? 'ignored' : $task->status]);
        }
        return $task->fresh();
    }

    private function reviewPlan(WeeklyMarketingPlan $plan, string $operation, User $reviewer, ?string $notes, ?string $reason): WeeklyMarketingPlan
    {
        if ($operation === 'request_revision') {
            $reason = trim((string) ($reason ?: $notes));
            if ($reason === '') throw new InvalidArgumentException('A revision reason is required.');
            $plan->update($this->audit($reviewer, $notes) + ['status' => 'revision_requested', 'revision_requested_at' => now(), 'revision_reason' => $reason]);
            $existing = AgentAction::where('metadata->revision_source->type', 'weekly_marketing_plan')->where('metadata->revision_source->id', $plan->id)->exists();
            if (! $existing) {
                $agent = $plan->agentRun?->agent ?: Agent::where('slug', 'marketing-director')->firstOrFail();
                $this->team->runAgent($agent, $plan->website, 'revision', ['trigger_type' => 'revision', 'correlation_id' => $plan->agentRun?->correlation_id ?: (string) Str::uuid(), 'trigger_reason' => 'Weekly marketing plan revision requested', 'trigger_evidence' => ['revision_reason' => $reason, 'plan_id' => $plan->id], 'revision_source' => ['type' => 'weekly_marketing_plan', 'id' => $plan->id]]);
            }
            return $plan->fresh();
        }
        $status = $operation === 'ignore' ? 'ignored' : 'approved';
        $plan->update($this->audit($reviewer, $notes) + ['status' => $status, 'approved_at' => $status === 'approved' ? now() : null]);
        if ($operation === 'approve_task') {
            foreach ($plan->top_priorities ?? [] as $priority) {
                if ($action = AgentAction::find($priority['action_id'] ?? null)) $this->createTaskFromAction($action, $reviewer, true);
            }
        }
        return $plan->fresh();
    }

    private function reviewHandoff(AgentHandoff $handoff, string $operation, User $reviewer, ?string $notes, ?string $reason): AgentHandoff
    {
        $handoff->update($this->audit($reviewer, $notes));
        if ($operation === 'ignore') return $this->handoffs->ignoreHandoff($handoff);
        if ($operation === 'request_revision') {
            $action = AgentAction::with(['run.agent', 'website'])->find(data_get($handoff->context, 'originating_action_id'));
            if (! $action) throw new InvalidArgumentException('This handoff has no originating action to revise.');
            $this->requestRevision($action, $reviewer, $notes, $reason);
            return $handoff->fresh();
        }
        if ($operation === 'approve_task' && ($action = AgentAction::find(data_get($handoff->context, 'originating_action_id')))) $this->createTaskFromAction($action, $reviewer, true);
        return $this->handoffs->acceptHandoff($handoff);
    }

    private function audit(User $reviewer, ?string $notes): array
    {
        return ['reviewed_by' => $reviewer->id, 'reviewed_at' => now(), 'review_notes' => $notes];
    }
}
