<section class="rounded-lg border border-slate-200 bg-white shadow-soft">
    <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-navy">AI Insights</h2></div>
    <div class="divide-y divide-slate-100">@forelse($insights as $insight)<div class="px-5 py-4"><p class="font-semibold">{{ $insight->title }}</p><p class="mt-1 text-sm text-slate-500">{{ ucfirst($insight->priority) }} · {{ str_replace('_',' ', $insight->category) }}</p><p class="mt-2 text-sm text-slate-600">{{ $insight->summary }}</p></div>@empty<div class="px-5 py-10 text-center text-sm text-slate-500">No insights yet.</div>@endforelse</div>
</section>
