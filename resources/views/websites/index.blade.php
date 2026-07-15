<x-layouts.app heading="Workspaces">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div><p class="text-sm font-semibold text-navy">Managed workspaces</p><p class="mt-1 text-sm text-slate-500">Connected evidence, AI insights, and goal-based conversion work.</p></div>
        <a href="{{ route('websites.create') }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-teal px-4 py-2 text-sm font-semibold text-white shadow-[0_8px_20px_rgba(1,101,118,.2)]"><i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>Add workspace</a>
    </div>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($websites as $website)
            <article class="app-panel overflow-hidden">
                <div class="border-b border-slate-100 p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0"><span class="mb-3 grid h-9 w-9 place-items-center rounded-lg bg-cyan-50 text-teal"><i data-lucide="globe-2" class="h-4 w-4" aria-hidden="true"></i></span><h2 class="truncate font-bold text-navy">{{ $website->name }}</h2><a href="{{ $website->url }}" class="mt-1 block truncate text-xs font-medium text-teal" target="_blank" rel="noopener">{{ $website->url }}</a><p class="mt-2 text-xs font-semibold text-slate-500">{{ data_get($website, 'goal_profile.label') }}</p></div>
                        <span class="shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700">{{ ucfirst($website->status) }}</span>
                    </div>
                </div>
                <dl class="grid grid-cols-3 divide-x divide-slate-100 px-2 py-4 text-center text-xs">
                    <div><dt class="text-slate-400">Audits</dt><dd class="mt-1 text-lg font-bold text-navy">{{ $website->seo_audits_count }}</dd></div>
                    <div><dt class="text-slate-400">Insights</dt><dd class="mt-1 text-lg font-bold text-navy">{{ $website->ai_insights_count }}</dd></div>
                    <div><dt class="text-slate-400">Tasks</dt><dd class="mt-1 text-lg font-bold text-navy">{{ $website->marketing_tasks_count }}</dd></div>
                </dl>
                <div class="flex flex-wrap gap-2 border-t border-slate-100 p-4">
                    <a href="{{ route('websites.show', $website) }}" class="inline-flex min-h-10 flex-1 items-center justify-center gap-2 rounded-lg bg-navy px-3 py-2 text-sm font-semibold text-white"><i data-lucide="arrow-up-right" class="h-4 w-4" aria-hidden="true"></i>Open</a>
                    <form method="POST" action="{{ route('websites.audit', $website) }}">@csrf<button class="grid h-10 w-10 place-items-center rounded-lg bg-white text-navy shadow-[inset_0_0_0_1px_rgba(5,18,55,.13)]" title="Run SEO scan" aria-label="Run SEO scan for {{ $website->name }}"><i data-lucide="scan-search" class="h-4 w-4" aria-hidden="true"></i></button></form>
                    <form method="POST" action="{{ route('websites.ai-insights.store', $website) }}">@csrf<button class="grid h-10 w-10 place-items-center rounded-lg bg-cyan-50 text-teal shadow-[inset_0_0_0_1px_rgba(1,101,118,.12)]" title="Generate AI insight" aria-label="Generate AI insight for {{ $website->name }}"><i data-lucide="sparkles" class="h-4 w-4" aria-hidden="true"></i></button></form>
                </div>
            </article>
        @empty
            <div class="app-panel grid min-h-64 place-items-center p-8 text-center md:col-span-2 xl:col-span-3"><div><span class="mx-auto grid h-12 w-12 place-items-center rounded-lg bg-cyan-50 text-teal"><i data-lucide="globe-2" class="h-5 w-5" aria-hidden="true"></i></span><h2 class="mt-4 font-bold text-navy">No workspaces yet</h2><p class="mt-2 text-sm text-slate-500">Add a website workspace and configure the conversion action that matters most.</p><a href="{{ route('websites.create') }}" class="mt-5 inline-flex min-h-10 items-center gap-2 rounded-lg bg-teal px-4 py-2 text-sm font-semibold text-white"><i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>Add workspace</a></div></div>
        @endforelse
    </div>
    <div class="mt-6">{{ $websites->links() }}</div>
</x-layouts.app>
