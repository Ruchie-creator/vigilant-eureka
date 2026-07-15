<?php

namespace App\Services\Agents;

use App\Models\AgentAction;
use App\Models\Website;

class TaskManagerAgentService extends AgentService
{
    protected function action(Website $website, array $goal): array
    {
        $candidate = AgentAction::query()
            ->with('run.agent')
            ->where('website_id', $website->id)
            ->whereNull('created_task_id')
            ->whereIn('status', ['pending', 'reviewed', 'approved'])
            ->whereHas('run.agent', fn ($query) => $query->where('slug', '!=', 'task-manager'))
            ->orderByRaw("CASE priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END")
            ->latest()
            ->first();

        if (! $candidate) {
            return $this->baseAction($goal, 'No duplicate task needed', 'Every current agent action is already linked to a task, or no actionable recommendation exists.', 'Avoiding duplicate work keeps the execution board reliable.', 'Wait for a new reviewed agent action before creating another task.', 'A clean task backlog without duplicate work.', 'No task required', 'low') + ['type' => 'task_plan', 'approval_required' => false, 'data_sources' => ['agent actions', 'marketing tasks']];
        }

        $duplicate = $website->marketingTasks()->where('title', $candidate->metadata['suggested_task'] ?? $candidate->title)
            ->when($candidate->related_page_url, fn ($query) => $query->where('related_page_url', $candidate->related_page_url))
            ->exists();
        $recommended = $duplicate
            ? 'Review the existing matching task instead of creating a duplicate.'
            : 'Create one pending task from this action, assign a due date, and keep external execution approval-gated.';

        return $this->baseAction($goal, $duplicate ? 'Review existing task for '.$candidate->title : 'Create task: '.($candidate->metadata['suggested_task'] ?? $candidate->title), $candidate->run->agent->name.' produced an actionable recommendation that is not linked to a task.', 'A single owned task is easier to prioritize and complete without duplicating work.', $recommended, $candidate->expected_result ?: 'A clear next step toward '.$goal['primary_action_label'].'.', $candidate->metadata['suggested_task'] ?? $candidate->title, $duplicate ? 'low' : $candidate->priority) + ['type' => 'task_plan', 'page' => $candidate->related_page_url, 'query' => $candidate->related_query, 'approval_required' => ! $duplicate, 'data_sources' => ['agent actions', 'marketing tasks'], 'metadata' => ['source_agent_action_id' => $candidate->id, 'duplicate_task_found' => $duplicate]];
    }
}
