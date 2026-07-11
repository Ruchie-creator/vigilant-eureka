<x-layouts.app :heading="$website->name">
    <section class="mb-6 rounded-lg border border-teal/20 bg-white p-5 shadow-soft">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <a href="{{ $website->url }}" target="_blank" rel="noopener" class="break-all text-sm font-semibold text-teal">{{ $website->url }}</a>
                <p class="mt-2 text-sm text-slate-600">No patient medical data is collected. This agent uses public website data, Search Console data, and conversion interaction planning data.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('websites.edit', $website) }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold">Edit</a>
                <form method="POST" action="{{ route('websites.search-console.sync', $website) }}">@csrf<button class="rounded-lg bg-teal px-4 py-2 text-sm font-semibold text-white" @disabled(! $website->searchConsoleSite || $propertyMismatch)>Sync Search Data</button></form>
            </div>
        </div>
    </section>

    @if($latestGscSync)
        <p class="mb-3 text-sm text-slate-600">Search Console data: {{ $latestGscSync->date_start->format('M j, Y') }} - {{ $latestGscSync->date_end->format('M j, Y') }} · {{ ucfirst($latestGscSync->search_type) }} search · {{ $latestGscSync->country_filter ?: 'All countries' }} · {{ $latestGscSync->device_filter ?: 'All devices' }} · Property: {{ $latestGscSync->property_url }}</p>
    @endif

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-6">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-soft"><p class="text-sm text-slate-500">Clicks @if($latestGscSync)<span class="block text-xs">Latest sync period</span>@endif</p><p class="mt-2 text-3xl font-bold text-navy">{{ number_format((int) ($gscSummary->clicks ?? 0)) }}</p></div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-soft"><p class="text-sm text-slate-500">Impressions @if($latestGscSync)<span class="block text-xs">Latest sync period</span>@endif</p><p class="mt-2 text-3xl font-bold text-navy">{{ number_format((int) ($gscSummary->impressions ?? 0)) }}</p></div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-soft"><p class="text-sm text-slate-500">CTR @if($latestGscSync)<span class="block text-xs">Latest sync period</span>@endif</p><p class="mt-2 text-3xl font-bold text-navy">{{ number_format((float) ($gscSummary->ctr ?? 0), 2) }}%</p></div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-soft"><p class="text-sm text-slate-500">Avg. Position @if($latestGscSync)<span class="block text-xs">Latest sync period</span>@endif</p><p class="mt-2 text-3xl font-bold text-navy">{{ number_format((float) ($gscSummary->position ?? 0), 1) }}</p></div>
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
                <p class="mt-1 text-sm text-slate-500">{{ $propertyMismatch ? 'Selected property needing review' : 'Connected property' }}: <span class="font-semibold text-slate-700">{{ $website->searchConsoleSite?->site_url ?? 'Not selected' }}</span></p>
                @if($latestGscSync)
                    <p class="mt-1 text-sm text-slate-500">Data period: {{ $latestGscSync->date_start->toDateString() }} to {{ $latestGscSync->date_end->toDateString() }}</p>
                    <p class="mt-1 text-sm text-slate-500">Filters: {{ ucfirst($latestGscSync->search_type) }} search · {{ $latestGscSync->country_filter ?: 'All countries' }} · {{ $latestGscSync->device_filter ?: 'All devices' }}</p>
                    <p class="mt-1 text-sm text-slate-500">Property used: {{ $latestGscSync->property_url }}</p>
                    <p class="mt-1 text-sm text-slate-500">Rows: {{ $latestGscSync->rows_daily }} daily · {{ $latestGscSync->rows_queries }} queries · {{ $latestGscSync->rows_pages }} pages · {{ $latestGscSync->rows_devices }} devices · {{ $latestGscSync->rows_countries }} countries</p>
                @endif
                <p class="mt-1 text-sm text-slate-500">Last sync: {{ $website->gsc_last_synced_at?->diffForHumans() ?? 'Never' }}</p>
                @if($availableCountries->isNotEmpty())
                    <p class="mt-1 text-sm text-slate-500">Available countries: {{ $availableCountries->implode(', ') }}</p>
                @endif
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $propertyMismatch ? 'bg-amber-100 text-amber-800' : ($website->searchConsoleSite ? 'bg-teal/10 text-teal' : 'bg-slate-100 text-slate-700') }}">{{ $propertyMismatch ? 'Mismatch' : ($website->searchConsoleSite ? 'Connected' : 'Not connected') }}</span>
        </div>
        @if($propertyMismatch)
            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">This Search Console property does not match the website URL.</div>
        @endif
        <div class="mt-5 flex flex-wrap gap-2">
            <a href="{{ route('google.search-console.connect') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold">Connect Google</a>
            <a href="{{ route('google.search-console.sites') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold">View Properties</a>
            @if($website->searchConsoleSite)<form method="POST" action="{{ route('websites.search-console.sync', $website) }}">@csrf<button class="rounded-lg bg-teal px-3 py-2 text-sm font-semibold text-white" @disabled($propertyMismatch)>Sync Search Data</button></form>@endif
            @if($website->gsc_last_synced_at)<form method="POST" action="{{ route('websites.search-console.reset', $website) }}" onsubmit="return confirm('Reset synced Search Console metrics and growth opportunities for this website? Existing tasks will be kept.');">@csrf<button class="rounded-lg border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-700">Reset GSC Data</button></form>@endif
        </div>
        @if($googleAccount)
            <form method="POST" action="{{ route('websites.search-console.assign', $website) }}" class="mt-5 flex flex-wrap gap-2">
                @csrf
                <select name="search_console_site_id" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">@foreach($googleAccount->sites as $site)<option value="{{ $site->id }}" @selected($website->search_console_site_id === $site->id)>{{ $site->site_url }} · {{ $site->permission_level }}</option>@endforeach</select>
                <button class="rounded-lg bg-navy px-4 py-2 text-sm font-semibold text-white">Select Property</button>
            </form>
        @endif
    </section>

    <section class="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-soft">
        <h2 class="font-semibold text-navy">Search Console Filters</h2>
        <form method="GET" action="{{ route('websites.show', $website) }}" class="mt-4 grid gap-3 md:grid-cols-3 xl:grid-cols-6">
            <label class="grid gap-1 text-sm font-semibold">Start date<input type="date" name="date_start" value="{{ $filters['date_start'] }}" class="rounded-lg border border-slate-300 px-3 py-2 font-normal"></label>
            <label class="grid gap-1 text-sm font-semibold">End date<input type="date" name="date_end" value="{{ $filters['date_end'] }}" class="rounded-lg border border-slate-300 px-3 py-2 font-normal"></label>
            <label class="grid gap-1 text-sm font-semibold">Country<select name="country" class="rounded-lg border border-slate-300 px-3 py-2 font-normal"><option value="">All countries</option>@foreach($availableCountries as $country)<option value="{{ $country }}" @selected($filters['country'] === $country)>{{ $country }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-sm font-semibold">Device<select name="device" class="rounded-lg border border-slate-300 px-3 py-2 font-normal"><option value="">All devices</option>@foreach(['desktop','mobile','tablet'] as $device)<option value="{{ $device }}" @selected($filters['device'] === $device)>{{ ucfirst($device) }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-sm font-semibold">Query intent<select name="query_intent" class="rounded-lg border border-slate-300 px-3 py-2 font-normal"><option value="">All intents</option>@foreach(['service_intent','local_service_intent','condition_intent','branded_practitioner','review_reputation','informational','competitor','irrelevant','unknown'] as $intent)<option value="{{ $intent }}" @selected($filters['query_intent'] === $intent)>{{ str_replace('_', ' ', ucfirst($intent)) }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-sm font-semibold">Page type<select name="page_type" class="rounded-lg border border-slate-300 px-3 py-2 font-normal"><option value="">All page types</option>@foreach(['homepage','service_page','blog','legal','unknown'] as $type)<option value="{{ $type }}" @selected($filters['page_type'] === $type)>{{ str_replace('_', ' ', ucfirst($type)) }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-sm font-semibold">Priority<select name="opportunity_priority" class="rounded-lg border border-slate-300 px-3 py-2 font-normal"><option value="">All priorities</option>@foreach(['high','medium','low'] as $priority)<option value="{{ $priority }}" @selected($filters['opportunity_priority'] === $priority)>{{ ucfirst($priority) }}</option>@endforeach</select></label>
            <div class="flex items-end gap-2 md:col-span-2 xl:col-span-5">
                <button class="rounded-lg bg-navy px-4 py-2 text-sm font-semibold text-white">Apply Filters</button>
                <a href="{{ route('websites.show', $website) }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold">Clear</a>
            </div>
        </form>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        @include('websites.partials.growth-opportunities', ['opportunities' => $serviceOpportunities, 'title' => 'Service Growth Opportunities'])
        @include('websites.partials.growth-opportunities', ['opportunities' => $brandedOpportunities, 'title' => 'Branded & Reputation Searches'])
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        @include('websites.partials.growth-opportunities', ['opportunities' => $website->relationLoaded('growthOpportunities') ? $website->growthOpportunities : collect(), 'title' => 'General Growth Opportunities'])
        @include('websites.partials.conversion-checks', ['checks' => $website->relationLoaded('conversionChecks') ? $website->conversionChecks : collect()])
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        @include('websites.partials.gsc-pages', ['pageRecommendations' => $pageRecommendations])
        @include('websites.partials.gsc-queries', ['queries' => $filteredQueries, 'queryIntents' => $queryIntents])
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        @include('websites.partials.gsc-devices', ['devices' => $website->relationLoaded('gscDevices') ? $website->gscDevices : collect()])
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
