<section class="rounded-lg border border-slate-200 bg-white shadow-soft">
    <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-navy">Growth Opportunities</h2></div>
    <div class="divide-y divide-slate-100">
        @forelse($opportunities as $opportunity)
            <article class="px-5 py-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold">{{ str_replace('_', ' ', ucfirst($opportunity->opportunity_type)) }}</p>
                        <p class="mt-1 break-all text-sm text-slate-500">{{ ucfirst($opportunity->source_type) }}: {{ $opportunity->source_value }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $opportunity->priority === 'high' ? 'bg-rose-50 text-rose-700' : 'bg-teal/10 text-teal' }}">{{ ucfirst($opportunity->priority) }}</span>
                </div>
                <p class="mt-3 text-sm text-slate-700">{{ $opportunity->problem }}</p>
                <p class="mt-2 text-sm font-medium text-navy">{{ $opportunity->recommendation }}</p>
            </article>
        @empty
            <div class="px-5 py-10 text-center text-sm text-slate-500">No growth opportunities yet. Sync Search Console data to generate them.</div>
        @endforelse
    </div>
</section>
