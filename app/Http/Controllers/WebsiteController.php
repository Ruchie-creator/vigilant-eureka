<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\AgentAction;
use App\Models\GoogleAccount;
use App\Models\GscSync;
use App\Services\SafeUrl;
use App\Services\GrowthOpportunityGenerator;
use App\Services\SearchConsolePropertyMatcher;
use App\Services\ConversionGoalProfileService;
use App\Services\ConversionCheckService;
use App\Services\Agents\AgentMemoryService;
use App\Services\Agents\AgentScheduleService;
use App\Models\Agent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class WebsiteController extends Controller
{
    public function index(ConversionGoalProfileService $goalProfiles): View
    {
        $websites = Website::withCount(['seoAudits', 'aiInsights', 'marketingTasks'])->latest()->paginate(12);
        $websites->getCollection()->each(fn (Website $website) => $website->setAttribute('goal_profile', $goalProfiles->forWebsite($website)));

        return view('websites.index', [
            'websites' => $websites,
        ]);
    }

    public function create(ConversionGoalProfileService $goalProfiles): View
    {
        return view('websites.form', ['website' => new Website(), 'goalProfiles' => $goalProfiles->profiles()]);
    }

    public function store(Request $request, ConversionGoalProfileService $goalProfiles, AgentScheduleService $schedules): RedirectResponse
    {
        $data = $this->validated($request, $goalProfiles);
        SafeUrl::assertPublicHttpUrl($data['url']);
        $website = Website::create($data);
        $schedules->createDefaultSchedules($website);

        return redirect()->route('websites.show', $website)->with('success', 'Workspace added. Select a matching Search Console property to begin syncing.');
    }

    public function show(Request $request, Website $website, GrowthOpportunityGenerator $classifier, ConversionGoalProfileService $goalProfiles): View
    {
        $hasGscTables = Schema::hasTable('gsc_daily_metrics') && Schema::hasTable('gsc_queries') && Schema::hasTable('gsc_pages') && Schema::hasTable('gsc_devices');
        $hasGrowth = Schema::hasTable('growth_opportunities');
        $hasGrowthScore = $hasGrowth && Schema::hasColumn('growth_opportunities', 'score');
        $hasGrowthCategory = $hasGrowth && Schema::hasColumn('growth_opportunities', 'opportunity_category');
        $hasGscSyncs = Schema::hasTable('gsc_syncs');
        $hasGscCountries = Schema::hasTable('gsc_countries');
        $hasConversionChecks = Schema::hasTable('conversion_checks');
        $hasConversionEvents = Schema::hasTable('conversion_events');

        $filters = [
            'date_start' => $request->query('date_start'),
            'date_end' => $request->query('date_end'),
            'country' => $request->query('country'),
            'device' => $request->query('device'),
            'query_intent' => $request->query('query_intent'),
            'page_type' => $request->query('page_type'),
            'country_scope' => $request->query('country_scope'),
            'opportunity_category' => $request->query('opportunity_category'),
            'opportunity_priority' => $request->query('opportunity_priority'),
            'status' => $request->query('status'),
        ];
        $opportunityStatus = $filters['status'] ?: 'open';
        $goalProfile = $goalProfiles->forWebsite($website);

        $mobileClicks = $hasGscTables ? (int) $website->gscDevices()->where('device', 'mobile')->latest()->value('clicks') : 0;
        $topPriority = $hasGrowth
            ? $website->growthOpportunities()
                ->where('status', $opportunityStatus)
                ->when($hasGrowthCategory, fn ($query) => $query->whereIn('opportunity_category', ['acquisition_growth', 'service_page_growth', 'conversion_improvement']))
                ->when(filled($filters['opportunity_category']) && $hasGrowthCategory, fn ($query) => $query->where('opportunity_category', $filters['opportunity_category']))
                ->when(filled($filters['opportunity_priority']), fn ($query) => $query->where('priority', $filters['opportunity_priority']))
                ->when($hasConversionEvents, fn ($query) => $query->withCount('conversionEvents')->withMax('conversionEvents', 'occurred_at'))
                ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
                ->when($hasGrowthScore, fn ($query) => $query->orderByDesc('score'))
                ->first()
            : null;

        $relations = [
            'searchConsoleSite',
            'seoAudits' => fn ($query) => $query->latest()->limit(8),
            'aiInsights' => fn ($query) => $query->whereIn('status', ['new', 'reviewed'])->whereNotNull('insight_key')->latest()->limit(3),
            'marketingTasks' => fn ($query) => $query->latest()->limit(8),
        ];

        if ($hasGscSyncs) {
            $relations[] = 'latestGscSync';
        }

        if ($hasGscTables) {
            $relations['gscQueries'] = fn ($query) => $query->orderByDesc('impressions')->limit(5);
            $relations['gscPages'] = fn ($query) => $query->orderByDesc('impressions')->limit(5);
            $relations['gscDevices'] = fn ($query) => $query->latest()->limit(10);
        }

        if ($hasConversionChecks) {
            $relations['conversionChecks'] = fn ($query) => $query->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")->limit(10);
        }

        $website->load($relations);
        $latestSync = $hasGscSyncs ? $this->matchingSync($website, $filters) : null;
        $gscSummary = $latestSync ?: ($hasGscTables ? $website->gscDailyMetrics()
            ->where('date', '>=', now()->subDays(28)->toDateString())
            ->selectRaw('COALESCE(SUM(clicks), 0) as clicks, COALESCE(SUM(impressions), 0) as impressions, COALESCE(AVG(ctr), 0) as ctr, COALESCE(AVG(position), 0) as position')
            ->first() : (object) ['clicks' => 0, 'impressions' => 0, 'ctr' => 0, 'position' => 0]);

        $propertyMismatch = $website->searchConsoleSite
            ? ! SearchConsolePropertyMatcher::matches($website->url, $website->searchConsoleSite->site_url)
            : false;

        $displayOpportunities = $hasGrowth ? $website->growthOpportunities()
            ->where('status', $opportunityStatus)
            ->when($hasGrowthCategory, fn ($query) => $query->where(fn ($categoryQuery) => $categoryQuery
                ->whereNull('opportunity_category')
                ->orWhere('opportunity_category', '!=', 'low_value')))
            ->when(filled($filters['opportunity_category']) && $hasGrowthCategory, fn ($query) => $query->where('opportunity_category', $filters['opportunity_category']))
            ->when(filled($filters['opportunity_priority']), fn ($query) => $query->where('priority', $filters['opportunity_priority']))
            ->when($hasConversionEvents, fn ($query) => $query->withCount('conversionEvents')->withMax('conversionEvents', 'occurred_at'))
            ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
            ->when($hasGrowthScore, fn ($query) => $query->orderByDesc('score'))
            ->limit(5)
            ->get() : collect();

        $serviceCategories = ['acquisition_growth', 'service_page_growth'];
        $brandedCategories = ['branded_visibility', 'reputation_conversion'];
        $serviceOpportunities = $displayOpportunities->whereIn('opportunity_category', $serviceCategories)->values();
        $brandedOpportunities = $displayOpportunities->whereIn('opportunity_category', $brandedCategories)->values();
        $otherOpportunities = $displayOpportunities
            ->whereNotIn('opportunity_category', [...$serviceCategories, ...$brandedCategories])
            ->values();

        $queries = $hasGscTables ? $website->gscQueries()
            ->when($latestSync, fn ($query) => $query->where('date_start', $latestSync->date_start->toDateString())->where('date_end', $latestSync->date_end->toDateString()))
            ->when(! $latestSync && filled($filters['date_start']), fn ($query) => $query->where('date_start', $filters['date_start']))
            ->when(! $latestSync && filled($filters['date_end']), fn ($query) => $query->where('date_end', $filters['date_end']))
            ->orderByDesc('impressions')
            ->limit(25)
            ->get()
            ->filter(fn ($query) => blank($filters['query_intent']) || $classifier->classifyQueryIntent($query->query, $website) === $filters['query_intent'])
            ->take(5)
            ->values() : collect();

        $queryIntents = $queries->mapWithKeys(function ($query) use ($website, $classifier) {
            $intent = $classifier->classifyQueryIntent($query->query, $website);

            return [$query->id => [
                'intent' => $intent,
                'category' => $classifier->opportunityCategoryForIntent($intent),
            ]];
        });

        $pageRows = $hasGscTables ? $website->gscPages()
            ->when($latestSync, fn ($query) => $query->where('date_start', $latestSync->date_start->toDateString())->where('date_end', $latestSync->date_end->toDateString()))
            ->when(! $latestSync && filled($filters['date_start']), fn ($query) => $query->where('date_start', $filters['date_start']))
            ->when(! $latestSync && filled($filters['date_end']), fn ($query) => $query->where('date_end', $filters['date_end']))
            ->orderByDesc('clicks')
            ->limit(25)
            ->get()
            ->filter(fn ($page) => blank($filters['page_type']) || $classifier->pageType($page->page_url, $website) === $filters['page_type'])
            ->take(5)
            ->values() : collect();

        $availableCountries = $hasGscCountries ? $website->gscCountries()
            ->select('country')
            ->distinct()
            ->orderBy('country')
            ->pluck('country') : collect();

        $dateScopedPages = $hasGscTables ? $website->gscPages()
            ->when($latestSync, fn ($query) => $query->where('date_start', $latestSync->date_start->toDateString())->where('date_end', $latestSync->date_end->toDateString()))
            ->when(! $latestSync && filled($filters['date_start']), fn ($query) => $query->where('date_start', $filters['date_start']))
            ->when(! $latestSync && filled($filters['date_end']), fn ($query) => $query->where('date_end', $filters['date_end']))
            ->get() : collect();

        $dateScopedQueries = $hasGscTables ? $website->gscQueries()
            ->when($latestSync, fn ($query) => $query->where('date_start', $latestSync->date_start->toDateString())->where('date_end', $latestSync->date_end->toDateString()))
            ->when(! $latestSync && filled($filters['date_start']), fn ($query) => $query->where('date_start', $filters['date_start']))
            ->when(! $latestSync && filled($filters['date_end']), fn ($query) => $query->where('date_end', $filters['date_end']))
            ->get() : collect();

        $servicePageClicks = $dateScopedPages
            ->filter(fn ($page) => $classifier->pageType($page->page_url, $website) === 'service_page')
            ->sum('clicks');

        $brandedClicks = $dateScopedQueries
            ->filter(fn ($query) => in_array($classifier->classifyQueryIntent($query->query, $website), ['branded_practitioner', 'review_reputation'], true))
            ->sum('clicks');

        $topDevice = $hasGscTables ? $website->gscDevices()
            ->when($latestSync, fn ($query) => $query->where('date_start', $latestSync->date_start->toDateString())->where('date_end', $latestSync->date_end->toDateString()))
            ->orderByDesc('clicks')
            ->first() : null;

        $topCountry = $hasGscCountries ? $website->gscCountries()
            ->when($latestSync, fn ($query) => $query->where('date_start', $latestSync->date_start->toDateString())->where('date_end', $latestSync->date_end->toDateString()))
            ->orderByDesc('clicks')
            ->first() : null;

        $trendRows = $hasGscTables ? $website->gscDailyMetrics()
            ->when($latestSync, fn ($query) => $query->whereBetween('date', [$latestSync->date_start->toDateString(), $latestSync->date_end->toDateString()]))
            ->when(! $latestSync && filled($filters['date_start']), fn ($query) => $query->where('date', '>=', $filters['date_start']))
            ->when(! $latestSync && filled($filters['date_end']), fn ($query) => $query->where('date', '<=', $filters['date_end']))
            ->orderBy('date')
            ->get(['date', 'clicks', 'impressions', 'ctr', 'position']) : collect();

        $dataDateStart = $latestSync?->date_start ?? $trendRows->first()?->date;
        $dataDateEnd = $latestSync?->date_end ?? $trendRows->last()?->date;

        $deviceRows = $hasGscTables ? $website->gscDevices()
            ->when($latestSync, fn ($query) => $query->where('date_start', $latestSync->date_start->toDateString())->where('date_end', $latestSync->date_end->toDateString()))
            ->selectRaw('device, SUM(clicks) as clicks, SUM(impressions) as impressions, AVG(ctr) as ctr, AVG(position) as position')
            ->groupBy('device')
            ->orderByDesc('clicks')
            ->get() : collect();

        $countryRows = $hasGscCountries ? $website->gscCountries()
            ->when($latestSync, fn ($query) => $query->where('date_start', $latestSync->date_start->toDateString())->where('date_end', $latestSync->date_end->toDateString()))
            ->selectRaw('country, SUM(clicks) as clicks, SUM(impressions) as impressions, AVG(ctr) as ctr, AVG(position) as position')
            ->groupBy('country')
            ->orderByDesc('clicks')
            ->get() : collect();

        $profile = $website->serviceProfile();
        $targetCountry = $this->targetCountryLabel($website);
        $targetCountryCodes = $this->targetCountryCodes($targetCountry);

        $chartData = [
            'trend' => [
                'labels' => $trendRows->map(fn ($row) => $row->date->format('M j'))->values(),
                'clicks' => $trendRows->pluck('clicks')->values(),
                'impressions' => $trendRows->pluck('impressions')->values(),
                'ctr' => $trendRows->map(fn ($row) => round((float) $row->ctr, 2))->values(),
                'position' => $trendRows->map(fn ($row) => round((float) $row->position, 1))->values(),
            ],
            'devices' => [
                'labels' => $deviceRows->pluck('device')->map(fn ($device) => ucfirst((string) $device))->values(),
                'clicks' => $deviceRows->pluck('clicks')->map(fn ($clicks) => (int) $clicks)->values(),
            ],
            'countries' => [
                'labels' => $countryRows->pluck('country')->values(),
                'clicks' => $countryRows->pluck('clicks')->map(fn ($clicks) => (int) $clicks)->values(),
            ],
        ];

        $countryMetrics = $countryRows
            ->filter(fn ($row) => blank($filters['country']) || strtoupper((string) $row->country) === strtoupper((string) $filters['country']))
            ->filter(function ($row) use ($filters, $targetCountryCodes) {
                if ($filters['country_scope'] === 'target') {
                    return $this->isTargetCountry((string) $row->country, $targetCountryCodes);
                }

                if ($filters['country_scope'] === 'non_target') {
                    return ! $this->isTargetCountry((string) $row->country, $targetCountryCodes);
                }

                return true;
            })
            ->take(5)
            ->map(fn ($row) => [
                'country' => $row->country,
                'clicks' => (int) $row->clicks,
                'impressions' => (int) $row->impressions,
                'ctr' => (float) $row->ctr,
                'position' => (float) $row->position,
                'is_target' => $this->isTargetCountry((string) $row->country, $targetCountryCodes),
                'recommendation' => $this->countryRecommendation($website, (string) $row->country, $this->isTargetCountry((string) $row->country, $targetCountryCodes), (int) $row->clicks),
            ]);

        $deviceMetrics = $deviceRows->take(5)->map(fn ($row) => [
            'device' => $row->device,
            'clicks' => (int) $row->clicks,
            'impressions' => (int) $row->impressions,
            'ctr' => (float) $row->ctr,
            'position' => (float) $row->position,
            'recommendation' => $this->deviceRecommendation($website, (string) $row->device, (float) $row->ctr, (float) $row->position),
        ]);

        $pageRecommendations = $pageRows->map(fn ($page) => [
            'page' => $page,
            'page_type' => $classifier->pageType($page->page_url, $website),
            'is_priority_service_page' => $classifier->isPriorityServicePage($website, $page->page_url),
            'top_country' => $topCountry?->country,
            'top_device' => $topDevice?->device,
            'recommendation' => $classifier->conversionRecommendationForPage($page, $website),
        ]);

        $queryRows = $queries->map(function ($query) use ($website, $classifier, $dateScopedPages) {
            $intent = $classifier->classifyQueryIntent($query->query, $website);
            $relatedPage = $classifier->mapQueryToPage($query->query, $dateScopedPages, $website);

            return [
                'query' => $query,
                'intent' => $intent,
                'category' => $classifier->opportunityCategoryForIntent($intent),
                'related_page' => $relatedPage?->page_url,
                'recommendation' => $this->queryRecommendationForDisplay($website, $intent, $relatedPage?->page_url),
            ];
        });

        $conversionEventSummary = [
            'total' => 0,
            'attributed' => 0,
            'last_event_at' => null,
            'script_url' => $website->tracking_key ? route('conversion-tracking.script', $website->tracking_key) : null,
            'install_tag' => $website->tracking_key ? '<script async src="'.route('conversion-tracking.script', $website->tracking_key).'"></script>' : null,
            'is_local' => str_contains((string) config('app.url'), 'localhost') || str_contains((string) config('app.url'), '127.0.0.1'),
            'example_opportunity_id' => $topPriority?->id,
            'event_labels' => $goalProfile['conversion_labels'],
        ];

        if ($hasConversionEvents) {
            $recentEvents = $website->conversionEvents()->where('occurred_at', '>=', now()->subDays(30));
            $conversionEventSummary['total'] = (clone $recentEvents)->count();
            $conversionEventSummary['attributed'] = (clone $recentEvents)->whereNotNull('growth_opportunity_id')->count();
            $conversionEventSummary['last_event_at'] = $website->conversionEvents()->latest('occurred_at')->first()?->occurred_at;
        }

        $latestTeamActions = AgentAction::with(['run.agent', 'createdTask'])
            ->where('website_id', $website->id)
            ->latest()
            ->get()
            ->unique(fn ($action) => $action->run->agent->slug)
            ->keyBy(fn ($action) => $action->run->agent->slug);

        $googleAccount = GoogleAccount::with('sites')->where('user_id', Auth::id())->where('provider', 'google')->first();
        $matchingSearchConsoleSites = $googleAccount?->sites
            ->filter(fn ($site) => SearchConsolePropertyMatcher::matches($website->url, $site->site_url))
            ->sortBy('site_url')
            ->values() ?? collect();

        return view('websites.show', [
            'website' => $website,
            'googleAccount' => $googleAccount,
            'matchingSearchConsoleSites' => $matchingSearchConsoleSites,
            'gscSummary' => (object) [
                'clicks' => $latestSync ? $latestSync->total_clicks : ($gscSummary->clicks ?? 0),
                'impressions' => $latestSync ? $latestSync->total_impressions : ($gscSummary->impressions ?? 0),
                'ctr' => $latestSync ? $latestSync->average_ctr : ($gscSummary->ctr ?? 0),
                'position' => $latestSync ? $latestSync->average_position : ($gscSummary->position ?? 0),
            ],
            'latestGscSync' => $latestSync,
            'availableCountries' => $availableCountries,
            'filters' => $filters,
            'propertyMismatch' => $propertyMismatch,
            'mobileClicks' => $mobileClicks,
            'topCountry' => $topCountry,
            'topDevice' => $topDevice,
            'servicePageClicks' => (int) $servicePageClicks,
            'brandedClicks' => (int) $brandedClicks,
            'targetCountry' => $targetCountry,
            'targetLocation' => $this->targetLocationLabel($website),
            'dataDateStart' => $dataDateStart,
            'dataDateEnd' => $dataDateEnd,
            'countryMetrics' => $countryMetrics,
            'deviceMetrics' => $deviceMetrics,
            'chartData' => $chartData,
            'openConversionOpportunities' => $hasGrowth ? $website->growthOpportunities()->where('status', 'open')->where('opportunity_category', 'conversion_improvement')->count() : 0,
            'topPriority' => $topPriority,
            'pageRecommendations' => $pageRecommendations,
            'filteredQueries' => $queries,
            'queryRows' => $queryRows,
            'queryIntents' => $queryIntents,
            'serviceOpportunities' => $serviceOpportunities,
            'brandedOpportunities' => $brandedOpportunities,
            'otherOpportunities' => $otherOpportunities,
            'conversionEventSummary' => $conversionEventSummary,
            'goalProfile' => $goalProfile,
            'latestTeamActions' => $latestTeamActions,
        ]);
    }

    private function matchingSync(Website $website, array $filters): ?GscSync
    {
        return $website->gscSyncs()
            ->where('status', 'success')
            ->when(filled($filters['date_start']), fn ($query) => $query->where('date_start', $filters['date_start']))
            ->when(filled($filters['date_end']), fn ($query) => $query->where('date_end', $filters['date_end']))
            ->when(filled($filters['country']), fn ($query) => $query->where('country_filter', $filters['country']))
            ->when(filled($filters['device']), fn ($query) => $query->where('device_filter', $filters['device']))
            ->latest('synced_at')
            ->first();
    }

    private function targetCountryLabel(Website $website): string
    {
        $locations = collect($website->serviceProfile()['target_locations'] ?? [])->map(fn ($location) => strtolower((string) $location));

        if ($locations->contains(fn ($location) => str_contains($location, 'suisse') || str_contains($location, 'switzerland') || str_contains($location, 'geneve') || str_contains($location, 'geneva'))) {
            return 'Switzerland';
        }

        if ($locations->contains(fn ($location) => str_contains($location, 'france') || str_contains($location, 'lyon'))) {
            return 'France';
        }

        return 'Not set';
    }

    private function targetCountryCodes(string $targetCountry): array
    {
        return match ($targetCountry) {
            'France' => ['france', 'fra', 'fr'],
            'Switzerland' => ['switzerland', 'suisse', 'che', 'ch'],
            default => [],
        };
    }

    private function targetLocationLabel(Website $website): string
    {
        $locations = collect($website->serviceProfile()['target_locations'] ?? [])
            ->push($website->target_location)
            ->filter()
            ->map(fn ($location) => strtolower((string) $location));

        if ($locations->contains(fn ($location) => str_contains($location, 'geneve') || str_contains($location, 'geneva') || str_contains($location, 'suisse') || str_contains($location, 'switzerland'))) {
            return 'Geneva / Switzerland';
        }

        if ($locations->contains(fn ($location) => str_contains($location, 'lyon') || str_contains($location, 'france'))) {
            return 'Lyon / France';
        }

        return $website->target_location ?: 'Not set';
    }

    private function isTargetCountry(string $country, array $targetCountryCodes): bool
    {
        $country = strtolower($country);

        return in_array($country, $targetCountryCodes, true);
    }

    private function countryRecommendation(Website $website, string $country, bool $isTarget, int $clicks): string
    {
        $goal = app(ConversionGoalProfileService::class)->forWebsite($website);

        if ($isTarget) {
            return $clicks > 0
                ? 'Protect target-market visibility and connect priority pages to '.$goal['primary_action_label'].'.'
                : 'Improve target-market relevance on priority pages and strengthen the '.$goal['journey_label'].'.';
        }

        return 'Review whether this country supports the configured audience; avoid letting non-target traffic distract from '.$goal['primary_action_label'].'.';
    }

    private function deviceRecommendation(Website $website, string $device, float $ctr, float $position): string
    {
        $device = strtolower($device);
        $goal = app(ConversionGoalProfileService::class)->forWebsite($website);

        if ($device === 'mobile') {
            return 'Prioritize a fast mobile '.$goal['cta_label'].' and a short, low-friction '.$goal['journey_label'].'.';
        }

        if ($ctr < 2 && $position <= 12) {
            return 'Improve titles/meta and ensure the '.$goal['journey_label'].' is visible above the fold.';
        }

        return 'Keep the conversion path clear and verify configured conversion events are tracked.';
    }

    private function queryRecommendationForDisplay(Website $website, string $intent, ?string $relatedPage): string
    {
        $goal = app(ConversionGoalProfileService::class)->forWebsite($website);

        if (in_array($intent, ['branded_practitioner', 'review_reputation'], true)) {
            return 'Strengthen trust, credentials, review visibility, and the '.$goal['cta_label'].' on the brand or representative path.';
        }

        if (in_array($intent, ['service_intent', 'local_service_intent', 'condition_intent'], true)) {
            return $relatedPage
                ? 'Improve the related priority page title/meta, audience-intent copy, internal links, and '.$goal['cta_label'].'.'
                : 'Map this query to a relevant priority page and add a clear '.$goal['journey_label'].'.';
        }

        return 'Review intent fit before prioritizing; do not let low-value searches dominate growth work.';
    }

    public function edit(Website $website, ConversionGoalProfileService $goalProfiles): View
    {
        return view('websites.form', ['website' => $website, 'goalProfiles' => $goalProfiles->profiles()]);
    }

    public function update(Request $request, Website $website, ConversionGoalProfileService $goalProfiles, ConversionCheckService $conversionChecks, AgentMemoryService $memories): RedirectResponse
    {
        $data = $this->validated($request, $goalProfiles);
        SafeUrl::assertPublicHttpUrl($data['url']);
        $goalChanged = $website->primary_conversion_goal !== $data['primary_conversion_goal'];
        $website->update($data);

        if ($goalChanged) {
            $website->conversionChecks()->delete();
            $conversionChecks->ensureDefaults($website);
            foreach (Agent::where('status', 'active')->get() as $agent) {
                $memories->updateOrRemember($agent, $website, 'conversion_goal_context', 'primary-conversion-goal', 'The primary conversion goal is '.$data['primary_conversion_goal'].'.', ['confidence' => 1, 'source_type' => 'workspace', 'source_id' => $website->id]);
            }
        }

        return redirect()->route('websites.show', $website)->with('success', 'Workspace updated.');
    }

    public function gscQueries(Website $website, GrowthOpportunityGenerator $classifier): View
    {
        $queries = $website->gscQueries()->orderByDesc('impressions')->paginate(50);
        $pages = $website->gscPages()->get();
        $queryRows = $queries->getCollection()->map(function ($query) use ($website, $classifier, $pages) {
            $intent = $classifier->classifyQueryIntent($query->query, $website);
            $relatedPage = $classifier->mapQueryToPage($query->query, $pages, $website);

            return [
                'query' => $query,
                'intent' => $intent,
                'category' => $classifier->opportunityCategoryForIntent($intent),
                'related_page' => $relatedPage?->page_url,
                'recommendation' => $this->queryRecommendationForDisplay($website, $intent, $relatedPage?->page_url),
            ];
        });

        return view('websites.gsc-queries-index', compact('website', 'queries', 'queryRows'));
    }

    public function gscPages(Website $website, GrowthOpportunityGenerator $classifier): View
    {
        $pages = $website->gscPages()->orderByDesc('impressions')->paginate(50);
        $topCountry = $website->gscCountries()->orderByDesc('clicks')->first();
        $topDevice = $website->gscDevices()->orderByDesc('clicks')->first();
        $pageRecommendations = $pages->getCollection()->map(fn ($page) => [
            'page' => $page,
            'page_type' => $classifier->pageType($page->page_url, $website),
            'is_priority_service_page' => $classifier->isPriorityServicePage($website, $page->page_url),
            'top_country' => $topCountry?->country,
            'top_device' => $topDevice?->device,
            'recommendation' => $classifier->conversionRecommendationForPage($page, $website),
        ]);

        return view('websites.gsc-pages-index', compact('website', 'pages', 'pageRecommendations'));
    }

    public function gscCountries(Website $website): View
    {
        $targetCountry = $this->targetCountryLabel($website);
        $targetCountryCodes = $this->targetCountryCodes($targetCountry);
        $countries = $website->gscCountries()
            ->orderByDesc('clicks')
            ->paginate(50);

        $countryMetrics = $countries->getCollection()->map(fn ($row) => [
            'country' => $row->country,
            'clicks' => (int) $row->clicks,
            'impressions' => (int) $row->impressions,
            'ctr' => (float) $row->ctr,
            'position' => (float) $row->position,
            'is_target' => $this->isTargetCountry((string) $row->country, $targetCountryCodes),
            'recommendation' => $this->countryRecommendation($website, (string) $row->country, $this->isTargetCountry((string) $row->country, $targetCountryCodes), (int) $row->clicks),
        ]);

        return view('websites.gsc-countries-index', compact('website', 'countries', 'countryMetrics', 'targetCountry'));
    }

    public function gscDevices(Website $website): View
    {
        $devices = $website->gscDevices()
            ->orderByDesc('clicks')
            ->paginate(50);

        $deviceMetrics = $devices->getCollection()->map(fn ($row) => [
            'device' => $row->device,
            'clicks' => (int) $row->clicks,
            'impressions' => (int) $row->impressions,
            'ctr' => (float) $row->ctr,
            'position' => (float) $row->position,
            'recommendation' => $this->deviceRecommendation($website, (string) $row->device, (float) $row->ctr, (float) $row->position),
        ]);

        return view('websites.gsc-devices-index', compact('website', 'devices', 'deviceMetrics'));
    }

    public function growthOpportunities(Request $request, Website $website, ConversionGoalProfileService $goalProfiles): View
    {
        $filters = [
            'priority' => $request->query('priority'),
            'status' => $request->query('status', 'open'),
            'category' => $request->query('category'),
            'source_type' => $request->query('source_type'),
            'source' => $request->query('source'),
        ];

        return view('websites.growth-opportunities-index', [
            'website' => $website,
            'opportunities' => $website->growthOpportunities()
                ->when(filled($filters['status']), fn ($query) => $query->where('status', $filters['status']))
                ->when(filled($filters['priority']), fn ($query) => $query->where('priority', $filters['priority']))
                ->when(filled($filters['category']), fn ($query) => $query->where('opportunity_category', $filters['category']))
                ->when(filled($filters['source_type']), fn ($query) => $query->where('source_type', $filters['source_type']))
                ->when(filled($filters['source']), function ($query) use ($filters) {
                    $term = '%'.$filters['source'].'%';

                    $query->where(function ($query) use ($term) {
                        $query->where('source_value', 'like', $term)
                            ->orWhere('related_page_url', 'like', $term);
                    });
                })
                ->when(Schema::hasTable('conversion_events'), fn ($query) => $query->withCount('conversionEvents')->withMax('conversionEvents', 'occurred_at'))
                ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
                ->orderByDesc('score')
                ->paginate(25)
                ->withQueryString(),
            'filters' => $filters,
            'goalProfile' => $goalProfiles->forWebsite($website),
        ]);
    }

    public function destroy(Website $website): RedirectResponse
    {
        $website->delete();

        return redirect()->route('websites.index')->with('success', 'Website deleted.');
    }

    private function validated(Request $request, ConversionGoalProfileService $goalProfiles): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url:http,https', 'max:2048'],
            'type' => ['required', Rule::in(['professional_services', 'saas', 'ecommerce', 'osteopathy', 'auriculotherapy', 'sexology', 'other'])],
            'language' => ['required', 'string', 'max:40'],
            'timezone' => ['nullable', 'timezone'],
            'target_location' => ['nullable', 'string', 'max:255'],
            'primary_services' => ['nullable', 'string', 'max:5000'],
            'target_locations' => ['nullable', 'string', 'max:5000'],
            'practitioner_names' => ['nullable', 'string', 'max:5000'],
            'brand_terms' => ['nullable', 'string', 'max:5000'],
            'priority_pages' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', Rule::in(['active', 'paused', 'archived'])],
            'primary_conversion_goal' => ['required', Rule::in(array_keys($goalProfiles->profiles()))],
            'secondary_conversion_goals' => ['nullable', 'string', 'max:5000'],
            'target_audience' => ['nullable', 'string', 'max:2000'],
            'business_model' => ['nullable', 'string', 'max:255'],
            'conversion_labels' => ['nullable', 'string', 'max:10000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        foreach (['primary_services', 'target_locations', 'practitioner_names', 'brand_terms', 'priority_pages'] as $field) {
            $data[$field] = $this->parseList($data[$field] ?? '');
        }

        $data['secondary_conversion_goals'] = $this->parseList($data['secondary_conversion_goals'] ?? '');
        $data['conversion_labels'] = $this->parseLabels($data['conversion_labels'] ?? '');
        $data['conversion_labels'] = $data['conversion_labels'] ?: $goalProfiles->labelsFor($data['primary_conversion_goal']);

        return $data;
    }

    private function parseList(?string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $value))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function parseLabels(?string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $value))
            ->map(fn ($line) => array_map('trim', explode('=', $line, 2)))
            ->filter(fn ($parts) => count($parts) === 2 && preg_match('/\A[a-z0-9_-]+\z/i', $parts[0]) && filled($parts[1]))
            ->mapWithKeys(fn ($parts) => [strtolower($parts[0]) => $parts[1]])
            ->all();
    }
}
