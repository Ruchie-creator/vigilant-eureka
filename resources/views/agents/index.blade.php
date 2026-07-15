<x-layouts.app heading="AI Marketing Team">
    <section class="rounded-lg bg-navy p-6 text-white shadow-[0_18px_50px_rgba(5,18,55,0.22)]">
        <div class="flex flex-wrap items-end justify-between gap-5">
            <div class="max-w-3xl"><p class="text-xs font-bold uppercase tracking-[.18em] text-teal-200">Agentic Marketing Team v1</p><h2 class="mt-2 text-2xl font-bold sm:text-3xl">Specialist analysis, coordinated priorities</h2><p class="mt-3 text-sm leading-6 text-slate-300">Agents use connected workspace evidence to create recommendations and suggested tasks. Campaigns, messages, and website changes always require approval.</p></div>
            <span class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-3 py-2 text-xs font-semibold"><i data-lucide="shield-check" class="size-4 text-teal-200"></i>No automatic external execution</span>
        </div>
    </section>

    <section class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @foreach($agents as $agent)
            @php
                $latestAction = $agent->latestRun?->actions?->first();
            @endphp
            <article class="flex min-w-0 flex-col rounded-lg bg-white p-5 shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)]">
                <div class="flex items-start justify-between gap-3"><div class="grid size-11 place-items-center rounded-lg bg-teal/10 text-teal"><i data-lucide="bot" class="size-5"></i></div><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $agent->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ ucfirst($agent->status) }}</span></div>
                <h2 class="mt-4 text-lg font-bold text-navy">{{ $agent->name }}</h2>
                <p class="mt-1 text-xs font-bold uppercase tracking-wide text-teal">{{ $agent->role }}</p>
                <p class="mt-3 text-sm leading-6 text-slate-600">{{ $agent->goal }}</p>
                <div class="mt-4 rounded-lg bg-slate-50 p-3"><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Latest action</p><p class="mt-2 line-clamp-2 text-sm font-medium text-navy">{{ $latestAction?->title ?? 'No action generated yet' }}</p><p class="mt-1 text-xs text-slate-400">{{ $agent->latestRun ? ucfirst($agent->latestRun->status).' · '.$agent->latestRun->created_at->diffForHumans() : 'Ready for first run' }}</p></div>
                <div class="mt-auto pt-5">
                    <form method="POST" action="{{ route('agents.run', $agent) }}" class="flex gap-2">@csrf<select name="website_id" required class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-navy"><option value="">Choose workspace</option>@foreach($websites as $website)<option value="{{ $website->id }}">{{ $website->name }}</option>@endforeach</select><button class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-teal px-3 text-sm font-semibold text-white"><i data-lucide="play" class="size-4"></i>Run</button></form>
                    <a href="{{ route('agents.show', $agent) }}" class="mt-2 inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-lg text-sm font-semibold text-navy hover:bg-slate-50">View details<i data-lucide="arrow-right" class="size-4"></i></a>
                </div>
            </article>
        @endforeach
    </section>
</x-layouts.app>
