<section class="rounded-lg bg-white shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)]">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <h2 class="font-semibold text-navy">Top Queries</h2>
        @isset($website)<a href="{{ route('websites.gsc-queries.index', $website) }}" class="text-sm font-semibold text-teal">View all</a>@endisset
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[1160px] text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Query</th><th class="px-5 py-3">Intent</th><th class="px-5 py-3">Category</th><th class="px-5 py-3">Clicks</th><th class="px-5 py-3">Impressions</th><th class="px-5 py-3">CTR</th><th class="px-5 py-3">Position</th><th class="px-5 py-3">Related page</th><th class="px-5 py-3">Recommendation</th><th class="px-5 py-3">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($queryRows ?? collect() as $row)
                @php($query = $row['query'])
                <tr><td class="px-5 py-4 font-semibold">{{ $query->query }}</td><td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ str_replace('_', ' ', $row['intent']) }}</span></td><td class="px-5 py-4"><span class="rounded-full bg-teal/10 px-2.5 py-1 text-xs font-semibold text-teal">{{ str_replace('_', ' ', $row['category']) }}</span></td><td class="px-5 py-4 tabular-nums">{{ number_format($query->clicks) }}</td><td class="px-5 py-4 tabular-nums">{{ number_format($query->impressions) }}</td><td class="px-5 py-4 tabular-nums">{{ number_format($query->ctr, 2) }}%</td><td class="px-5 py-4 tabular-nums">{{ number_format($query->position, 1) }}</td><td class="max-w-xs break-all px-5 py-4 text-slate-600">{{ $row['related_page'] ?: 'Not mapped' }}</td><td class="px-5 py-4 text-slate-600">{{ $row['recommendation'] }}</td><td class="px-5 py-4">@if($row['related_page'])<a href="{{ $row['related_page'] }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center rounded-lg bg-white px-3 py-2 text-xs font-semibold text-teal shadow-[0_0_0_1px_rgba(1,101,118,0.18)]">Open</a>@else<span class="text-xs text-slate-400">Review</span>@endif</td></tr>
            @empty
                <tr><td colspan="10" class="px-5 py-10 text-center text-slate-500">No query data yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
