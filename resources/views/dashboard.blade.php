<x-layouts.app heading="Overview">
    @php
        $metricCards = [
            ['label' => 'Total clicks', 'value' => number_format($totalClicks), 'icon' => 'mouse-pointer-click', 'tone' => 'text-blue-600 bg-blue-50'],
            ['label' => 'Total impressions', 'value' => number_format($totalImpressions), 'icon' => 'eye', 'tone' => 'text-emerald-600 bg-emerald-50'],
            ['label' => 'Average CTR', 'value' => number_format($averageCtr, 2).'%', 'icon' => 'percent', 'tone' => 'text-violet-600 bg-violet-50'],
            ['label' => 'Average position', 'value' => number_format($averagePosition, 1), 'icon' => 'chart-no-axes-column-increasing', 'tone' => 'text-indigo-600 bg-indigo-50'],
            ['label' => 'Mobile clicks', 'value' => number_format($mobileClicks), 'icon' => 'smartphone', 'tone' => 'text-cyan-700 bg-cyan-50'],
            ['label' => 'Growth opportunities', 'value' => number_format($openGrowthOpportunities), 'icon' => 'lightbulb', 'tone' => 'text-amber-700 bg-amber-50'],
            ['label' => 'Conversion tasks', 'value' => number_format($pendingConversionTasks), 'icon' => 'circle-check-big', 'tone' => 'text-rose-600 bg-rose-50'],
            ['label' => 'Last sync', 'value' => ucfirst(str_replace('_', ' ', $lastSyncStatus)), 'icon' => 'refresh-cw', 'tone' => $lastSyncStatus === 'success' ? 'text-emerald-600 bg-emerald-50' : ($lastSyncStatus === 'failed' ? 'text-rose-600 bg-rose-50' : 'text-slate-600 bg-slate-100')],
        ];
        $hasActiveFilters = collect($filters)->contains(fn ($value) => filled($value));
        $hasTrendData = count($chartData['trend']['labels']) > 0;
    @endphp

    <section class="relative overflow-hidden rounded-lg bg-navy px-5 py-6 text-white shadow-[0_18px_44px_rgba(5,18,55,.18)] sm:px-7 sm:py-8">
        <div class="grid items-end gap-8 xl:grid-cols-[minmax(0,1.4fr)_minmax(340px,.6fr)]">
            <div class="max-w-3xl">
                <div class="flex items-center gap-2 text-xs font-semibold text-cyan-200">
                    <span class="grid h-6 w-6 place-items-center rounded-md bg-cyan-300/10"><i data-lucide="sparkles" class="h-3.5 w-3.5" aria-hidden="true"></i></span>
                    AI command center
                </div>
                <h2 class="mt-4 text-2xl font-bold leading-tight sm:text-3xl">AI Growth & Conversion Agent</h2>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">Agents use the connected data sources and conversion goals configured for this workspace.</p>
                <div class="mt-6 flex flex-wrap gap-2">
                    <a href="{{ route('websites.index') }}" class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-teal px-4 py-2 text-sm font-semibold text-white shadow-[0_10px_24px_rgba(1,101,118,.35)] hover:bg-[#087687]">
                        <i data-lucide="globe-2" class="h-4 w-4" aria-hidden="true"></i>Manage workspaces
                    </a>
                    <a href="{{ route('marketing-tasks.index') }}" class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-white/[.08] px-4 py-2 text-sm font-semibold text-white shadow-[inset_0_0_0_1px_rgba(255,255,255,.14)] hover:bg-white/[.13]">
                        <i data-lucide="list-checks" class="h-4 w-4" aria-hidden="true"></i>Open task board
                    </a>
                </div>
            </div>
            <dl class="grid grid-cols-2 gap-2 text-sm">
                <div class="rounded-lg bg-white/[.065] p-3 shadow-[inset_0_0_0_1px_rgba(255,255,255,.07)]">
                    <dt class="text-[10px] font-bold uppercase tracking-[.12em] text-slate-400">Workspaces</dt>
                    <dd class="mt-1 text-lg font-bold">{{ $websiteCount }}</dd>
                </div>
                <div class="rounded-lg bg-white/[.065] p-3 shadow-[inset_0_0_0_1px_rgba(255,255,255,.07)]">
                    <dt class="text-[10px] font-bold uppercase tracking-[.12em] text-slate-400">Connected</dt>
                    <dd class="mt-1 text-lg font-bold">{{ $connectedWebsiteCount }}</dd>
                </div>
                <div class="col-span-2 rounded-lg bg-white/[.065] p-3 shadow-[inset_0_0_0_1px_rgba(255,255,255,.07)]">
                    <dt class="text-[10px] font-bold uppercase tracking-[.12em] text-slate-400">Conversion goal</dt>
                    <dd class="mt-1 text-sm font-semibold text-white">{{ $goalContext['label'] }}</dd>
                    <p class="mt-1 text-xs text-cyan-200">{{ $goalContext['primary_action_label'] }}</p>
                </div>
                <div class="col-span-2 rounded-lg bg-white/[.065] p-3 shadow-[inset_0_0_0_1px_rgba(255,255,255,.07)]">
                    <dt class="text-[10px] font-bold uppercase tracking-[.12em] text-slate-400">Reporting period</dt>
                    <dd class="mt-1 text-sm font-semibold text-white">{{ $dateContext }}</dd>
                </div>
                <div class="col-span-2 flex items-center gap-2 pt-1 text-xs text-emerald-200">
                    <i data-lucide="shield-check" class="h-4 w-4" aria-hidden="true"></i>
                    Campaigns, messages, and website changes require approval.
                </div>
            </dl>
        </div>
    </section>

    <section class="app-panel mt-5 p-4">
        <div class="mb-3 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-slate-100 text-navy"><i data-lucide="sliders-horizontal" class="h-4 w-4" aria-hidden="true"></i></span>
                <div><h2 class="text-sm font-bold text-navy">Global filters</h2><p class="text-xs text-slate-500">Refine every metric on this page.</p></div>
            </div>
            @if($hasActiveFilters)<span class="rounded-full bg-teal/10 px-2.5 py-1 text-[11px] font-bold text-teal">Active</span>@endif
        </div>
        <form method="GET" action="{{ route('dashboard') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-8">
            <label class="grid gap-1 text-[11px] font-bold text-slate-500">Workspace
                <select name="website_id" class="min-h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-navy">
                    <option value="">All workspaces</option>
                    @foreach($websites as $website)<option value="{{ $website->id }}" @selected((string) $filters['website_id'] === (string) $website->id)>{{ $website->name }}</option>@endforeach
                </select>
            </label>
            <label class="grid gap-1 text-[11px] font-bold text-slate-500">Start date<input type="date" name="date_start" value="{{ $filters['date_start'] }}" class="min-h-10 w-full rounded-lg border border-slate-200 px-3 text-sm font-medium text-navy"></label>
            <label class="grid gap-1 text-[11px] font-bold text-slate-500">End date<input type="date" name="date_end" value="{{ $filters['date_end'] }}" class="min-h-10 w-full rounded-lg border border-slate-200 px-3 text-sm font-medium text-navy"></label>
            <label class="grid gap-1 text-[11px] font-bold text-slate-500">Country<select name="country" class="min-h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-navy"><option value="">All countries</option>@foreach($countries as $country)<option value="{{ $country }}" @selected($filters['country'] === $country)>{{ $country }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-[11px] font-bold text-slate-500">Device<select name="device" class="min-h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-navy"><option value="">All devices</option>@foreach(['desktop','mobile','tablet'] as $device)<option value="{{ $device }}" @selected($filters['device'] === $device)>{{ ucfirst($device) }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-[11px] font-bold text-slate-500">Intent<select name="intent" class="min-h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-navy"><option value="">All intents</option>@foreach(['service_intent' => 'Offer intent','local_service_intent' => 'Local high intent','condition_intent' => 'Need or problem intent','branded_practitioner' => 'Brand or representative','review_reputation' => 'Reviews and reputation','informational' => 'Informational','competitor' => 'Competitor','irrelevant' => 'Irrelevant','unknown' => 'Unknown'] as $intent => $label)<option value="{{ $intent }}" @selected($filters['intent'] === $intent)>{{ $label }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-[11px] font-bold text-slate-500">Priority<select name="priority" class="min-h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-navy"><option value="">All priorities</option>@foreach(['high','medium','low'] as $priority)<option value="{{ $priority }}" @selected($filters['priority'] === $priority)>{{ ucfirst($priority) }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-[11px] font-bold text-slate-500">Sync status<select name="sync_status" class="min-h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-navy"><option value="">Any status</option>@foreach(['success','running','failed'] as $status)<option value="{{ $status }}" @selected($filters['sync_status'] === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label>
            <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-4 2xl:col-span-8">
                <button class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-teal px-4 py-2 text-sm font-semibold text-white shadow-[0_8px_20px_rgba(1,101,118,.2)]"><i data-lucide="filter" class="h-4 w-4" aria-hidden="true"></i>Apply filters</button>
                <a href="{{ route('dashboard') }}" class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-[inset_0_0_0_1px_rgba(5,18,55,.12)] hover:text-navy"><i data-lucide="rotate-ccw" class="h-4 w-4" aria-hidden="true"></i>Reset</a>
            </div>
        </form>
    </section>

    <section class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-4 2xl:grid-cols-8">
        @foreach($metricCards as $card)
            <article class="app-panel min-h-[132px] p-4">
                <div class="flex items-start justify-between gap-2">
                    <span class="grid h-8 w-8 place-items-center rounded-lg {{ $card['tone'] }}"><i data-lucide="{{ $card['icon'] }}" class="h-4 w-4" aria-hidden="true"></i></span>
                    <i data-lucide="more-horizontal" class="h-4 w-4 text-slate-300" aria-hidden="true"></i>
                </div>
                <p class="mt-3 truncate text-[11px] font-semibold text-slate-500" title="{{ $card['label'] }}">{{ $card['label'] }}</p>
                <p class="mt-1 break-words text-xl font-bold leading-tight text-navy sm:text-2xl">{{ $card['value'] }}</p>
                <p class="mt-2 truncate text-[10px] font-medium text-slate-400" title="{{ $dateContext }}">{{ $dateContext }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-5 grid gap-4 xl:grid-cols-3">
        <article class="app-panel p-4 sm:p-5 xl:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div><h2 class="text-sm font-bold text-navy">Performance over time</h2><p class="mt-1 text-xs text-slate-500">Clicks and impressions across {{ strtolower($dateContext) }}.</p></div>
                <div class="flex items-center gap-3 text-[11px] font-semibold text-slate-500"><span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-blue-600"></span>Clicks</span><span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-teal"></span>Impressions</span></div>
            </div>
            <div class="relative mt-4 h-[300px]">
                <canvas id="trafficTrend"></canvas>
                @unless($hasTrendData)<div class="absolute inset-0 grid place-items-center"><div class="text-center"><i data-lucide="chart-no-axes-combined" class="mx-auto h-7 w-7 text-slate-300" aria-hidden="true"></i><p class="mt-2 text-sm font-semibold text-slate-500">No performance data yet</p><p class="mt-1 text-xs text-slate-400">Sync a connected website to populate this chart.</p></div></div>@endunless
            </div>
        </article>
        <article class="app-panel p-4 sm:p-5">
            <div><h2 class="text-sm font-bold text-navy">Clicks by device</h2><p class="mt-1 text-xs text-slate-500">Where search visitors are finding you.</p></div>
            <div class="mt-4 h-[300px]"><canvas id="deviceBreakdown"></canvas></div>
        </article>
    </section>

    <section class="mt-4 grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
        <article class="app-panel p-4 sm:p-5"><h2 class="text-sm font-bold text-navy">CTR trend</h2><p class="mt-1 text-xs text-slate-500">Click-through rate movement.</p><div class="mt-4 h-56"><canvas id="ctrTrend"></canvas></div></article>
        <article class="app-panel p-4 sm:p-5"><h2 class="text-sm font-bold text-navy">Average position</h2><p class="mt-1 text-xs text-slate-500">Lower is better.</p><div class="mt-4 h-56"><canvas id="positionTrend"></canvas></div></article>
        <article class="app-panel p-4 sm:p-5 lg:col-span-2 xl:col-span-1"><h2 class="text-sm font-bold text-navy">Country breakdown</h2><p class="mt-1 text-xs text-slate-500">Clicks by search country.</p><div class="mt-4 h-56"><canvas id="countryBreakdown"></canvas></div></article>
    </section>

    <section class="mt-5 overflow-hidden rounded-lg bg-navy px-5 py-6 text-white shadow-[0_18px_44px_rgba(5,18,55,.16)] sm:px-7">
        @if($topConversionPriority)
            <div class="grid items-center gap-6 xl:grid-cols-[minmax(0,1fr)_180px_180px_auto]">
                <div class="flex items-start gap-4">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-cyan-300/10 text-cyan-200"><i data-lucide="sparkles" class="h-5 w-5" aria-hidden="true"></i></span>
                    <div><p class="text-[10px] font-bold uppercase tracking-[.15em] text-cyan-200">AI priority · Score {{ $topConversionPriority->score }}</p><h2 class="mt-1 text-lg font-bold">{{ $topConversionPriority->recommendation }}</h2><p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">{{ $topConversionPriority->problem }}</p></div>
                </div>
                <div class="border-white/10 xl:border-l xl:pl-6"><p class="text-[10px] font-bold uppercase tracking-[.12em] text-slate-400">Workspace</p><p class="mt-1 text-sm font-semibold">{{ $topConversionPriority->website->name }}</p></div>
                <div class="border-white/10 xl:border-l xl:pl-6"><p class="text-[10px] font-bold uppercase tracking-[.12em] text-slate-400">Expected impact</p><p class="mt-1 text-sm font-semibold text-cyan-200">{{ $topConversionPriority->conversion_action ?: $topConversionPriority->expected_result }}</p></div>
                <form method="POST" action="{{ route('growth-opportunities.tasks.store', $topConversionPriority) }}">@csrf<button class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-lg bg-teal px-4 py-2 text-sm font-semibold text-white hover:bg-[#087687]"><i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>Create task</button></form>
            </div>
        @else
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><p class="text-[10px] font-bold uppercase tracking-[.15em] text-cyan-200">AI priority</p><h2 class="mt-1 text-lg font-bold">Your next goal-aligned action will appear here</h2><p class="mt-2 text-sm text-slate-300">Connect evidence sources to rank an opportunity against the configured conversion goal.</p></div><a href="{{ route('websites.index') }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-teal px-4 py-2 text-sm font-semibold text-white"><i data-lucide="refresh-cw" class="h-4 w-4" aria-hidden="true"></i>Choose workspace</a></div>
        @endif
    </section>

    <section class="app-panel mt-5 overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-5">
            <div><h2 class="text-sm font-bold text-navy">Workspace performance</h2><p class="mt-1 text-xs text-slate-500">Connected evidence and active goal-based conversion work.</p></div>
            <span class="rounded-full bg-cyan-50 px-2.5 py-1 text-[11px] font-bold text-teal">{{ $websiteRows->count() }} workspaces</span>
        </div>

        <div class="hidden overflow-x-auto md:block">
            <table class="app-table w-full min-w-[1120px] text-left text-sm">
                <thead class="border-y border-slate-100 bg-slate-50/80"><tr><th class="px-5 py-3">Workspace</th><th class="px-5 py-3">Property</th><th class="px-5 py-3">Date range</th><th class="px-5 py-3">Clicks</th><th class="px-5 py-3">Impressions</th><th class="px-5 py-3">CTR</th><th class="px-5 py-3">Position</th><th class="px-5 py-3">Top opportunity</th><th class="px-5 py-3">Last sync</th><th class="px-5 py-3 text-right">Actions</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($websiteRows as $row)
                    <tr>
                        <td class="px-5 py-4"><a href="{{ route('websites.show', $row['website']) }}" class="font-bold text-navy hover:text-teal">{{ $row['website']->name }}</a><p class="mt-1 text-xs text-slate-500">{{ $row['goal_profile']['label'] }}</p></td>
                        <td class="max-w-[220px] truncate px-5 py-4 text-xs text-slate-500" title="{{ $row['sync_context']?->property_url ?? 'Not synced' }}">{{ $row['sync_context']?->property_url ?? 'Not synced' }}</td>
                        <td class="whitespace-nowrap px-5 py-4 text-xs text-slate-600">@if($row['sync_context']){{ $row['sync_context']->date_start->format('M j') }} - {{ $row['sync_context']->date_end->format('M j, Y') }}@else No range @endif</td>
                        <td class="px-5 py-4 font-semibold text-navy">{{ number_format($row['clicks']) }}</td><td class="px-5 py-4">{{ number_format($row['impressions']) }}</td><td class="px-5 py-4">{{ number_format($row['ctr'], 2) }}%</td><td class="px-5 py-4">{{ number_format($row['position'], 1) }}</td>
                        <td class="max-w-[180px] truncate px-5 py-4 text-xs text-slate-600">{{ $row['top_opportunity']?->opportunity_type ? str_replace('_', ' ', $row['top_opportunity']->opportunity_type) : 'None yet' }}</td>
                        <td class="whitespace-nowrap px-5 py-4 text-xs text-slate-500">{{ $row['website']->gsc_last_synced_at?->diffForHumans() ?? 'Never' }}</td>
                        <td class="px-5 py-4"><div class="flex justify-end gap-2"><a href="{{ route('websites.show', $row['website']) }}" class="grid h-10 w-10 place-items-center rounded-lg bg-navy text-white" title="Open website" aria-label="Open {{ $row['website']->name }}"><i data-lucide="arrow-up-right" class="h-4 w-4" aria-hidden="true"></i></a>@if($row['website']->search_console_site_id)<form method="POST" action="{{ route('websites.search-console.sync', $row['website']) }}">@csrf<button class="grid h-10 w-10 place-items-center rounded-lg bg-white text-teal shadow-[inset_0_0_0_1px_rgba(1,101,118,.2)]" title="Sync search data" aria-label="Sync {{ $row['website']->name }}"><i data-lucide="refresh-cw" class="h-4 w-4" aria-hidden="true"></i></button></form>@endif</div></td>
                    </tr>
                @empty<tr><td colspan="10" class="px-5 py-12 text-center text-sm text-slate-500">{{ $hasActiveFilters ? 'No workspaces match the current filters.' : 'No workspaces have been added yet.' }}</td></tr>@endforelse
                </tbody>
            </table>
        </div>

        <div class="grid gap-3 border-t border-slate-100 p-3 md:hidden">
            @forelse($websiteRows as $row)
                <article class="rounded-lg bg-slate-50 p-4 shadow-[inset_0_0_0_1px_rgba(5,18,55,.05)]">
                    <div class="flex items-start justify-between gap-3"><div class="min-w-0"><a href="{{ route('websites.show', $row['website']) }}" class="font-bold text-navy">{{ $row['website']->name }}</a><p class="mt-1 text-xs font-medium text-teal">{{ $row['goal_profile']['label'] }}</p><p class="mt-1 truncate text-xs text-slate-500">{{ $row['sync_context']?->property_url ?? 'Not synced' }}</p></div><a href="{{ route('websites.show', $row['website']) }}" class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-navy text-white" aria-label="Open {{ $row['website']->name }}"><i data-lucide="arrow-up-right" class="h-4 w-4" aria-hidden="true"></i></a></div>
                    <dl class="mt-4 grid grid-cols-3 gap-3 text-xs"><div><dt class="text-slate-400">Clicks</dt><dd class="mt-1 font-bold text-navy">{{ number_format($row['clicks']) }}</dd></div><div><dt class="text-slate-400">Impressions</dt><dd class="mt-1 font-bold text-navy">{{ number_format($row['impressions']) }}</dd></div><div><dt class="text-slate-400">CTR</dt><dd class="mt-1 font-bold text-navy">{{ number_format($row['ctr'], 2) }}%</dd></div></dl>
                </article>
            @empty<div class="px-4 py-10 text-center text-sm text-slate-500">{{ $hasActiveFilters ? 'No workspaces match the current filters.' : 'No workspaces have been added yet.' }}</div>@endforelse
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (() => {
            const data = @json($chartData);
            if (!window.Chart) return;
            Chart.defaults.font.family = 'Inter, ui-sans-serif, system-ui, sans-serif';
            Chart.defaults.color = '#64748b';
            const labels = (values) => values.length ? values : ['No data'];
            const points = (values) => values.length ? values : [0];
            const grid = 'rgba(5,18,55,.07)';
            const baseScales = { x: { grid: { display: false }, border: { display: false }, ticks: { maxRotation: 0, autoSkip: true, padding: 8, font: { size: 10 } } }, y: { grid: { color: grid }, border: { display: false }, ticks: { padding: 8, font: { size: 10 } } } };
            const lineDataset = (label, values, color, background) => ({ label, data: points(values), borderColor: color, backgroundColor: background, fill: true, tension: .36, pointRadius: 0, pointHoverRadius: 4, borderWidth: 2 });

            new Chart(document.getElementById('trafficTrend'), { type: 'line', data: { labels: labels(data.trend.labels), datasets: [lineDataset('Clicks', data.trend.clicks, '#2563eb', 'rgba(37,99,235,.06)'), lineDataset('Impressions', data.trend.impressions, '#0c9aa6', 'rgba(12,154,166,.08)')] }, options: { responsive: true, maintainAspectRatio: false, interaction: { intersect: false, mode: 'index' }, plugins: { legend: { display: false }, tooltip: { backgroundColor: '#051237', padding: 12, cornerRadius: 8, titleFont: { weight: 600 } } }, scales: baseScales } });
            new Chart(document.getElementById('ctrTrend'), { type: 'line', data: { labels: labels(data.trend.labels), datasets: [lineDataset('CTR', data.trend.ctr, '#7c3aed', 'rgba(124,58,237,.08)')] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: baseScales } });
            new Chart(document.getElementById('positionTrend'), { type: 'line', data: { labels: labels(data.trend.labels), datasets: [lineDataset('Position', data.trend.position, '#016576', 'rgba(1,101,118,.08)')] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { ...baseScales, y: { ...baseScales.y, reverse: true } } } });
            new Chart(document.getElementById('deviceBreakdown'), { type: 'doughnut', data: { labels: labels(data.devices.labels), datasets: [{ data: points(data.devices.clicks), backgroundColor: ['#016576', '#2563eb', '#7c3aed'], borderWidth: 0, hoverOffset: 4 }] }, options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'rectRounded', boxWidth: 8, padding: 18, font: { size: 11, weight: 600 } } } } } });
            new Chart(document.getElementById('countryBreakdown'), { type: 'bar', data: { labels: labels(data.countries.labels), datasets: [{ label: 'Clicks', data: points(data.countries.clicks), backgroundColor: '#0c9aa6', borderRadius: 4, borderSkipped: false }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: baseScales } });
        })();
    </script>
</x-layouts.app>
