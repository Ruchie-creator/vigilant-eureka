<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentMemory;
use App\Models\Website;
use App\Services\Agents\AgentMemoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentMemoryController extends Controller
{
    public function all(Request $request): View
    {
        $memories = AgentMemory::with(['agent', 'website'])->when($request->website_id, fn ($query) => $query->where('website_id', $request->website_id))->latest()->paginate(30)->withQueryString();
        return view('agents.memories', ['agent' => null, 'memories' => $memories, 'websites' => Website::where('status', 'active')->orderBy('name')->get()]);
    }

    public function index(Agent $agent): View
    {
        return view('agents.memories', ['agent' => $agent, 'memories' => $agent->memories()->with(['agent', 'website'])->latest()->paginate(30), 'websites' => Website::where('status', 'active')->orderBy('name')->get()]);
    }

    public function store(Request $request, Agent $agent, AgentMemoryService $service): RedirectResponse
    {
        $data = $request->validate(['website_id' => ['required', 'exists:websites,id'], 'instruction' => ['required', 'string', 'max:3000'], 'confidence' => ['nullable', 'numeric', 'between:0,1'], 'expires_at' => ['nullable', 'date', 'after:now']]);
        $website = Website::findOrFail($data['website_id']);
        $service->updateOrRemember($agent, $website, 'user_instruction', 'user-instruction:'.sha1(mb_strtolower(trim($data['instruction']))), trim($data['instruction']), ['confidence' => $data['confidence'] ?? 1, 'source_type' => 'user_instruction', 'expires_at' => $data['expires_at'] ?? null]);
        return back()->with('success', 'Lasting workspace instruction saved.');
    }

    public function destroy(AgentMemory $agentMemory, AgentMemoryService $service): RedirectResponse { $service->forget($agentMemory); return back()->with('success', 'Agent memory forgotten.'); }
    public function expire(AgentMemory $agentMemory): RedirectResponse { $agentMemory->update(['expires_at' => now()]); return back()->with('success', 'Agent memory marked expired.'); }
}
