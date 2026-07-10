<section class="rounded-lg border border-slate-200 bg-white shadow-soft">
    <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-navy">Top Pages</h2></div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Page</th><th class="px-5 py-3">Clicks</th><th class="px-5 py-3">Impressions</th><th class="px-5 py-3">CTR</th><th class="px-5 py-3">Pos.</th></tr></thead>
            <tbody class="divide-y divide-slate-100">@forelse($pages as $page)<tr><td class="max-w-xs break-all px-5 py-4 font-semibold">{{ $page->page_url }}</td><td class="px-5 py-4">{{ $page->clicks }}</td><td class="px-5 py-4">{{ $page->impressions }}</td><td class="px-5 py-4">{{ number_format($page->ctr, 2) }}%</td><td class="px-5 py-4">{{ number_format($page->position, 1) }}</td></tr>@empty<tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">No page data yet.</td></tr>@endforelse</tbody>
        </table>
    </div>
</section>
