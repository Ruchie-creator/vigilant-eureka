<x-layouts.app :heading="$website->name">
    <section class="mb-6 rounded-lg border border-teal/20 bg-white p-5 shadow-soft">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <a href="{{ $website->url }}" target="_blank" rel="noopener" class="break-all text-sm font-semibold text-teal">{{ $website->url }}</a>
                <p class="mt-2 text-sm text-slate-600">No patient medical data is collected. This agent uses public website data, Search Console data, and conversion interaction planning data.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('websites.edit', $website) }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold">Edit</a>
                <form method="POST" action="{{ route('websites.search-console.sync', $website) }}">@csrf<button class="rounded-lg bg-teal px-4 py-2 text-sm font-semibold text-white" @disabled(! $website->searchConsoleSite)>Sync Search Data</button></form>
            </div>
        </div>
    </section>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-6">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-soft"><p class="text-sm text-slate-500">Clicks</p><p class="mt-2 text-3xl font-bold text-navy">{{ number_format((int) ($gscSummary->clicks ?? 0)) }}</p></div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-soft"><p class="text-sm text-slate-500">Impressions</p><p class="mt-2 text-3xl font-bold text-navy">{{ number_format((int) ($gscSummary->impressions ?? 0)) }}</p></div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-soft"><p class="text-sm text-slate-500">CTR</p><p class="mt-2 text-3xl font-bold text-navy">{{ number_format((float) ($gscSummary->ctr ?? 0), 2) }}%</p></div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-soft"><p class="text-sm text-slate-500">Avg. Position</p><p class="mt-2 text-3xl font-bold text-navy">{{ number_format((float) ($gscSummary->position ?? 0), 1) }}</p></div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-soft"><p class="text-sm text-slate-500">Mobile Clicks</p><p class="mt-2 text-3xl font-bold text-navy">{{ number_format($mobileClicks) }}</p></div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-soft"><p class="text-sm text-slate-500">Open Conversion Opps</p><p class="mt-2 text-3xl font-bold text-navy">{{ number_format($openConversionOpportunities) }}</p></div>
    </div>

    <section class="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-soft">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="font-semibold text-navy">Conversion Priority</h2>
                <p class="mt-1 text-sm text-slate-500">One clear next action for this website.</p>
            </div>
            @if($topPriority)<span class="rounded-full bg-teal/10 px-3 py-1 text-xs font-semibold text-teal">Score {{ $topPriority->score }}</span>@endif
        </div>
        @if($topPriority)
            <p class="mt-4 text-sm leading-6 text-slate-700"><span class="font-semibold text-navy">Top priority:</span> {{ $topPriority->recommendation }}</p>
            @if($topPriority->related_page_url)<p class="mt-2 break-all text-sm text-slate-500">Page: {{ $topPriority->related_page_url }}</p>@endif
        @else
            <p class="mt-4 text-sm text-slate-500">Sync Search Console data to generate a clear conversion priority.</p>
        @endif
    </section>

    <section class="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-soft">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="font-semibold text-navy">Google Search Console</h2>
                <p class="mt-1 text-sm text-slate-500">Connected property: <span class="font-semibold text-slate-700">{{ $website->searchConsoleSite?->site_url ?? 'Not selected' }}</span></p>
                <p class="mt-1 text-sm text-slate-500">Last sync: {{ $website->gsc_last_synced_at?->diffForHumans() ?? 'Never' }}</p>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $website->searchConsoleSite ? 'bg-teal/10 text-teal' : 'bg-slate-100 text-slate-700' }}">{{ $website->searchConsoleSite ? 'Connected' : 'Not connected' }}</span>
        </div>
        <div class="mt-5 flex flex-wrap gap-2">
            <a href="{{ route('google.search-console.connect') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold">Connect Google</a>
            <a href="{{ route('google.search-console.sites') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold">View Properties</a>
            @if($website->searchConsoleSite)<form method="POST" action="{{ route('websites.search-console.sync', $website) }}">@csrf<button class="rounded-lg bg-teal px-3 py-2 text-sm font-semibold text-white">Sync Search Data</button></form>@endif
        </div>
        @if($googleAccount)
            <form method="POST" action="{{ route('websites.search-console.assign', $website) }}" class="mt-5 flex flex-wrap gap-2">
                @csrf
                <select name="search_console_site_id" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">@foreach($googleAccount->sites as $site)<option value="{{ $site->id }}" @selected($website->search_console_site_id === $site->id)>{{ $site->site_url }} · {{ $site->permission_level }}</option>@endforeach</select>
                <button class="rounded-lg bg-navy px-4 py-2 text-sm font-semibold text-white">Select Property</button>
            </form>
        @endif
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        @include('websites.partials.growth-opportunities', ['opportunities' => $website->growthOpportunities])
        @include('websites.partials.conversion-checks', ['checks' => $website->conversionChecks])
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        @include('websites.partials.gsc-pages', ['pageRecommendations' => $pageRecommendations])
        @include('websites.partials.gsc-queries', ['queries' => $website->gscQueries, 'queryIntents' => $queryIntents])
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        @include('websites.partials.gsc-devices', ['devices' => $website->gscDevices])
        @include('websites.partials.insights', ['insights' => $website->aiInsights])
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        @include('websites.partials.audits', ['audits' => $website->seoAudits])
        <section class="rounded-lg border border-slate-200 bg-white shadow-soft">
            <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-navy">Marketing Tasks</h2></div>
            <div class="divide-y divide-slate-100">@forelse($website->marketingTasks as $task)<div class="px-5 py-4"><p class="font-semibold">{{ $task->title }}</p><p class="text-sm text-slate-500">{{ ucfirst($task->priority) }} · {{ str_replace('_',' ', $task->status) }}</p></div>@empty<div class="px-5 py-10 text-center text-sm text-slate-500">No tasks yet.</div>@endforelse</div>
        </section>
    </div>
</x-layouts.app>
