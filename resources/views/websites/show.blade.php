<x-layouts.app :heading="$website->name">
    <div class="mb-5 flex flex-wrap gap-2">
        <a href="{{ route('websites.edit', $website) }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold">Edit</a>
        <form method="POST" action="{{ route('websites.audit', $website) }}">@csrf<button class="rounded-lg bg-navy px-4 py-2 text-sm font-semibold text-white">Run SEO Scan</button></form>
        <form method="POST" action="{{ route('websites.ai-insights.store', $website) }}">@csrf<button class="rounded-lg bg-teal px-4 py-2 text-sm font-semibold text-white">Generate AI Insight</button></form>
    </div>
    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-soft">
        <a href="{{ $website->url }}" target="_blank" rel="noopener" class="break-all text-teal">{{ $website->url }}</a>
        <div class="mt-4 grid gap-4 text-sm md:grid-cols-4"><div><span class="text-slate-500">Type</span><p class="font-semibold">{{ ucfirst($website->type) }}</p></div><div><span class="text-slate-500">Language</span><p class="font-semibold">{{ $website->language }}</p></div><div><span class="text-slate-500">Location</span><p class="font-semibold">{{ $website->target_location ?: 'Not set' }}</p></div><div><span class="text-slate-500">Status</span><p class="font-semibold">{{ ucfirst($website->status) }}</p></div></div>
    </section>
    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        @include('websites.partials.audits', ['audits' => $website->seoAudits])
        @include('websites.partials.insights', ['insights' => $website->aiInsights])
    </div>
    <section class="mt-6 rounded-lg border border-slate-200 bg-white shadow-soft">
        <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-navy">Marketing Tasks</h2></div>
        <div class="divide-y divide-slate-100">@forelse($website->marketingTasks as $task)<div class="px-5 py-4"><p class="font-semibold">{{ $task->title }}</p><p class="text-sm text-slate-500">{{ ucfirst($task->priority) }} · {{ str_replace('_',' ', $task->status) }}</p></div>@empty<div class="px-5 py-10 text-center text-sm text-slate-500">No tasks yet.</div>@endforelse</div>
    </section>
</x-layouts.app>
