<section class="rounded-lg border border-slate-200 bg-white shadow-soft">
    <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-navy">Top Queries</h2></div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Query</th><th class="px-5 py-3">Intent</th><th class="px-5 py-3">Clicks</th><th class="px-5 py-3">Impr.</th><th class="px-5 py-3">CTR</th><th class="px-5 py-3">Pos.</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($queries as $query)
                <tr><td class="px-5 py-4 font-semibold">{{ $query->query }}</td><td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ str_replace('_', ' ', $queryIntents[$query->id] ?? 'unknown') }}</span></td><td class="px-5 py-4">{{ $query->clicks }}</td><td class="px-5 py-4">{{ $query->impressions }}</td><td class="px-5 py-4">{{ number_format($query->ctr, 2) }}%</td><td class="px-5 py-4">{{ number_format($query->position, 1) }}</td></tr>
            @empty
                <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">No query data yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
