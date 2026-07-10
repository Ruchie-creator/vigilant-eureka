<x-layouts.app heading="AI Website Growth & Conversion Agent">
    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-soft">
            <p class="text-sm font-medium text-slate-500">Total Clicks</p>
            <p class="mt-3 text-4xl font-bold text-navy">{{ number_format($totalClicks) }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-soft">
            <p class="text-sm font-medium text-slate-500">Total Impressions</p>
            <p class="mt-3 text-4xl font-bold text-navy">{{ number_format($totalImpressions) }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-soft">
            <p class="text-sm font-medium text-slate-500">Average CTR</p>
            <p class="mt-3 text-4xl font-bold text-navy">{{ number_format($averageCtr, 2) }}%</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-soft">
            <p class="text-sm font-medium text-slate-500">Average Position</p>
            <p class="mt-3 text-4xl font-bold text-navy">{{ number_format($averagePosition, 1) }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-soft">
            <p class="text-sm font-medium text-slate-500">Open Growth Opportunities</p>
            <p class="mt-3 text-4xl font-bold text-navy">{{ number_format($openGrowthOpportunities) }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-soft">
            <p class="text-sm font-medium text-slate-500">Pending Conversion Tasks</p>
            <p class="mt-3 text-4xl font-bold text-navy">{{ number_format($pendingConversionTasks) }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="rounded-lg border border-slate-200 bg-white shadow-soft xl:col-span-2">
            <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-navy">Latest SEO Checks</h2></div>
            <div class="divide-y divide-slate-100">
                @forelse ($latestAudits as $audit)
                    <div class="flex items-center justify-between gap-4 px-5 py-4">
                        <div>
                            <p class="font-semibold">{{ $audit->website->name }}</p>
                            <p class="text-sm text-slate-500">{{ $audit->page_title ?: 'No page title captured' }}</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $audit->is_indexable ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $audit->is_indexable ? 'Indexable' : 'Review' }}</span>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-slate-500">No SEO checks yet.</div>
                @endforelse
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white shadow-soft">
            <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-navy">Latest AI Insights</h2></div>
            <div class="divide-y divide-slate-100">
                @forelse ($latestInsights as $insight)
                    <div class="px-5 py-4">
                        <p class="font-semibold">{{ $insight->title }}</p>
                        <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-slate-500"><span>{{ $insight->website->name }}</span><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $insight->source === 'ai' ? 'bg-teal/10 text-teal' : 'bg-slate-100 text-slate-700' }}">{{ $insight->source === 'ai' ? 'AI' : 'Fallback' }}</span></p>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-slate-500">Generate an insight from a website audit.</div>
                @endforelse
            </div>
        </section>
    </div>

    <section class="mt-6 rounded-lg border border-slate-200 bg-white shadow-soft">
        <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-navy">Open Marketing Tasks</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Task</th><th class="px-5 py-3">Website</th><th class="px-5 py-3">Priority</th><th class="px-5 py-3">Status</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($openTasks as $task)
                    <tr><td class="px-5 py-4 font-semibold">{{ $task->title }}</td><td class="px-5 py-4">{{ $task->website->name }}</td><td class="px-5 py-4">{{ ucfirst($task->priority) }}</td><td class="px-5 py-4">{{ str_replace('_', ' ', ucfirst($task->status)) }}</td></tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">No open tasks.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.app>
