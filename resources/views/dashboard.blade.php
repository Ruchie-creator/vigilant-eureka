<x-layouts.app heading="AI Growth & Conversion Agent">
    @php
        $metricCards = [
            ['label' => 'Total Clicks', 'value' => number_format($totalClicks), 'accent' => 'bg-teal'],
            ['label' => 'Total Impressions', 'value' => number_format($totalImpressions), 'accent' => 'bg-navy'],
            ['label' => 'Average CTR', 'value' => number_format($averageCtr, 2).'%', 'accent' => 'bg-emerald-500'],
            ['label' => 'Average Position', 'value' => number_format($averagePosition, 1), 'accent' => 'bg-indigo-500'],
            ['label' => 'Mobile Clicks', 'value' => number_format($mobileClicks), 'accent' => 'bg-cyan-500'],
            ['label' => 'Open Growth Opportunities', 'value' => number_format($openGrowthOpportunities), 'accent' => 'bg-amber-500'],
            ['label' => 'Pending Conversion Tasks', 'value' => number_format($pendingConversionTasks), 'accent' => 'bg-rose-500'],
            ['label' => 'Last Sync Status', 'value' => ucfirst(str_replace('_', ' ', $lastSyncStatus)), 'accent' => $lastSyncStatus === 'success' ? 'bg-emerald-500' : ($lastSyncStatus === 'failed' ? 'bg-rose-500' : 'bg-slate-400')],
        ];
    @endphp

    <section class="relative overflow-hidden rounded-lg bg-navy p-6 text-white shadow-soft">
        <div class="absolute inset-y-0 right-0 hidden w-1/2 bg-[radial-gradient(circle_at_70%_30%,rgba(1,101,118,0.45),transparent_45%)] lg:block"></div>
        <div class="relative max-w-4xl">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-teal-200">Command center</p>
            <h1 class="mt-3 max-w-3xl text-balance text-3xl font-bold tracking-tight md:text-4xl">AI Growth & Conversion Agent</h1>
            <p class="mt-3 max-w-2xl text-pretty text-sm leading-6 text-slate-200">Track search visibility, uncover service growth opportunities, and improve appointment conversions.</p>
            <p class="mt-4 inline-flex rounded-lg bg-white/10 px-3 py-2 text-xs font-semibold text-white shadow-[0_0_0_1px_rgba(255,255,255,0.12)]">No patient medical data is collected.</p>
        </div>
    </section>

    <section class="mt-6 rounded-lg bg-white p-4 shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_18px_45px_rgba(5,18,55,0.08)]">
        <form method="GET" action="{{ route('dashboard') }}" class="grid gap-3 md:grid-cols-3 xl:grid-cols-7">
            <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Website
                <select name="website_id" class="min-h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy">
                    <option value="">All websites</option>
                    @foreach($websites as $website)
                        <option value="{{ $website->id }}" @selected((string) $filters['website_id'] === (string) $website->id)>{{ $website->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Start
                <input type="date" name="date_start" value="{{ $filters['date_start'] }}" class="min-h-10 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy">
            </label>
            <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">End
                <input type="date" name="date_end" value="{{ $filters['date_end'] }}" class="min-h-10 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy">
            </label>
            <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Country
                <select name="country" class="min-h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy">
                    <option value="">All countries</option>
                    @foreach($countries as $country)
                        <option value="{{ $country }}" @selected($filters['country'] === $country)>{{ $country }}</option>
                    @endforeach
                </select>
            </label>
            <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Device
                <select name="device" class="min-h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy">
                    <option value="">All devices</option>
                    @foreach(['desktop','mobile','tablet'] as $device)
                        <option value="{{ $device }}" @selected($filters['device'] === $device)>{{ ucfirst($device) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Intent
                <select name="intent" class="min-h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy">
                    <option value="">All intents</option>
                    @foreach(['service_intent','local_service_intent','condition_intent','branded_practitioner','review_reputation','informational','competitor','irrelevant','unknown'] as $intent)
                        <option value="{{ $intent }}" @selected($filters['intent'] === $intent)>{{ str_replace('_', ' ', ucfirst($intent)) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Priority
                <select name="priority" class="min-h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy">
                    <option value="">All priorities</option>
                    @foreach(['high','medium','low'] as $priority)
                        <option value="{{ $priority }}" @selected($filters['priority'] === $priority)>{{ ucfirst($priority) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Sync
                <select name="sync_status" class="min-h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy">
                    <option value="">Any status</option>
                    @foreach(['success','running','failed'] as $status)
                        <option value="{{ $status }}" @selected($filters['sync_status'] === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex items-end gap-2 md:col-span-2 xl:col-span-6">
                <button class="min-h-10 rounded-lg bg-teal px-4 py-2 text-sm font-semibold text-white shadow-[0_8px_20px_rgba(1,101,118,0.22)] transition-transform active:scale-[0.96]">Apply Filters</button>
                <a href="{{ route('dashboard') }}" class="inline-flex min-h-10 items-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-navy shadow-[0_0_0_1px_rgba(5,18,55,0.12)] transition-transform active:scale-[0.96]">Clear</a>
            </div>
        </form>
    </section>

    <section class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach($metricCards as $card)
            <article class="rounded-lg bg-white p-5 shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_10px_30px_rgba(5,18,55,0.07)]">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                        <p class="mt-3 text-3xl font-bold tabular-nums text-navy">{{ $card['value'] }}</p>
                    </div>
                    <span class="h-2.5 w-2.5 rounded-full {{ $card['accent'] }}"></span>
                </div>
                <p class="mt-3 text-xs font-medium text-slate-400">{{ $dateContext }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-6 grid gap-5 xl:grid-cols-4">
        <article class="rounded-lg bg-white p-5 shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)] xl:col-span-2">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-navy">Clicks Trend</h2>
                    <p class="text-sm text-slate-500">{{ $dateContext }}</p>
                </div>
            </div>
            <div class="h-72"><canvas id="clicksTrend"></canvas></div>
        </article>
        <article class="rounded-lg bg-white p-5 shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)] xl:col-span-2">
            <h2 class="text-base font-semibold text-navy">Impressions Trend</h2>
            <p class="text-sm text-slate-500">{{ $dateContext }}</p>
            <div class="mt-4 h-72"><canvas id="impressionsTrend"></canvas></div>
        </article>
        <article class="rounded-lg bg-white p-5 shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)]">
            <h2 class="text-base font-semibold text-navy">CTR Trend</h2>
            <div class="mt-4 h-64"><canvas id="ctrTrend"></canvas></div>
        </article>
        <article class="rounded-lg bg-white p-5 shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)]">
            <h2 class="text-base font-semibold text-navy">Average Position Trend</h2>
            <div class="mt-4 h-64"><canvas id="positionTrend"></canvas></div>
        </article>
        <article class="rounded-lg bg-white p-5 shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)]">
            <h2 class="text-base font-semibold text-navy">Device Breakdown</h2>
            <div class="mt-4 h-64"><canvas id="deviceBreakdown"></canvas></div>
        </article>
        <article class="rounded-lg bg-white p-5 shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)]">
            <h2 class="text-base font-semibold text-navy">Country Breakdown</h2>
            <div class="mt-4 h-64"><canvas id="countryBreakdown"></canvas></div>
        </article>
    </section>

    <section class="mt-6 grid gap-5 xl:grid-cols-3">
        <article class="rounded-lg bg-navy p-6 text-white shadow-soft xl:col-span-1">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-teal-200">Top priority</p>
                    <h2 class="mt-2 text-xl font-bold text-balance">Conversion Priority</h2>
                </div>
                @if($topConversionPriority)<span class="rounded-lg bg-white/10 px-3 py-1 text-xs font-semibold">Score {{ $topConversionPriority->score }}</span>@endif
            </div>
            @if($topConversionPriority)
                <div class="mt-5 space-y-4 text-sm">
                    <p><span class="block text-xs font-semibold uppercase tracking-wide text-slate-300">Website</span>{{ $topConversionPriority->website->name }}</p>
                    <p class="break-all"><span class="block text-xs font-semibold uppercase tracking-wide text-slate-300">Affected page/query</span>{{ $topConversionPriority->related_page_url ?: $topConversionPriority->source_value }}</p>
                    <p><span class="block text-xs font-semibold uppercase tracking-wide text-slate-300">Reason</span>{{ $topConversionPriority->problem }}</p>
                    <p><span class="block text-xs font-semibold uppercase tracking-wide text-slate-300">Exact next action</span>{{ $topConversionPriority->recommendation }}</p>
                    <p><span class="block text-xs font-semibold uppercase tracking-wide text-slate-300">Expected conversion impact</span>{{ $topConversionPriority->conversion_action ?: $topConversionPriority->expected_result }}</p>
                </div>
                <form method="POST" action="{{ route('growth-opportunities.tasks.store', $topConversionPriority) }}" class="mt-5">
                    @csrf
                    <button class="min-h-10 rounded-lg bg-teal px-4 py-2 text-sm font-semibold text-white transition-transform active:scale-[0.96]">Create Task</button>
                </form>
            @else
                <p class="mt-5 text-sm leading-6 text-slate-300">Sync Search Console data to generate a ranked conversion priority.</p>
            @endif
        </article>

        <article class="rounded-lg bg-white shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)] xl:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="font-semibold text-navy">Website Performance</h2>
                    <p class="text-sm text-slate-500">Search visibility and conversion backlog by website.</p>
                </div>
                <span class="rounded-lg bg-mist px-3 py-1 text-xs font-semibold text-teal">{{ $websiteRows->count() }} websites</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Website</th>
                        <th class="px-5 py-3">Property</th>
                        <th class="px-5 py-3">Date range</th>
                        <th class="px-5 py-3">Clicks</th>
                        <th class="px-5 py-3">Impressions</th>
                        <th class="px-5 py-3">CTR</th>
                        <th class="px-5 py-3">Position</th>
                        <th class="px-5 py-3">Top opportunity</th>
                        <th class="px-5 py-3">Last sync</th>
                        <th class="px-5 py-3">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($websiteRows as $row)
                        <tr class="align-top">
                            <td class="px-5 py-4 font-semibold"><a href="{{ route('websites.show', $row['website']) }}" class="text-teal">{{ $row['website']->name }}</a></td>
                            <td class="max-w-xs break-all px-5 py-4 text-slate-600">{{ $row['sync_context']?->property_url ?? 'Not synced' }}</td>
                            <td class="px-5 py-4 text-slate-600">@if($row['sync_context']){{ $row['sync_context']->date_start->format('M j') }} - {{ $row['sync_context']->date_end->format('M j, Y') }}@else No range @endif</td>
                            <td class="px-5 py-4 tabular-nums">{{ number_format($row['clicks']) }}</td>
                            <td class="px-5 py-4 tabular-nums">{{ number_format($row['impressions']) }}</td>
                            <td class="px-5 py-4 tabular-nums">{{ number_format($row['ctr'], 2) }}%</td>
                            <td class="px-5 py-4 tabular-nums">{{ number_format($row['position'], 1) }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $row['top_opportunity']?->opportunity_type ? str_replace('_', ' ', $row['top_opportunity']->opportunity_type) : 'None yet' }}</td>
                            <td class="px-5 py-4 text-slate-500">{{ $row['website']->gsc_last_synced_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="px-5 py-4">
                                <div class="flex gap-2">
                                    <a href="{{ route('websites.show', $row['website']) }}" class="inline-flex min-h-10 items-center rounded-lg bg-navy px-3 py-2 text-xs font-semibold text-white transition-transform active:scale-[0.96]">Open</a>
                                    @if($row['website']->search_console_site_id)
                                        <form method="POST" action="{{ route('websites.search-console.sync', $row['website']) }}">@csrf<button class="min-h-10 rounded-lg bg-white px-3 py-2 text-xs font-semibold text-teal shadow-[0_0_0_1px_rgba(1,101,118,0.18)] transition-transform active:scale-[0.96]">Sync</button></form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-5 py-10 text-center text-slate-500">No websites yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (() => {
            const data = @json($chartData);
            const navy = '#051237';
            const teal = '#016576';
            const grid = 'rgba(5, 18, 55, 0.08)';
            const text = '#64748b';

            const safeLabels = labels => labels.length ? labels : ['No data'];
            const safeData = values => values.length ? values : [0];
            const baseOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: text, maxRotation: 0, autoSkip: true } },
                    y: { grid: { color: grid }, ticks: { color: text } }
                }
            };

            if (!window.Chart) {
                return;
            }

            const line = (id, values, label, color, reverse = false) => new Chart(document.getElementById(id), {
                type: 'line',
                data: {
                    labels: safeLabels(data.trend.labels),
                    datasets: [{ label, data: safeData(values), borderColor: color, backgroundColor: color + '22', fill: true, tension: 0.35, pointRadius: 2, borderWidth: 2 }]
                },
                options: { ...baseOptions, scales: { ...baseOptions.scales, y: { ...baseOptions.scales.y, reverse } } }
            });

            line('clicksTrend', data.trend.clicks, 'Clicks', teal);
            line('impressionsTrend', data.trend.impressions, 'Impressions', navy);
            line('ctrTrend', data.trend.ctr, 'CTR', '#059669');
            line('positionTrend', data.trend.position, 'Position', '#6366f1', true);

            new Chart(document.getElementById('deviceBreakdown'), {
                type: 'doughnut',
                data: { labels: safeLabels(data.devices.labels), datasets: [{ data: safeData(data.devices.clicks), backgroundColor: [teal, navy, '#38bdf8'] }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { color: text, boxWidth: 10 } } }, cutout: '68%' }
            });

            new Chart(document.getElementById('countryBreakdown'), {
                type: 'bar',
                data: { labels: safeLabels(data.countries.labels), datasets: [{ data: safeData(data.countries.clicks), backgroundColor: teal, borderRadius: 6 }] },
                options: baseOptions
            });
        })();
    </script>
</x-layouts.app>
