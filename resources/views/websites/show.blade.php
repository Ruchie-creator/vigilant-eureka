<x-layouts.app :heading="$website->name">
    @php
        $connectionLabel = $propertyMismatch ? 'Property mismatch' : ($website->searchConsoleSite ? 'Connected' : 'Not connected');
        $connectionClass = $propertyMismatch ? 'bg-amber-100 text-amber-800' : ($website->searchConsoleSite ? 'bg-teal/10 text-teal' : 'bg-slate-100 text-slate-700');
        $syncStatus = $latestGscSync?->status ?? 'No sync yet';
        $dateRangeLabel = $latestGscSync ? $latestGscSync->date_start->format('M j, Y').' - '.$latestGscSync->date_end->format('M j, Y') : 'No synced date range';
        $summaryCards = [
            ['label' => 'Clicks', 'value' => number_format((int) ($gscSummary->clicks ?? 0)), 'note' => $dateRangeLabel],
            ['label' => 'Impressions', 'value' => number_format((int) ($gscSummary->impressions ?? 0)), 'note' => $dateRangeLabel],
            ['label' => 'CTR', 'value' => number_format((float) ($gscSummary->ctr ?? 0), 2).'%', 'note' => $dateRangeLabel],
            ['label' => 'Position', 'value' => number_format((float) ($gscSummary->position ?? 0), 1), 'note' => $dateRangeLabel],
            ['label' => 'Mobile Clicks', 'value' => number_format($mobileClicks), 'note' => 'Mobile search traffic'],
            ['label' => 'Top Country', 'value' => $topCountry?->country ?? 'None', 'note' => $topCountry ? number_format($topCountry->clicks).' clicks' : 'No country data'],
            ['label' => 'Top Device', 'value' => $topDevice?->device ? ucfirst($topDevice->device) : 'None', 'note' => $topDevice ? number_format($topDevice->clicks).' clicks' : 'No device data'],
            ['label' => 'Service-page Clicks', 'value' => number_format($servicePageClicks), 'note' => 'Service landing pages'],
            ['label' => 'Branded Clicks', 'value' => number_format($brandedClicks), 'note' => 'Practitioner and reputation queries'],
            ['label' => 'Open Conversion Opps', 'value' => number_format($openConversionOpportunities), 'note' => 'Appointment-focused backlog'],
        ];
    @endphp

    <section class="overflow-hidden rounded-lg bg-navy text-white shadow-soft">
        <div class="grid gap-6 p-6 lg:grid-cols-[1fr_360px] lg:p-7">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-lg bg-white/10 px-3 py-1 text-xs font-semibold">{{ ucfirst($website->type) }}</span>
                    <span class="rounded-lg bg-white/10 px-3 py-1 text-xs font-semibold">{{ strtoupper($website->language) }}</span>
                    <span class="rounded-lg px-3 py-1 text-xs font-semibold {{ $connectionClass }}">{{ $connectionLabel }}</span>
                </div>
                <h1 class="mt-4 text-balance text-3xl font-bold tracking-tight md:text-4xl">{{ $website->name }}</h1>
                <a href="{{ $website->url }}" target="_blank" rel="noopener" class="mt-2 inline-block break-all text-sm font-semibold text-teal-200">{{ $website->url }}</a>
                <p class="mt-4 max-w-2xl text-pretty text-sm leading-6 text-slate-200">No patient medical data is collected. This agent uses public website data, Search Console data, and conversion interaction planning data.</p>
                <dl class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-lg bg-white/10 p-3">
                        <dt class="text-xs uppercase tracking-wide text-slate-300">Target location</dt>
                        <dd class="mt-1 font-semibold">{{ $website->target_location ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg bg-white/10 p-3">
                        <dt class="text-xs uppercase tracking-wide text-slate-300">Target country</dt>
                        <dd class="mt-1 font-semibold">{{ $targetCountry }}</dd>
                    </div>
                    <div class="rounded-lg bg-white/10 p-3 sm:col-span-2">
                        <dt class="text-xs uppercase tracking-wide text-slate-300">Selected Search Console property</dt>
                        <dd class="mt-1 break-all font-semibold">{{ $website->searchConsoleSite?->site_url ?? 'Not selected' }}</dd>
                    </div>
                    <div class="rounded-lg bg-white/10 p-3">
                        <dt class="text-xs uppercase tracking-wide text-slate-300">Last sync</dt>
                        <dd class="mt-1 font-semibold">{{ $website->gsc_last_synced_at?->diffForHumans() ?? 'Never' }}</dd>
                    </div>
                </dl>
            </div>
            <div class="rounded-lg bg-white p-4 text-navy shadow-[0_0_0_1px_rgba(255,255,255,0.08)]">
                <p class="text-sm font-semibold text-slate-500">Actions</p>
                <div class="mt-4 grid gap-2">
                    <form method="POST" action="{{ route('websites.search-console.sync', $website) }}">@csrf<button class="min-h-10 w-full rounded-lg bg-teal px-4 py-2 text-sm font-semibold text-white transition-transform active:scale-[0.96]" @disabled(! $website->searchConsoleSite || $propertyMismatch)>Sync Search Data</button></form>
                    <form method="POST" action="{{ route('websites.ai-insights.store', $website) }}">@csrf<button class="min-h-10 w-full rounded-lg bg-navy px-4 py-2 text-sm font-semibold text-white transition-transform active:scale-[0.96]">Generate AI Insight</button></form>
                    <form method="POST" action="{{ route('websites.audit', $website) }}">@csrf<button class="min-h-10 w-full rounded-lg bg-white px-4 py-2 text-sm font-semibold text-navy shadow-[0_0_0_1px_rgba(5,18,55,0.14)] transition-transform active:scale-[0.96]">Run SEO Scan</button></form>
                    <a href="{{ route('settings') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-navy shadow-[0_0_0_1px_rgba(5,18,55,0.14)] transition-transform active:scale-[0.96]">Manage GSC Settings</a>
                    @if($website->gsc_last_synced_at)<form method="POST" action="{{ route('websites.search-console.reset', $website) }}" onsubmit="return confirm('Reset synced Search Console metrics and growth opportunities for this website? Existing tasks will be kept.');">@csrf<button class="min-h-10 w-full rounded-lg bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 shadow-[0_0_0_1px_rgba(225,29,72,0.16)] transition-transform active:scale-[0.96]">Reset Analysis</button></form>@endif
                </div>
            </div>
        </div>
    </section>

    <section class="mt-6 rounded-lg bg-white p-5 shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)]">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-navy">Search Console Context</h2>
                <p class="text-sm text-slate-500">Current synced data provenance and fetch status.</p>
            </div>
            <span class="rounded-lg px-3 py-1 text-xs font-semibold {{ $syncStatus === 'success' ? 'bg-emerald-50 text-emerald-700' : ($syncStatus === 'failed' ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-700') }}">{{ ucfirst($syncStatus) }}</span>
        </div>
        @if($propertyMismatch)
            <div class="mt-4 rounded-lg bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800 shadow-[0_0_0_1px_rgba(245,158,11,0.2)]">This Search Console property does not match the website URL.</div>
        @endif
        <dl class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Property used</dt><dd class="mt-1 break-all text-sm font-semibold text-navy">{{ $latestGscSync?->property_url ?? $website->searchConsoleSite?->site_url ?? 'Not selected' }}</dd></div>
            <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Date range</dt><dd class="mt-1 text-sm font-semibold text-navy">{{ $dateRangeLabel }}</dd></div>
            <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Search type</dt><dd class="mt-1 text-sm font-semibold text-navy">{{ ucfirst($latestGscSync?->search_type ?? 'web') }} search</dd></div>
            <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Country filter</dt><dd class="mt-1 text-sm font-semibold text-navy">{{ $latestGscSync?->country_filter ?: 'All countries' }}</dd></div>
            <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Device filter</dt><dd class="mt-1 text-sm font-semibold text-navy">{{ $latestGscSync?->device_filter ?: 'All devices' }}</dd></div>
            <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Rows fetched</dt><dd class="mt-1 text-sm font-semibold text-navy">@if($latestGscSync){{ $latestGscSync->rows_daily }} daily · {{ $latestGscSync->rows_queries }} queries · {{ $latestGscSync->rows_pages }} pages · {{ $latestGscSync->rows_devices }} devices · {{ $latestGscSync->rows_countries }} countries @else No rows yet @endif</dd></div>
            <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sync status</dt><dd class="mt-1 text-sm font-semibold text-navy">{{ ucfirst($syncStatus) }}</dd></div>
            <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Last sync</dt><dd class="mt-1 text-sm font-semibold text-navy">{{ $website->gsc_last_synced_at?->diffForHumans() ?? 'Never' }}</dd></div>
        </dl>
    </section>

    <section class="mt-6 rounded-lg bg-white p-5 shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)]">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-navy">Filters</h2>
            @if($availableCountries->isNotEmpty())<p class="text-sm text-slate-500">Available countries: {{ $availableCountries->implode(', ') }}</p>@endif
        </div>
        <form method="GET" action="{{ route('websites.show', $website) }}" class="mt-4 grid gap-3 md:grid-cols-3 xl:grid-cols-8">
            <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Start<input type="date" name="date_start" value="{{ $filters['date_start'] }}" class="min-h-10 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy"></label>
            <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">End<input type="date" name="date_end" value="{{ $filters['date_end'] }}" class="min-h-10 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy"></label>
            <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Country<select name="country" class="min-h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy"><option value="">All countries</option>@foreach($availableCountries as $country)<option value="{{ $country }}" @selected($filters['country'] === $country)>{{ $country }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Device<select name="device" class="min-h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy"><option value="">All devices</option>@foreach(['desktop','mobile','tablet'] as $device)<option value="{{ $device }}" @selected($filters['device'] === $device)>{{ ucfirst($device) }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Intent<select name="query_intent" class="min-h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy"><option value="">All intents</option>@foreach(['service_intent','local_service_intent','condition_intent','branded_practitioner','review_reputation','informational','competitor','irrelevant','unknown'] as $intent)<option value="{{ $intent }}" @selected($filters['query_intent'] === $intent)>{{ str_replace('_', ' ', ucfirst($intent)) }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Page type<select name="page_type" class="min-h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy"><option value="">All types</option>@foreach(['homepage','service_page','blog','legal','unknown'] as $type)<option value="{{ $type }}" @selected($filters['page_type'] === $type)>{{ str_replace('_', ' ', ucfirst($type)) }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Category<select name="opportunity_category" class="min-h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy"><option value="">All categories</option>@foreach(['acquisition_growth','service_page_growth','conversion_improvement','reputation_conversion','branded_visibility','technical_seo','low_value'] as $category)<option value="{{ $category }}" @selected($filters['opportunity_category'] === $category)>{{ str_replace('_', ' ', ucfirst($category)) }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Priority<select name="opportunity_priority" class="min-h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy"><option value="">All priorities</option>@foreach(['high','medium','low'] as $priority)<option value="{{ $priority }}" @selected($filters['opportunity_priority'] === $priority)>{{ ucfirst($priority) }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Status<select name="status" class="min-h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy"><option value="">Open</option>@foreach(['open','reviewed','in_progress','completed','ignored'] as $status)<option value="{{ $status }}" @selected($filters['status'] === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>@endforeach</select></label>
            <div class="flex items-end gap-2 md:col-span-2 xl:col-span-7"><button class="min-h-10 rounded-lg bg-navy px-4 py-2 text-sm font-semibold text-white transition-transform active:scale-[0.96]">Apply Filters</button><a href="{{ route('websites.show', $website) }}" class="inline-flex min-h-10 items-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-navy shadow-[0_0_0_1px_rgba(5,18,55,0.12)] transition-transform active:scale-[0.96]">Clear</a></div>
        </form>
    </section>

    <section class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        @foreach($summaryCards as $card)
            <article class="rounded-lg bg-white p-5 shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_10px_30px_rgba(5,18,55,0.07)]">
                <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-3 break-words text-3xl font-bold tabular-nums text-navy">{{ $card['value'] }}</p>
                <p class="mt-3 text-xs font-medium text-slate-400">{{ $card['note'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-6 grid gap-5 xl:grid-cols-4">
        <article class="rounded-lg bg-white p-5 shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)] xl:col-span-2"><h2 class="font-semibold text-navy">Clicks & Impressions</h2><p class="text-sm text-slate-500">{{ $dateRangeLabel }}</p><div class="mt-4 h-72"><canvas id="trafficTrend"></canvas></div></article>
        <article class="rounded-lg bg-white p-5 shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)]"><h2 class="font-semibold text-navy">CTR Trend</h2><div class="mt-4 h-64"><canvas id="ctrTrend"></canvas></div></article>
        <article class="rounded-lg bg-white p-5 shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)]"><h2 class="font-semibold text-navy">Position Trend</h2><div class="mt-4 h-64"><canvas id="positionTrend"></canvas></div></article>
        <article class="rounded-lg bg-white p-5 shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)] xl:col-span-2"><h2 class="font-semibold text-navy">Device Breakdown</h2><div class="mt-4 h-64"><canvas id="deviceBreakdown"></canvas></div></article>
        <article class="rounded-lg bg-white p-5 shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)] xl:col-span-2"><h2 class="font-semibold text-navy">Country Breakdown</h2><div class="mt-4 h-64"><canvas id="countryBreakdown"></canvas></div></article>
    </section>

    <section class="mt-6 rounded-lg bg-navy p-6 text-white shadow-soft">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div><p class="text-xs font-semibold uppercase tracking-[0.22em] text-teal-200">Top Conversion Priority</p><h2 class="mt-2 text-balance text-2xl font-bold">Specific next action</h2></div>
            @if($topPriority)<span class="rounded-lg bg-white/10 px-3 py-1 text-xs font-semibold">Score {{ $topPriority->score }}</span>@endif
        </div>
        @if($topPriority)
            <div class="mt-5 grid gap-4 lg:grid-cols-5">
                <div class="lg:col-span-3">
                    <p class="text-pretty text-lg font-semibold">{{ $topPriority->recommendation }}</p>
                    <p class="mt-3 text-sm leading-6 text-slate-300">{{ $topPriority->problem }}</p>
                </div>
                <dl class="grid gap-3 text-sm lg:col-span-2">
                    <div class="rounded-lg bg-white/10 p-3"><dt class="text-xs uppercase tracking-wide text-slate-300">Affected page/query</dt><dd class="mt-1 break-all font-semibold">{{ $topPriority->related_page_url ?: $topPriority->source_value }}</dd></div>
                    <div class="rounded-lg bg-white/10 p-3"><dt class="text-xs uppercase tracking-wide text-slate-300">Expected impact</dt><dd class="mt-1 font-semibold">{{ $topPriority->conversion_action ?: $topPriority->expected_result }}</dd></div>
                </dl>
            </div>
            <form method="POST" action="{{ route('growth-opportunities.tasks.store', $topPriority) }}" class="mt-5">@csrf<button class="min-h-10 rounded-lg bg-teal px-4 py-2 text-sm font-semibold text-white transition-transform active:scale-[0.96]">Create Task</button></form>
        @else
            <p class="mt-5 text-sm text-slate-300">Sync Search Console data to generate a specific conversion priority.</p>
        @endif
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
        <section class="rounded-lg bg-white shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)]">
            <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-navy">Marketing Tasks</h2></div>
            <div class="divide-y divide-slate-100">@forelse($website->marketingTasks as $task)<div class="px-5 py-4"><p class="font-semibold">{{ $task->title }}</p><p class="text-sm text-slate-500">{{ ucfirst($task->priority) }} · {{ str_replace('_',' ', $task->status) }}</p></div>@empty<div class="px-5 py-10 text-center text-sm text-slate-500">No tasks yet.</div>@endforelse</div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (() => {
            const data = @json($chartData);
            if (!window.Chart) return;
            const navy = '#051237';
            const teal = '#016576';
            const text = '#64748b';
            const grid = 'rgba(5,18,55,0.08)';
            const labels = values => values.length ? values : ['No data'];
            const points = values => values.length ? values : [0];
            const base = { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: text, boxWidth: 10 } } }, scales: { x: { grid: { display: false }, ticks: { color: text, maxRotation: 0 } }, y: { grid: { color: grid }, ticks: { color: text } } } };
            new Chart(document.getElementById('trafficTrend'), { type: 'line', data: { labels: labels(data.trend.labels), datasets: [{ label: 'Clicks', data: points(data.trend.clicks), borderColor: teal, backgroundColor: 'rgba(1,101,118,0.12)', fill: true, tension: 0.35, pointRadius: 2 }, { label: 'Impressions', data: points(data.trend.impressions), borderColor: navy, backgroundColor: 'rgba(5,18,55,0.08)', fill: true, tension: 0.35, pointRadius: 2 }] }, options: base });
            new Chart(document.getElementById('ctrTrend'), { type: 'line', data: { labels: labels(data.trend.labels), datasets: [{ label: 'CTR', data: points(data.trend.ctr), borderColor: '#059669', backgroundColor: 'rgba(5,150,105,0.12)', fill: true, tension: 0.35, pointRadius: 2 }] }, options: { ...base, plugins: { legend: { display: false } } } });
            new Chart(document.getElementById('positionTrend'), { type: 'line', data: { labels: labels(data.trend.labels), datasets: [{ label: 'Position', data: points(data.trend.position), borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.12)', fill: true, tension: 0.35, pointRadius: 2 }] }, options: { ...base, plugins: { legend: { display: false } }, scales: { ...base.scales, y: { ...base.scales.y, reverse: true } } } });
            new Chart(document.getElementById('deviceBreakdown'), { type: 'doughnut', data: { labels: labels(data.devices.labels), datasets: [{ data: points(data.devices.clicks), backgroundColor: [teal, navy, '#38bdf8'] }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { color: text, boxWidth: 10 } } }, cutout: '68%' } });
            new Chart(document.getElementById('countryBreakdown'), { type: 'bar', data: { labels: labels(data.countries.labels), datasets: [{ label: 'Clicks', data: points(data.countries.clicks), backgroundColor: teal, borderRadius: 6 }] }, options: base });
        })();
    </script>
</x-layouts.app>
