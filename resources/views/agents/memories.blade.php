<x-layouts.app heading="Agent Memory">
    <section class="rounded-lg bg-navy p-6 text-white shadow-[0_18px_50px_rgba(5,18,55,0.22)]">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div><p class="text-xs font-bold uppercase tracking-[.18em] text-teal-200">Persistent context</p><h2 class="mt-2 text-2xl font-bold">{{ $agent ? $agent->name.' Memory' : 'All Agent Memory' }}</h2><p class="mt-2 max-w-2xl text-sm text-slate-300">Current decisions, instructions, and learned workspace context used by future agent runs.</p></div>
            @if($agent)<a href="{{ route('agents.show', $agent) }}" class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-white/10 px-4 text-sm font-semibold"><i data-lucide="arrow-left" class="size-4"></i>Agent details</a>@endif
        </div>
    </section>

    @if($agent)
        <section class="mt-6 rounded-lg bg-white p-5 shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)]">
            <h2 class="font-bold text-navy">Add lasting instruction</h2><p class="mt-1 text-sm text-slate-500">Use workspace preferences that should guide this agent across future runs. Never enter credentials or customer records.</p>
            <form method="POST" action="{{ route('agents.memories.store', $agent) }}" class="mt-4 grid gap-3 lg:grid-cols-[220px_1fr_180px_auto]">@csrf
                <select name="website_id" required class="min-h-11 rounded-lg border border-slate-200 bg-white px-3 text-sm text-navy"><option value="">Choose workspace</option>@foreach($websites as $website)<option value="{{ $website->id }}">{{ $website->name }}</option>@endforeach</select>
                <input name="instruction" required maxlength="3000" placeholder="Example: Prioritize the trial activation path before retention ideas." class="min-h-11 rounded-lg border border-slate-200 px-3 text-sm text-navy">
                <input type="datetime-local" name="expires_at" title="Optional expiry" class="min-h-11 rounded-lg border border-slate-200 px-3 text-sm text-navy">
                <button class="min-h-11 rounded-lg bg-teal px-4 text-sm font-semibold text-white">Save instruction</button>
            </form>
        </section>
    @else
        <form method="GET" class="mt-6 flex flex-wrap gap-3 rounded-lg bg-white p-4 shadow-[0_0_0_1px_rgba(5,18,55,0.06)]"><select name="website_id" class="min-h-10 min-w-56 rounded-lg border border-slate-200 bg-white px-3 text-sm text-navy"><option value="">All workspaces</option>@foreach($websites as $website)<option value="{{ $website->id }}" @selected(request('website_id') == $website->id)>{{ $website->name }}</option>@endforeach</select><button class="min-h-10 rounded-lg bg-navy px-4 text-sm font-semibold text-white">Filter</button></form>
    @endif

    <section class="mt-6"><div class="mb-4"><h2 class="text-xl font-bold text-navy">Memory timeline</h2><p class="mt-1 text-sm text-slate-500">Expired memories remain visible here but are excluded from agent context.</p></div><div class="grid gap-3">@forelse($memories as $memory)@include('agents.partials.memory-card', ['memory' => $memory])@empty<div class="rounded-lg bg-white px-6 py-14 text-center"><i data-lucide="brain" class="mx-auto size-8 text-slate-300"></i><p class="mt-3 font-semibold text-navy">No memories yet</p><p class="mt-1 text-sm text-slate-500">Memory appears as actions are reviewed and work is completed.</p></div>@endforelse</div></section>
    <div class="mt-6">{{ $memories->links() }}</div>
</x-layouts.app>
