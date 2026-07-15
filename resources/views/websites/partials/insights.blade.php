<section class="rounded-lg bg-white shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)]">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
        <div>
            <h2 class="font-semibold text-navy">AI Insights</h2>
            <p class="mt-1 text-sm text-slate-500">Top 3 unique insights tied to Search Console evidence.</p>
        </div>
        @isset($website)<a href="{{ route('websites.ai-insights.index', $website) }}" class="inline-flex min-h-10 items-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-teal shadow-[0_0_0_1px_rgba(1,101,118,0.18)] transition-transform active:scale-[0.96]">View all</a>@endisset
    </div>
    <div class="grid gap-4 p-5">
        @forelse($insights as $insight)
            @include('ai-insights.partials.card', ['insight' => $insight, 'compact' => true])
        @empty
            <div class="px-5 py-10 text-center text-sm text-slate-500">No insights yet.</div>
        @endforelse
    </div>
</section>
