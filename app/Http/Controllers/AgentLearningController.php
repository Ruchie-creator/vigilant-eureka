<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentMemory;
use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AgentLearningController extends Controller
{
    private const TYPES = ['successful_action', 'unsuccessful_action', 'inconclusive_action', 'performance_pattern'];

    public function index(Request $request): View
    {
        $memories = AgentMemory::with(['agent', 'website', 'outcome'])->whereIn('memory_type', self::TYPES)
            ->when($request->filled('website_id'), fn ($query) => $query->where('website_id', $request->integer('website_id')))
            ->when($request->filled('agent_id'), fn ($query) => $query->where('agent_id', $request->integer('agent_id')))
            ->when($request->filled('result_type'), fn ($query) => $query->where('memory_type', $request->string('result_type')))
            ->when($request->filled('conversion_goal'), fn ($query) => $query->where('learning_metadata->conversion_goal', $request->string('conversion_goal')))
            ->when($request->filled('confidence'), function ($query) use ($request) {
                match ($request->string('confidence')->toString()) { 'high' => $query->where('confidence', '>=', .8), 'medium' => $query->whereBetween('confidence', [.5, .7999]), 'low' => $query->where('confidence', '<', .5), default => null };
            })->latest()->paginate(24)->withQueryString();

        return view('agent-learning.index', ['memories' => $memories, 'websites' => Website::orderBy('name')->get(), 'agents' => Agent::orderBy('name')->get(), 'conversionGoals' => Website::whereNotNull('primary_conversion_goal')->distinct()->pluck('primary_conversion_goal')]);
    }

    public function update(Request $request, AgentMemory $agentMemory): RedirectResponse
    {
        abort_unless(in_array($agentMemory->memory_type, self::TYPES, true), 404);
        $data = $request->validate(['memory_type' => ['required', Rule::in(self::TYPES)], 'confidence' => ['required', 'numeric', 'between:0,1'], 'enabled' => ['required', 'boolean'], 'review_notes' => ['nullable', 'string', 'max:5000']]);
        $agentMemory->update($data);
        return back()->with('success', 'Learning classification updated. Original outcome evidence was preserved.');
    }
}
