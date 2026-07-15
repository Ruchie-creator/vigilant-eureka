<x-layouts.app :heading="$agent->name">
    <section class="rounded-lg bg-navy p-6 text-white shadow-[0_18px_50px_rgba(5,18,55,0.22)]">
        <div class="flex flex-wrap items-start justify-between gap-5">
            <div class="max-w-3xl"><a href="{{ route('agents.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-teal-200"><i data-lucide="arrow-left" class="size-4"></i>AI Marketing Team</a><h2 class="mt-4 text-2xl font-bold">{{ $agent->name }}</h2><p class="mt-2 text-sm font-semibold text-teal-200">{{ $agent->role }}</p><p class="mt-3 text-sm leading-6 text-slate-300">{{ $agent->goal }}</p></div>
            <form method="POST" action="{{ route('agents.run', $agent) }}" class="flex w-full gap-2 rounded-lg bg-white/10 p-3 sm:w-auto">@csrf<select name="website_id" required class="min-w-0 flex-1 rounded-lg border-0 bg-white px-3 text-sm font-medium text-navy sm:w-56"><option value="">Choose workspace</option>@foreach($websites as $website)<option value="{{ $website->id }}">{{ $website->name }}</option>@endforeach</select><button class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-teal px-4 text-sm font-semibold text-white"><i data-lucide="play" class="size-4"></i>Run Agent</button></form>
        </div>
    </section>

    <section class="mt-6 rounded-lg bg-white p-5 shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)]"><h2 class="font-bold text-navy">Instructions</h2><p class="mt-2 text-sm leading-6 text-slate-600">{{ $agent->instructions ?: 'Use connected workspace evidence and create reviewable recommendations.' }}</p><div class="mt-4 inline-flex items-center gap-2 rounded-lg bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800"><i data-lucide="shield-alert" class="size-4"></i>External actions require human approval.</div></section>

    <div class="mt-6 space-y-5">
        @forelse($runs as $run)
            <section>
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2"><div><p class="text-sm font-bold text-navy">{{ $run->website?->name ?? 'Global run' }}</p><p class="text-xs text-slate-400">{{ str_replace('_', ' ', ucfirst($run->run_type)) }} · {{ $run->created_at->format('M j, Y g:i A') }}</p></div><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ ucfirst($run->status) }}</span></div>
                @if($run->status === 'failed')<div class="rounded-lg bg-rose-50 p-4 text-sm text-rose-700">{{ $run->error_message }}</div>@endif
                <div class="grid gap-4">@foreach($run->actions as $action)@include('agents.partials.action-card', ['action' => $action])@endforeach</div>
            </section>
        @empty
            <div class="rounded-lg bg-white px-6 py-14 text-center shadow-[0_0_0_1px_rgba(5,18,55,0.06)]"><i data-lucide="bot" class="mx-auto size-8 text-slate-300"></i><p class="mt-3 font-semibold text-navy">No runs yet</p><p class="mt-1 text-sm text-slate-500">Choose a workspace and run this agent to generate its first action.</p></div>
        @endforelse
    </div>
    <div class="mt-6">{{ $runs->links() }}</div>
</x-layouts.app>
