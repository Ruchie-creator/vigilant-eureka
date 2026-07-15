<?php

namespace App\Http\Controllers;

use App\Models\AgentAction;
use App\Services\Agents\ApprovalWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AgentActionController extends Controller
{
    public function update(Request $request, AgentAction $agentAction, ApprovalWorkflowService $approvals): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['reviewed', 'approved', 'completed', 'ignored'])]]);
        if (in_array($data['status'], ['approved', 'ignored'], true)) $approvals->review($agentAction, $data['status'] === 'approved' ? 'approve' : 'ignore', $request->user());
        else $agentAction->update($data);
        $message = $data['status'] === 'approved'
            ? 'Action approved for planned work. No campaign, message, or website change was executed.'
            : 'Agent action marked '.str_replace('_', ' ', $data['status']).'.';

        return back()->with('success', $message);
    }

    public function storeTask(AgentAction $agentAction, ApprovalWorkflowService $approvals): RedirectResponse
    {
        $task = $approvals->createTaskFromAction($agentAction);
        if ($agentAction->status === 'pending') $agentAction->update(['status' => 'reviewed']);

        return back()->with('success', $task->wasRecentlyCreated ? 'Task created from agent action.' : 'Existing matching task linked; no duplicate was created.');
    }
}
