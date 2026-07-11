<section class="rounded-lg border border-slate-200 bg-white shadow-soft">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <h2 class="font-semibold text-navy">Top Pages</h2>
        @isset($website)<a href="{{ route('websites.gsc-pages.index', $website) }}" class="text-sm font-semibold text-teal">View all</a>@endisset
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Page</th><th class="px-5 py-3">Type</th><th class="px-5 py-3">Priority</th><th class="px-5 py-3">Clicks</th><th class="px-5 py-3">Impr.</th><th class="px-5 py-3">CTR</th><th class="px-5 py-3">Recommendation</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($pageRecommendations as $item)
                <tr><td class="max-w-xs break-all px-5 py-4 font-semibold">{{ $item['page']->page_url }}</td><td class="px-5 py-4">{{ str_replace('_', ' ', $item['page_type']) }}</td><td class="px-5 py-4">{{ $item['is_priority_service_page'] ? 'Priority service page' : 'Standard' }}</td><td class="px-5 py-4">{{ $item['page']->clicks }}</td><td class="px-5 py-4">{{ $item['page']->impressions }}</td><td class="px-5 py-4">{{ number_format($item['page']->ctr, 2) }}%</td><td class="px-5 py-4 text-slate-600">{{ $item['recommendation'] }}</td></tr>
            @empty
                <tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">No page data yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
