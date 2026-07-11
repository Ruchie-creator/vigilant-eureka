<section class="rounded-lg bg-white shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)]">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <h2 class="font-semibold text-navy">Top Pages</h2>
        @isset($website)<a href="{{ route('websites.gsc-pages.index', $website) }}" class="text-sm font-semibold text-teal">View all</a>@endisset
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[1180px] text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Page URL</th><th class="px-5 py-3">Page type</th><th class="px-5 py-3">Priority service</th><th class="px-5 py-3">Clicks</th><th class="px-5 py-3">Impressions</th><th class="px-5 py-3">CTR</th><th class="px-5 py-3">Position</th><th class="px-5 py-3">Top country</th><th class="px-5 py-3">Top device</th><th class="px-5 py-3">Conversion recommendation</th><th class="px-5 py-3">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($pageRecommendations as $item)
                <tr><td class="max-w-xs break-all px-5 py-4 font-semibold">{{ $item['page']->page_url }}</td><td class="px-5 py-4">{{ str_replace('_', ' ', $item['page_type']) }}</td><td class="px-5 py-4">{{ $item['is_priority_service_page'] ? 'Yes' : 'No' }}</td><td class="px-5 py-4 tabular-nums">{{ number_format($item['page']->clicks) }}</td><td class="px-5 py-4 tabular-nums">{{ number_format($item['page']->impressions) }}</td><td class="px-5 py-4 tabular-nums">{{ number_format($item['page']->ctr, 2) }}%</td><td class="px-5 py-4 tabular-nums">{{ number_format($item['page']->position, 1) }}</td><td class="px-5 py-4">{{ $item['top_country'] ?: 'None' }}</td><td class="px-5 py-4">{{ $item['top_device'] ? ucfirst($item['top_device']) : 'None' }}</td><td class="px-5 py-4 text-slate-600">{{ $item['recommendation'] }}</td><td class="px-5 py-4"><a href="{{ $item['page']->page_url }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center rounded-lg bg-white px-3 py-2 text-xs font-semibold text-teal shadow-[0_0_0_1px_rgba(1,101,118,0.18)]">Open</a></td></tr>
            @empty
                <tr><td colspan="11" class="px-5 py-10 text-center text-slate-500">No page data yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
