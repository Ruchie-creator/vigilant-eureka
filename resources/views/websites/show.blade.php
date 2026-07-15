<x-layouts.app :heading="$website->name">
    @php
        $connectionLabel = $propertyMismatch ? 'Property mismatch' : ($website->searchConsoleSite ? 'Connected' : 'Not connected');
        $connectionClass = $propertyMismatch ? 'bg-amber-100 text-amber-800' : ($website->searchConsoleSite ? 'bg-teal/10 text-teal' : 'bg-slate-100 text-slate-700');
        $hasLegacyMetrics = ! $latestGscSync && $dataDateStart && $dataDateEnd;
        $syncStatus = $latestGscSync?->status ?? ($hasLegacyMetrics ? 'Data available' : 'Not synced');
        $dateRangeLabel = $dataDateStart && $dataDateEnd
            ? $dataDateStart->format('M j, Y').' - '.$dataDateEnd->format('M j, Y')
            : 'No Search Console data';
        $isAppointmentGoal = $goalProfile['key'] === 'appointment_booking';
        $summaryCards = [
            ['label' => 'Clicks', 'value' => number_format((int) ($gscSummary->clicks ?? 0)), 'note' => $dateRangeLabel],
            ['label' => 'Impressions', 'value' => number_format((int) ($gscSummary->impressions ?? 0)), 'note' => $dateRangeLabel],
            ['label' => 'CTR', 'value' => number_format((float) ($gscSummary->ctr ?? 0), 2).'%', 'note' => $dateRangeLabel],
            ['label' => 'Position', 'value' => number_format((float) ($gscSummary->position ?? 0), 1), 'note' => $dateRangeLabel],
            ['label' => 'Mobile Clicks', 'value' => number_format($mobileClicks), 'note' => 'Mobile search traffic'],
            ['label' => 'Top Country', 'value' => $topCountry?->country ?? 'None', 'note' => $topCountry ? number_format($topCountry->clicks).' clicks' : 'No country data'],
            ['label' => 'Top Device', 'value' => $topDevice?->device ? ucfirst($topDevice->device) : 'None', 'note' => $topDevice ? number_format($topDevice->clicks).' clicks' : 'No device data'],
            ['label' => $isAppointmentGoal ? 'Service Page Clicks' : 'Priority Page Clicks', 'value' => number_format($servicePageClicks), 'note' => 'High-intent landing pages'],
            ['label' => 'Branded Clicks', 'value' => number_format($brandedClicks), 'note' => 'Brand and reputation queries'],
            ['label' => 'Open Conversion Opportunities', 'value' => number_format($openConversionOpportunities), 'note' => $goalProfile['primary_action_label'].' backlog'],
        ];
    @endphp

    <section class="overflow-hidden rounded-lg bg-navy px-5 py-6 text-white shadow-[0_18px_44px_rgba(5,18,55,.18)] sm:px-7 sm:py-8">
        <div class="flex flex-col gap-7">
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(360px,.6fr)] xl:items-end">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/[.08] px-2.5 py-1 text-[11px] font-semibold text-slate-200"><i data-lucide="briefcase-business" class="h-3.5 w-3.5" aria-hidden="true"></i>{{ str_replace('_', ' ', ucfirst($website->type ?: 'website')) }}</span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/[.08] px-2.5 py-1 text-[11px] font-semibold text-slate-200"><i data-lucide="languages" class="h-3.5 w-3.5" aria-hidden="true"></i>{{ strtoupper($website->language ?: 'n/a') }}</span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-cyan-300/10 px-2.5 py-1 text-[11px] font-semibold text-cyan-100"><i data-lucide="target" class="h-3.5 w-3.5" aria-hidden="true"></i>{{ $goalProfile['label'] }}</span>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $connectionClass }}"><i data-lucide="{{ $propertyMismatch ? 'triangle-alert' : ($website->searchConsoleSite ? 'circle-check' : 'circle-minus') }}" class="h-3.5 w-3.5" aria-hidden="true"></i>{{ $connectionLabel }}</span>
                    </div>
                    <h1 class="mt-4 text-2xl font-bold leading-tight sm:text-3xl">{{ $website->name }}</h1>
                    <a href="{{ $website->url }}" target="_blank" rel="noopener" class="mt-2 inline-flex max-w-full items-center gap-2 break-all text-sm font-semibold text-cyan-200 hover:text-white"><i data-lucide="external-link" class="h-4 w-4 shrink-0" aria-hidden="true"></i>{{ $website->url }}</a>
                    <p class="mt-4 max-w-2xl text-sm leading-6 text-slate-300">Agents use the connected data sources and conversion goals configured for this workspace. Sensitive personal or medical data is not collected.</p>
                </div>
                <dl class="grid grid-cols-2 gap-2 text-sm">
                    <div class="rounded-lg bg-white/[.065] p-3 shadow-[inset_0_0_0_1px_rgba(255,255,255,.07)]"><dt class="text-[10px] font-bold uppercase tracking-[.12em] text-slate-400">Location</dt><dd class="mt-1 font-semibold">{{ $targetLocation }}</dd></div>
                    <div class="rounded-lg bg-white/[.065] p-3 shadow-[inset_0_0_0_1px_rgba(255,255,255,.07)]"><dt class="text-[10px] font-bold uppercase tracking-[.12em] text-slate-400">Country</dt><dd class="mt-1 font-semibold">{{ $targetCountry }}</dd></div>
                    <div class="col-span-2 rounded-lg bg-white/[.065] p-3 shadow-[inset_0_0_0_1px_rgba(255,255,255,.07)]"><dt class="text-[10px] font-bold uppercase tracking-[.12em] text-slate-400">Search Console property</dt><dd class="mt-1 truncate font-semibold" title="{{ $website->searchConsoleSite?->site_url ?? 'Not selected' }}">{{ $website->searchConsoleSite?->site_url ?? 'Not selected' }}</dd></div>
                    <div class="col-span-2 flex items-center gap-2 pt-1 text-xs text-slate-300"><i data-lucide="clock-3" class="h-4 w-4" aria-hidden="true"></i>Last sync {{ $website->gsc_last_synced_at?->diffForHumans() ?? 'never' }}</div>
                </dl>
            </div>
            <div class="flex flex-wrap gap-2 border-t border-white/10 pt-5">
                <form method="POST" action="{{ route('websites.search-console.sync', $website) }}">@csrf<button class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-teal px-4 py-2 text-sm font-semibold text-white shadow-[0_8px_20px_rgba(1,101,118,.28)]" @disabled(! $website->searchConsoleSite || $propertyMismatch)><i data-lucide="refresh-cw" class="h-4 w-4" aria-hidden="true"></i>Sync search data</button></form>
                <form method="POST" action="{{ route('websites.ai-insights.store', $website) }}">@csrf<button class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-white/[.09] px-4 py-2 text-sm font-semibold text-white shadow-[inset_0_0_0_1px_rgba(255,255,255,.14)] hover:bg-white/[.14]"><i data-lucide="sparkles" class="h-4 w-4" aria-hidden="true"></i>Generate AI insight</button></form>
                <form method="POST" action="{{ route('websites.audit', $website) }}">@csrf<button class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-white/[.09] px-4 py-2 text-sm font-semibold text-white shadow-[inset_0_0_0_1px_rgba(255,255,255,.14)] hover:bg-white/[.14]"><i data-lucide="scan-search" class="h-4 w-4" aria-hidden="true"></i>Run SEO scan</button></form>
                <a href="#search-console-setup" class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-white/[.09] px-4 py-2 text-sm font-semibold text-white shadow-[inset_0_0_0_1px_rgba(255,255,255,.14)] hover:bg-white/[.14]"><i data-lucide="settings-2" class="h-4 w-4" aria-hidden="true"></i>Manage GSC</a>
                @if($website->gsc_last_synced_at)<form method="POST" action="{{ route('websites.search-console.reset', $website) }}" class="sm:ml-auto" onsubmit="return confirm('Reset synced Search Console metrics and growth opportunities for this website? Existing tasks will be kept.');">@csrf<button class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-rose-400/10 px-4 py-2 text-sm font-semibold text-rose-200 shadow-[inset_0_0_0_1px_rgba(251,113,133,.24)] hover:bg-rose-400/20"><i data-lucide="rotate-ccw" class="h-4 w-4" aria-hidden="true"></i>Reset analysis</button></form>@endif
            </div>
        </div>
    </section>

    <section id="search-console-setup" class="mt-6 scroll-mt-24 rounded-lg bg-white p-5 shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)]">
        <div class="flex flex-wrap items-start justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-wide text-teal">Workspace goal</p><h2 class="mt-1 text-lg font-semibold text-navy">{{ $goalProfile['label'] }}</h2><p class="mt-1 text-sm text-slate-500">The main action is <span class="font-semibold text-navy">{{ $goalProfile['primary_action_label'] }}</span>.</p></div><a href="{{ route('websites.edit', $website) }}" class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-teal shadow-[0_0_0_1px_rgba(1,101,118,0.18)]"><i data-lucide="settings-2" class="size-4"></i>Edit goal</a></div>
        <dl class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Target audience</dt><dd class="mt-1 text-sm font-semibold text-navy">{{ $goalProfile['target_audience'] ?: 'Not configured' }}</dd></div>
            <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Business model</dt><dd class="mt-1 text-sm font-semibold text-navy">{{ $goalProfile['business_model'] ?: 'Not configured' }}</dd></div>
            <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Supporting conversions</dt><dd class="mt-1 text-sm font-semibold text-navy">{{ collect($goalProfile['secondary_conversion_goals'])->map(fn ($goal) => $goalProfile['conversion_labels'][$goal] ?? str_replace('_', ' ', $goal))->implode(', ') ?: 'None configured' }}</dd></div>
            <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Evidence sources</dt><dd class="mt-1 text-sm font-semibold text-navy">{{ implode(', ', $goalProfile['data_sources']) }}</dd></div>
        </dl>
        <div class="mt-4 flex items-center gap-2 rounded-lg bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800"><i data-lucide="shield-check" class="size-4 shrink-0"></i>Campaigns, messages, and website changes require approval before execution.</div>
    </section>

    <section class="mt-6 rounded-lg bg-white p-5 shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)]">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-navy">Search Console Context</h2>
                <p class="text-sm text-slate-500">Current synced data provenance and fetch status.</p>
            </div>
            <span class="rounded-lg px-3 py-1 text-xs font-semibold {{ $syncStatus === 'success' || $syncStatus === 'Data available' ? 'bg-emerald-50 text-emerald-700' : ($syncStatus === 'failed' ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-700') }}">{{ ucfirst($syncStatus) }}</span>
        </div>
        @if(! $googleAccount)
            <div class="mt-4 flex flex-wrap items-center justify-between gap-4 rounded-lg bg-slate-50 p-4">
                <div><p class="font-semibold text-navy">Connect Google Search Console</p><p class="mt-1 text-sm text-slate-500">Connect the Google account that owns this website property, then return here to select it.</p></div>
                <a href="{{ route('google.search-console.connect') }}" class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-teal px-4 py-2 text-sm font-semibold text-white"><i data-lucide="link" class="size-4"></i>Connect Google</a>
            </div>
        @else
            <div class="mt-4 rounded-lg bg-slate-50 p-4">
                <div class="flex flex-wrap items-start justify-between gap-3"><div><p class="font-semibold text-navy">Select a property for this workspace</p><p class="mt-1 text-sm text-slate-500">Connected account: {{ $googleAccount->email ?: 'Google account' }}. Only properties matching {{ parse_url($website->url, PHP_URL_HOST) }} are shown.</p></div><a href="{{ route('google.search-console.sites') }}" class="inline-flex min-h-9 items-center gap-2 rounded-lg bg-white px-3 text-xs font-semibold text-teal shadow-[0_0_0_1px_rgba(1,101,118,.16)]"><i data-lucide="refresh-cw" class="size-3.5"></i>Refresh properties</a></div>
                @if($matchingSearchConsoleSites->isNotEmpty())
                    <form method="POST" action="{{ route('websites.search-console.assign', $website) }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                        @csrf
                        <label class="grid min-w-0 flex-1 gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Matching property<select name="search_console_site_id" required class="min-h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium normal-case tracking-normal text-navy">@foreach($matchingSearchConsoleSites as $site)<option value="{{ $site->id }}" @selected($website->search_console_site_id === $site->id)>{{ $site->site_url }} · {{ $site->permission_level ?: 'Permission unknown' }}</option>@endforeach</select></label>
                        <button class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-navy px-4 text-sm font-semibold text-white"><i data-lucide="check" class="size-4"></i>{{ $website->searchConsoleSite ? 'Update property' : 'Select property' }}</button>
                    </form>
                @else
                    <div class="mt-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900"><span class="font-semibold">No matching property is loaded.</span> Refresh properties after adding or verifying {{ parse_url($website->url, PHP_URL_HOST) }} in Google Search Console.</div>
                @endif
                @if($website->searchConsoleSite)
                    <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-slate-200 pt-4">
                        @if(! $propertyMismatch)<form method="POST" action="{{ route('websites.search-console.sync', $website) }}">@csrf<button class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-teal px-4 py-2 text-sm font-semibold text-white"><i data-lucide="refresh-cw" class="size-4"></i>Sync last 28 days</button></form>@endif
                        <form method="POST" action="{{ route('websites.search-console.disconnect', $website) }}">@csrf<button class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-rose-700 shadow-[0_0_0_1px_rgba(225,29,72,.16)]"><i data-lucide="unlink" class="size-4"></i>Remove property</button></form>
                    </div>
                @endif
            </div>
        @endif
        @if($propertyMismatch)
            <div class="mt-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900 shadow-[0_0_0_1px_rgba(245,158,11,0.2)]">
                <span class="font-semibold">Search Console property mismatch.</span>
                The selected property <span class="break-all font-semibold">{{ $website->searchConsoleSite?->site_url }}</span> does not cover <span class="break-all font-semibold">{{ $website->url }}</span>. Select a matching domain or URL-prefix property before syncing.
            </div>
        @endif
        <dl class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Property used</dt><dd class="mt-1 break-all text-sm font-semibold text-navy">{{ $latestGscSync?->property_url ?? $website->searchConsoleSite?->site_url ?? 'Not selected' }}</dd></div>
            <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Date range</dt><dd class="mt-1 text-sm font-semibold text-navy">{{ $dateRangeLabel }}</dd></div>
            <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Search type</dt><dd class="mt-1 text-sm font-semibold text-navy">{{ ucfirst($latestGscSync?->search_type ?? 'web') }} search</dd></div>
            <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Country filter</dt><dd class="mt-1 text-sm font-semibold text-navy">{{ $latestGscSync?->country_filter ?: 'All countries' }}</dd></div>
            <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Device filter</dt><dd class="mt-1 text-sm font-semibold text-navy">{{ $latestGscSync?->device_filter ?: 'All devices' }}</dd></div>
            <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Rows fetched</dt><dd class="mt-1 text-sm font-semibold text-navy">@if($latestGscSync){{ $latestGscSync->rows_daily }} daily · {{ $latestGscSync->rows_queries }} queries · {{ $latestGscSync->rows_pages }} pages · {{ $latestGscSync->rows_devices }} devices · {{ $latestGscSync->rows_countries }} countries @elseif($hasLegacyMetrics) Legacy metrics available; row counts were not recorded @else No rows fetched @endif</dd></div>
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
            <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Country scope<select name="country_scope" class="min-h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy"><option value="">All countries</option><option value="target" @selected($filters['country_scope'] === 'target')>Target country</option><option value="non_target" @selected($filters['country_scope'] === 'non_target')>Non-target countries</option></select></label>
            <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Country<select name="country" class="min-h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy"><option value="">All countries</option>@foreach($availableCountries as $country)<option value="{{ $country }}" @selected($filters['country'] === $country)>{{ $country }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Device<select name="device" class="min-h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy"><option value="">All devices</option>@foreach(['desktop','mobile','tablet'] as $device)<option value="{{ $device }}" @selected($filters['device'] === $device)>{{ ucfirst($device) }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Intent<select name="query_intent" class="min-h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy"><option value="">All intents</option>@foreach(['service_intent' => 'Offer intent','local_service_intent' => 'Local high intent','condition_intent' => 'Need or problem intent','branded_practitioner' => 'Brand or representative','review_reputation' => 'Reviews and reputation','informational' => 'Informational','competitor' => 'Competitor','irrelevant' => 'Irrelevant','unknown' => 'Unknown'] as $intent => $label)<option value="{{ $intent }}" @selected($filters['query_intent'] === $intent)>{{ $label }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Page type<select name="page_type" class="min-h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy"><option value="">All types</option>@foreach(['homepage' => 'Homepage','service_page' => 'Priority offer page','blog' => 'Content or resource','legal' => 'Legal','unknown' => 'Unknown'] as $type => $label)<option value="{{ $type }}" @selected($filters['page_type'] === $type)>{{ $label }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Category<select name="opportunity_category" class="min-h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy"><option value="">All categories</option>@foreach(['acquisition_growth' => 'Acquisition growth','service_page_growth' => 'Priority page growth','conversion_improvement' => 'Conversion improvement','reputation_conversion' => 'Reputation conversion','branded_visibility' => 'Branded visibility','technical_seo' => 'Technical SEO','low_value' => 'Low value'] as $category => $label)<option value="{{ $category }}" @selected($filters['opportunity_category'] === $category)>{{ $label }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Priority<select name="opportunity_priority" class="min-h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy"><option value="">All priorities</option>@foreach(['high','medium','low'] as $priority)<option value="{{ $priority }}" @selected($filters['opportunity_priority'] === $priority)>{{ ucfirst($priority) }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Status<select name="status" class="min-h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-navy"><option value="">Open opportunities</option>@foreach(['reviewed','in_progress','completed','ignored'] as $status)<option value="{{ $status }}" @selected($filters['status'] === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>@endforeach</select></label>
            <div class="flex items-end gap-2 md:col-span-2 xl:col-span-6"><button class="min-h-10 rounded-lg bg-navy px-4 py-2 text-sm font-semibold text-white transition-transform active:scale-[0.96]">Apply Filters</button><a href="{{ route('websites.show', $website) }}" class="inline-flex min-h-10 items-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-navy shadow-[0_0_0_1px_rgba(5,18,55,0.12)] transition-transform active:scale-[0.96]">Clear</a></div>
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

    @include('agents.partials.website-team', ['latestActions' => $latestTeamActions])

    @include('websites.partials.conversion-tracking', ['summary' => $conversionEventSummary, 'goalProfile' => $goalProfile])

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        @include('websites.partials.growth-opportunities', ['opportunities' => $serviceOpportunities, 'title' => $isAppointmentGoal ? 'Service Growth Opportunities' : 'High-Intent Growth Opportunities', 'empty' => 'No high-intent opportunities match the current filters.'])
        @include('websites.partials.growth-opportunities', ['opportunities' => $brandedOpportunities, 'title' => 'Branded & Reputation Searches', 'empty' => 'No branded or reputation searches match the current filters.'])
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        @include('websites.partials.growth-opportunities', ['opportunities' => $otherOpportunities, 'title' => 'Other Growth Opportunities', 'empty' => 'No additional growth opportunities match the current filters.'])
        @include('websites.partials.conversion-checks', ['checks' => $website->relationLoaded('conversionChecks') ? $website->conversionChecks : collect()])
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        @include('websites.partials.gsc-countries', ['countries' => $countryMetrics, 'targetCountry' => $targetCountry])
        @include('websites.partials.gsc-devices', ['devices' => $deviceMetrics])
    </div>

    <div class="mt-6 grid gap-6">
        @include('websites.partials.gsc-pages', ['pageRecommendations' => $pageRecommendations])
        @include('websites.partials.gsc-queries', ['queryRows' => $queryRows])
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        @include('websites.partials.insights', ['insights' => $website->aiInsights])
        <section class="rounded-lg bg-white shadow-[0_0_0_1px_rgba(5,18,55,0.06),0_16px_40px_rgba(5,18,55,0.08)]">
            <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-navy">Marketing Tasks</h2></div>
            <div class="divide-y divide-slate-100">@forelse($website->marketingTasks as $task)<div class="px-5 py-4"><p class="font-semibold">{{ $task->title }}</p><p class="text-sm text-slate-500">{{ ucfirst($task->priority) }} · {{ str_replace('_',' ', $task->status) }}</p></div>@empty<div class="px-5 py-10 text-center text-sm text-slate-500">No tasks yet.</div>@endforelse</div>
        </section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        @include('websites.partials.audits', ['audits' => $website->seoAudits])
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
