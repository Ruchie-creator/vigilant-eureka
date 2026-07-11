<section class="rounded-lg bg-white shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)]">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
        <div>
            <h2 class="font-semibold text-navy">{{ $title ?? 'Top Growth Opportunities' }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ ($isFullView ?? false) ? 'Filtered opportunity backlog with task actions and source data.' : 'Top 5 shown here. Open the full view to filter and manage the backlog.' }}</p>
        </div>
        @if(! ($isFullView ?? false))
            @isset($website)<a href="{{ route('websites.growth-opportunities.index', $website) }}" class="inline-flex min-h-10 items-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-teal shadow-[0_0_0_1px_rgba(1,101,118,0.18)] transition-transform active:scale-[0.96]">View all</a>@endisset
        @endif
    </div>
    <div class="grid gap-4 p-5">
        @forelse($opportunities as $opportunity)
            <article class="rounded-lg bg-slate-50 p-4 shadow-[0_0_0_1px_rgba(5,18,55,0.06)]">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-teal/10 px-3 py-1 text-xs font-semibold text-teal">{{ str_replace('_', ' ', ucfirst($opportunity->opportunity_type)) }}</span>
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 shadow-[0_0_0_1px_rgba(5,18,55,0.08)]">{{ str_replace('_', ' ', ucfirst($opportunity->opportunity_category ?? 'conversion_improvement')) }}</span>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $opportunity->priority === 'high' ? 'bg-rose-50 text-rose-700' : ($opportunity->priority === 'medium' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-700') }}">{{ ucfirst($opportunity->priority) }}</span>
                        </div>
                        <h3 class="mt-3 text-balance text-lg font-semibold text-navy">{{ $opportunity->problem ?: 'Growth opportunity detected' }}</h3>
                        <p class="mt-2 break-all text-sm text-slate-500">{{ ucfirst(str_replace('_', ' ', $opportunity->source_type)) }}: <span class="font-medium text-slate-700">{{ $opportunity->source_value }}</span></p>
                    </div>
                    <div class="rounded-lg bg-white px-4 py-3 text-center shadow-[0_0_0_1px_rgba(5,18,55,0.08)]">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Score</p>
                        <p class="mt-1 text-2xl font-bold tabular-nums text-navy">{{ $opportunity->score }}</p>
                    </div>
                </div>

                @if($opportunity->related_page_url)<p class="mt-3 break-all text-sm text-slate-500">Related page: <span class="font-medium text-slate-700">{{ $opportunity->related_page_url }}</span></p>@endif

                <div class="mt-4 grid gap-3 lg:grid-cols-3">
                    <div class="rounded-lg bg-white p-4 shadow-[0_0_0_1px_rgba(5,18,55,0.06)]">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Recommendation</p>
                        <p class="mt-2 text-sm font-medium leading-6 text-navy">{{ $opportunity->recommendation ?: 'Review this opportunity and define the next conversion action.' }}</p>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow-[0_0_0_1px_rgba(5,18,55,0.06)]">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Expected result</p>
                        <p class="mt-2 text-sm leading-6 text-slate-700">{{ $opportunity->expected_result ?: 'Better search visibility and a clearer appointment path.' }}</p>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow-[0_0_0_1px_rgba(5,18,55,0.06)]">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Conversion impact</p>
                        <p class="mt-2 text-sm leading-6 text-slate-700">{{ $opportunity->conversion_action ?: 'Improve the path from qualified visit to appointment action.' }}</p>
                    </div>
                </div>

                <details class="mt-4 rounded-lg bg-white shadow-[0_0_0_1px_rgba(5,18,55,0.06)]">
                    <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-navy">Why this matters: data used</summary>
                    <dl class="grid gap-3 border-t border-slate-100 p-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                        <div><dt class="text-xs font-semibold uppercase text-slate-500">Page</dt><dd class="mt-1 break-all text-slate-700">{{ $opportunity->related_page_url ?: 'Not mapped' }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-slate-500">Date range</dt><dd class="mt-1 text-slate-700">{{ $opportunity->date_start?->format('M j, Y') ?: 'n/a' }} - {{ $opportunity->date_end?->format('M j, Y') ?: 'n/a' }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-slate-500">Clicks</dt><dd class="mt-1 tabular-nums text-slate-700">{{ number_format($opportunity->clicks) }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-slate-500">Impressions</dt><dd class="mt-1 tabular-nums text-slate-700">{{ number_format($opportunity->impressions) }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-slate-500">CTR</dt><dd class="mt-1 tabular-nums text-slate-700">{{ number_format($opportunity->ctr, 2) }}%</dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-slate-500">Position</dt><dd class="mt-1 tabular-nums text-slate-700">{{ number_format($opportunity->position, 1) }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-slate-500">Device</dt><dd class="mt-1 text-slate-700">All devices</dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-slate-500">Country</dt><dd class="mt-1 text-slate-700">All countries</dd></div>
                    </dl>
                </details>

                <div class="mt-4 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('growth-opportunities.tasks.store', $opportunity) }}">@csrf<button class="min-h-10 rounded-lg bg-teal px-3 py-2 text-sm font-semibold text-white transition-transform active:scale-[0.96]">Create Task</button></form>
                    <form method="POST" action="{{ route('growth-opportunities.update', $opportunity) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="ignored"><button class="min-h-10 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-[0_0_0_1px_rgba(5,18,55,0.12)] transition-transform active:scale-[0.96]">Ignore</button></form>
                    <form method="POST" action="{{ route('growth-opportunities.update', $opportunity) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="completed"><button class="min-h-10 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-[0_0_0_1px_rgba(5,18,55,0.12)] transition-transform active:scale-[0.96]">Mark Completed</button></form>
                </div>
            </article>
        @empty
            <div class="px-5 py-10 text-center text-sm text-slate-500">{{ $empty ?? 'No growth opportunities yet. Sync Search Console data to generate them.' }}</div>
        @endforelse
    </div>
</section>
