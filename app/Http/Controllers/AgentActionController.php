<?php

namespace App\Http\Controllers;

use App\Models\AgentAction;
use App\Models\MarketingTask;
use App\Models\Agent;
use App\Services\Agents\AgentHandoffService;
use App\Services\Agents\AgentMemoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AgentActionController extends Controller
{
    public function update(Request $request, AgentAction $agentAction, AgentMemoryService $memories, AgentHandoffService $handoffs): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['reviewed', 'approved', 'completed', 'ignored'])]]);
        $agentAction->update($data);
        $agentAction->loadMissing(['run.agent', 'website']);
        if ($data['status'] === 'approved') {
            $memories->updateOrRemember($agentAction->run->agent, $agentAction->website, 'approved_action', 'agent-action:'.$agentAction->id, 'Approved: '.$agentAction->title.'. Do not recreate while its task is open.', ['confidence' => 1, 'source_type' => 'agent_action', 'source_id' => $agentAction->id]);
            if ($agentAction->run->agent->slug === 'marketing-director' && ($taskManager = Agent::where('slug', 'task-manager')->first())) {
                $handoffs->createHandoff($agentAction->website, $agentAction->run->agent, $taskManager, 'Marketing Director action approved for implementation.', ['originating_action_id' => $agentAction->id, 'related_page_url' => $agentAction->related_page_url, 'related_query' => $agentAction->related_query], 'Create or link one duplicate-safe implementation task.', $agentAction->run);
            }
        }
        if ($data['status'] === 'ignored') {
            $memories->updateOrRemember($agentAction->run->agent, $agentAction->website, 'ignored_action', 'agent-action:'.$agentAction->id, 'Ignored recommendation: '.$agentAction->title, ['confidence' => 1, 'source_type' => 'agent_action', 'source_id' => $agentAction->id]);
        }
        $message = $data['status'] === 'approved'
            ? 'Action approved for planned work. No campaign, message, or website change was executed.'
            : 'Agent action marked '.str_replace('_', ' ', $data['status']).'.';

        return back()->with('success', $message);
    }

    public function storeTask(AgentAction $agentAction): RedirectResponse
    {
        $title = $agentAction->metadata['suggested_task'] ?? $agentAction->title;
        $query = MarketingTask::where('website_id', $agentAction->website_id)->where('title', $title);

        if ($agentAction->related_page_url) {
            $query->where('related_page_url', $agentAction->related_page_url);
        }
        if ($agentAction->related_query) {
            $query->where('source_value', $agentAction->related_query);
        }

        $task = $query->first() ?: MarketingTask::create([
            'website_id' => $agentAction->website_id,
            'title' => $title,
            'description' => $agentAction->description,
            'expected_result' => $agentAction->expected_result,
            'priority' => $agentAction->priority === 'critical' ? 'high' : $agentAction->priority,
            'source_type' => 'agent_action',
            'source_value' => $agentAction->related_query ?: $agentAction->title,
            'related_page_url' => $agentAction->related_page_url,
            'status' => 'pending',
        ]);

        $agentAction->update(['created_task_id' => $task->id, 'status' => $agentAction->status === 'pending' ? 'reviewed' : $agentAction->status]);

        return back()->with('success', $task->wasRecentlyCreated ? 'Task created from agent action.' : 'Existing matching task linked; no duplicate was created.');
    }
}
