<x-layouts.app heading="AI Website Growth & Conversion Agent">
    <section class="mb-6 rounded-lg border border-teal/20 bg-white p-5 shadow-soft">
        <p class="text-sm leading-6 text-slate-700">Tracks search visibility, finds growth opportunities, and recommends actions to increase appointment conversions. No patient medical data is collected. The agent only uses public website data, Search Console data, and conversion interaction data.</p>
    </section>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-soft"><p class="text-sm font-medium text-slate-500">Connected Websites</p><p class="mt-3 text-4xl font-bold text-navy">{{ number_format($connectedWebsiteCount) }}</p><p class="mt-1 text-sm text-slate-500">of {{ number_format($websiteCount) }} total</p></div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-soft"><p class="text-sm font-medium text-slate-500">Total Clicks</p><p class="mt-3 text-4xl font-bold text-navy">{{ number_format($totalClicks) }}</p></div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-soft"><p class="text-sm font-medium text-slate-500">Total Impressions</p><p class="mt-3 text-4xl font-bold text-navy">{{ number_format($totalImpressions) }}</p></div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-soft"><p class="text-sm font-medium text-slate-500">Average CTR</p><p class="mt-3 text-4xl font-bold text-navy">{{ number_format($averageCtr, 2) }}%</p></div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-soft"><p class="text-sm font-medium text-slate-500">Average Position</p><p class="mt-3 text-4xl font-bold text-navy">{{ number_format($averagePosition, 1) }}</p></div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-soft"><p class="text-sm font-medium text-slate-500">Open Growth Opportunities</p><p class="mt-3 text-4xl font-bold text-navy">{{ number_format($openGrowthOpportunities) }}</p></div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-soft"><p class="text-sm font-medium text-slate-500">Pending Conversion Tasks</p><p class="mt-3 text-4xl font-bold text-navy">{{ number_format($pendingConversionTasks) }}</p></div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-soft"><p class="text-sm font-medium text-slate-500">Weekly Report</p><p class="mt-3 text-lg font-bold text-navy">{{ $latestReport?->status ?? 'Not generated' }}</p></div>
    </div>

    <section class="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-soft">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="font-semibold text-navy">Top Conversion Priority</h2>
                <p class="mt-1 text-sm text-slate-500">The highest-value action across connected websites.</p>
            </div>
            @if($topConversionPriority)<span class="rounded-full bg-teal/10 px-3 py-1 text-xs font-semibold text-teal">Score {{ $topConversionPriority->score }}</span>@endif
        </div>
        @if($topConversionPriority)
            <p class="mt-4 text-sm text-slate-700"><span class="font-semibold text-navy">{{ $topConversionPriority->website->name }}:</span> {{ $topConversionPriority->problem }}</p>
            <p class="mt-2 text-sm font-medium text-navy">{{ $topConversionPriority->recommendation }}</p>
            @if($topConversionPriority->related_page_url)<p class="mt-2 break-all text-sm text-slate-500">Page: {{ $topConversionPriority->related_page_url }}</p>@endif
        @else
            <p class="mt-4 text-sm text-slate-500">Sync Search Console data to generate conversion priorities.</p>
        @endif
    </section>

    <section class="mt-6 rounded-lg border border-slate-200 bg-white shadow-soft">
        <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-navy">Websites Needing Attention</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Website</th><th class="px-5 py-3">Clicks</th><th class="px-5 py-3">Impressions</th><th class="px-5 py-3">CTR</th><th class="px-5 py-3">Position</th><th class="px-5 py-3">Top Opportunity</th><th class="px-5 py-3">Tasks</th><th class="px-5 py-3">Last Sync</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($websiteRows as $row)
                    <tr>
                        <td class="px-5 py-4 font-semibold"><a href="{{ route('websites.show', $row['website']) }}" class="text-teal">{{ $row['website']->name }}</a></td>
                        <td class="px-5 py-4">{{ number_format($row['clicks']) }}</td>
                        <td class="px-5 py-4">{{ number_format($row['impressions']) }}</td>
                        <td class="px-5 py-4">{{ number_format($row['ctr'], 2) }}%</td>
                        <td class="px-5 py-4">{{ number_format($row['position'], 1) }}</td>
                        <td class="px-5 py-4">{{ $row['top_opportunity']?->opportunity_type ? str_replace('_', ' ', $row['top_opportunity']->opportunity_type) : 'None yet' }}</td>
                        <td class="px-5 py-4">{{ $row['pending_tasks'] }}</td>
                        <td class="px-5 py-4 text-slate-500">{{ $row['website']->gsc_last_synced_at?->diffForHumans() ?? 'Never' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-5 py-10 text-center text-slate-500">No websites yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.app>
