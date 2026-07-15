<?php

namespace App\Http\Controllers;

use App\Models\AgentAction;
use App\Models\MarketingTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AgentActionController extends Controller
{
    public function update(Request $request, AgentAction $agentAction): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['reviewed', 'approved', 'completed', 'ignored'])]]);
        $agentAction->update($data);
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
