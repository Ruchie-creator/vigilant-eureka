<x-layouts.app heading="Websites">
    <div class="mb-5 flex justify-end"><a href="{{ route('websites.create') }}" class="rounded-lg bg-teal px-4 py-2 text-sm font-semibold text-white">Add Website</a></div>
    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($websites as $website)
            <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-soft">
                <div class="flex items-start justify-between gap-3">
                    <div><h2 class="font-semibold text-navy">{{ $website->name }}</h2><a href="{{ $website->url }}" class="mt-1 block break-all text-sm text-teal" target="_blank" rel="noopener">{{ $website->url }}</a></div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $website->status }}</span>
                </div>
                <dl class="mt-5 grid grid-cols-3 gap-3 text-sm"><div><dt class="text-slate-500">Audits</dt><dd class="font-semibold">{{ $website->seo_audits_count }}</dd></div><div><dt class="text-slate-500">Insights</dt><dd class="font-semibold">{{ $website->ai_insights_count }}</dd></div><div><dt class="text-slate-500">Tasks</dt><dd class="font-semibold">{{ $website->marketing_tasks_count }}</dd></div></dl>
                <div class="mt-5 flex flex-wrap gap-2">
                    <a href="{{ route('websites.show', $website) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold">Open</a>
                    <form method="POST" action="{{ route('websites.audit', $website) }}">@csrf<button class="rounded-lg bg-navy px-3 py-2 text-sm font-semibold text-white">Scan</button></form>
                    <form method="POST" action="{{ route('websites.ai-insights.store', $website) }}">@csrf<button class="rounded-lg bg-teal px-3 py-2 text-sm font-semibold text-white">Generate AI Insight</button></form>
                </div>
            </article>
        @empty
            <div class="rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500 md:col-span-2 xl:col-span-3">Add the first healthcare website to begin monitoring.</div>
        @endforelse
    </div>
    <div class="mt-6">{{ $websites->links() }}</div>
</x-layouts.app>
