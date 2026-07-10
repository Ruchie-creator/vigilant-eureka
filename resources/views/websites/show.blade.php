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

    <section class="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-soft">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="font-semibold text-navy">Google Search Console</h2>
                <p class="mt-1 text-sm text-slate-500">Direct public search performance sync. No patient medical data is collected.</p>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $website->searchConsoleSite ? 'bg-teal/10 text-teal' : 'bg-slate-100 text-slate-700' }}">{{ $website->searchConsoleSite ? 'Connected' : 'Not connected' }}</span>
        </div>

        <div class="mt-5 grid gap-4 md:grid-cols-4">
            <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs font-semibold uppercase text-slate-500">Clicks</p><p class="mt-2 text-2xl font-bold text-navy">{{ number_format((int) ($gscSummary->clicks ?? 0)) }}</p></div>
            <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs font-semibold uppercase text-slate-500">Impressions</p><p class="mt-2 text-2xl font-bold text-navy">{{ number_format((int) ($gscSummary->impressions ?? 0)) }}</p></div>
            <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs font-semibold uppercase text-slate-500">CTR</p><p class="mt-2 text-2xl font-bold text-navy">{{ number_format((float) ($gscSummary->ctr ?? 0), 2) }}%</p></div>
            <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs font-semibold uppercase text-slate-500">Position</p><p class="mt-2 text-2xl font-bold text-navy">{{ number_format((float) ($gscSummary->position ?? 0), 1) }}</p></div>
        </div>

        <div class="mt-5 grid gap-4 lg:grid-cols-2">
            <div>
                <p class="text-sm font-semibold text-slate-700">Selected property</p>
                <p class="mt-1 break-all text-sm text-slate-500">{{ $website->searchConsoleSite?->site_url ?? 'No property selected' }}</p>
                <p class="mt-1 text-sm text-slate-500">Last sync: {{ $website->gsc_last_synced_at?->diffForHumans() ?? 'Never' }}</p>
            </div>
            <div class="flex flex-wrap items-end gap-2 lg:justify-end">
                <a href="{{ route('google.search-console.connect') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold">Connect Google</a>
                @if($googleAccount)
                    <a href="{{ route('google.search-console.sites') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold">View Properties</a>
                @endif
                @if($website->searchConsoleSite)
                    <form method="POST" action="{{ route('websites.search-console.sync', $website) }}">@csrf<button class="rounded-lg bg-teal px-3 py-2 text-sm font-semibold text-white">Sync Search Data</button></form>
                @endif
            </div>
        </div>

        @if($googleAccount)
            <form method="POST" action="{{ route('websites.search-console.assign', $website) }}" class="mt-5 flex flex-wrap gap-2">
                @csrf
                <select name="search_console_site_id" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @foreach($googleAccount->sites as $site)
                        <option value="{{ $site->id }}" @selected($website->search_console_site_id === $site->id)>{{ $site->site_url }} · {{ $site->permission_level }}</option>
                    @endforeach
                </select>
                <button class="rounded-lg bg-navy px-4 py-2 text-sm font-semibold text-white">Select Property</button>
            </form>
        @endif
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        @include('websites.partials.audits', ['audits' => $website->seoAudits])
        @include('websites.partials.insights', ['insights' => $website->aiInsights])
    </div>
    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        @include('websites.partials.gsc-queries', ['queries' => $website->gscQueries])
        @include('websites.partials.gsc-pages', ['pages' => $website->gscPages])
    </div>
    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        @include('websites.partials.gsc-devices', ['devices' => $website->gscDevices])
        @include('websites.partials.growth-opportunities', ['opportunities' => $website->growthOpportunities])
    </div>
    <section class="mt-6 rounded-lg border border-slate-200 bg-white shadow-soft">
        <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-navy">Marketing Tasks</h2></div>
        <div class="divide-y divide-slate-100">@forelse($website->marketingTasks as $task)<div class="px-5 py-4"><p class="font-semibold">{{ $task->title }}</p><p class="text-sm text-slate-500">{{ ucfirst($task->priority) }} · {{ str_replace('_',' ', $task->status) }}</p></div>@empty<div class="px-5 py-10 text-center text-sm text-slate-500">No tasks yet.</div>@endforelse</div>
    </section>
</x-layouts.app>
