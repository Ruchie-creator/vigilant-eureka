<section class="rounded-lg bg-white shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)]">
    <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-4">
        <div>
            <h2 class="font-semibold text-navy">Countries</h2>
            <p class="text-sm text-slate-500">Target country: {{ $targetCountry }}</p>
        </div>
        @isset($website)
            <a href="{{ route('websites.gsc-countries.index', $website) }}" class="shrink-0 text-sm font-semibold text-teal">View all</a>
        @endisset
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[820px] text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Country</th><th class="px-5 py-3">Clicks</th><th class="px-5 py-3">Impressions</th><th class="px-5 py-3">CTR</th><th class="px-5 py-3">Position</th><th class="px-5 py-3">Target</th><th class="px-5 py-3">Recommendation</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($countries as $row)
                <tr>
                    <td class="px-5 py-4 font-semibold">{{ $row['country'] }}</td>
                    <td class="px-5 py-4 tabular-nums">{{ number_format($row['clicks']) }}</td>
                    <td class="px-5 py-4 tabular-nums">{{ number_format($row['impressions']) }}</td>
                    <td class="px-5 py-4 tabular-nums">{{ number_format($row['ctr'], 2) }}%</td>
                    <td class="px-5 py-4 tabular-nums">{{ number_format($row['position'], 1) }}</td>
                    <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $row['is_target'] ? 'bg-teal/10 text-teal' : 'bg-slate-100 text-slate-600' }}">{{ $row['is_target'] ? 'Yes' : 'No' }}</span></td>
                    <td class="px-5 py-4 text-slate-600">{{ $row['recommendation'] }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">No country data yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
