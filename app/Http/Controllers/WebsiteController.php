<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\GoogleAccount;
use App\Models\GscSync;
use App\Services\SafeUrl;
use App\Services\GrowthOpportunityGenerator;
use App\Services\SearchConsolePropertyMatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class WebsiteController extends Controller
{
    public function index(): View
    {
        return view('websites.index', [
            'websites' => Website::withCount(['seoAudits', 'aiInsights', 'marketingTasks'])->latest()->paginate(12),
        ]);
    }

    public function create(): View
    {
        return view('websites.form', ['website' => new Website()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        SafeUrl::assertPublicHttpUrl($data['url']);
        Website::create($data);

        return redirect()->route('websites.index')->with('success', 'Website added.');
    }

    public function show(Request $request, Website $website, GrowthOpportunityGenerator $classifier): View
    {
        $hasGscTables = Schema::hasTable('gsc_daily_metrics') && Schema::hasTable('gsc_queries') && Schema::hasTable('gsc_pages') && Schema::hasTable('gsc_devices');
        $hasGrowth = Schema::hasTable('growth_opportunities');
        $hasGrowthScore = $hasGrowth && Schema::hasColumn('growth_opportunities', 'score');
        $hasGrowthCategory = $hasGrowth && Schema::hasColumn('growth_opportunities', 'opportunity_category');
        $hasGscSyncs = Schema::hasTable('gsc_syncs');
        $hasGscCountries = Schema::hasTable('gsc_countries');
        $hasConversionChecks = Schema::hasTable('conversion_checks');

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

        $mobileClicks = $hasGscTables ? (int) $website->gscDevices()->where('device', 'mobile')->latest()->value('clicks') : 0;
        $topPriority = $hasGrowth
            ? $website->growthOpportunities()
                ->where('status', $opportunityStatus)
                ->when($hasGrowthCategory, fn ($query) => $query->whereIn('opportunity_category', ['acquisition_growth', 'service_page_growth', 'conversion_improvement']))
                ->when(filled($filters['opportunity_category']) && $hasGrowthCategory, fn ($query) => $query->where('opportunity_category', $filters['opportunity_category']))
                ->when(filled($filters['opportunity_priority']), fn ($query) => $query->where('priority', $filters['opportunity_priority']))
                ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
                ->when($hasGrowthScore, fn ($query) => $query->orderByDesc('score'))
                ->first()
            : null;

        $relations = [
            'searchConsoleSite',
            'seoAudits' => fn ($query) => $query->latest()->limit(8),
            'aiInsights' => fn ($query) => $query->whereIn('status', ['new', 'reviewed'])->latest()->limit(5),
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

        if ($hasGrowth) {
            $relations['growthOpportunities'] = fn ($query) => $query
                ->where('status', $opportunityStatus)
                ->when($hasGrowthCategory, fn ($query) => $query->whereNotIn('opportunity_category', ['branded_visibility', 'reputation_conversion', 'low_value']))
                ->when(filled($filters['opportunity_category']) && $hasGrowthCategory, fn ($query) => $query->where('opportunity_category', $filters['opportunity_category']))
                ->when(filled($filters['opportunity_priority']), fn ($query) => $query->where('priority', $filters['opportunity_priority']))
                ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
                ->when($hasGrowthScore, fn ($query) => $query->orderByDesc('score'))
                ->limit(5);
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

        $serviceOpportunities = $hasGrowth ? $website->growthOpportunities()
            ->where('status', $opportunityStatus)
            ->when($hasGrowthCategory, fn ($query) => $query->whereIn('opportunity_category', ['acquisition_growth', 'service_page_growth']))
            ->when(filled($filters['opportunity_category']) && $hasGrowthCategory, fn ($query) => $query->where('opportunity_category', $filters['opportunity_category']))
            ->when(filled($filters['opportunity_priority']), fn ($query) => $query->where('priority', $filters['opportunity_priority']))
            ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
            ->when($hasGrowthScore, fn ($query) => $query->orderByDesc('score'))
            ->limit(5)
            ->get() : collect();

        $brandedOpportunities = $hasGrowth ? $website->growthOpportunities()
            ->where('status', $opportunityStatus)
            ->when($hasGrowthCategory, fn ($query) => $query->whereIn('opportunity_category', ['branded_visibility', 'reputation_conversion']))
            ->when(filled($filters['opportunity_category']) && $hasGrowthCategory, fn ($query) => $query->where('opportunity_category', $filters['opportunity_category']))
            ->when(filled($filters['opportunity_priority']), fn ($query) => $query->where('priority', $filters['opportunity_priority']))
            ->orderByDesc('impressions')
            ->limit(5)
            ->get() : collect();

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
                'recommendation' => $this->countryRecommendation((string) $row->country, $this->isTargetCountry((string) $row->country, $targetCountryCodes), (int) $row->clicks),
            ]);

        $deviceMetrics = $deviceRows->take(5)->map(fn ($row) => [
            'device' => $row->device,
            'clicks' => (int) $row->clicks,
            'impressions' => (int) $row->impressions,
            'ctr' => (float) $row->ctr,
            'position' => (float) $row->position,
            'recommendation' => $this->deviceRecommendation((string) $row->device, (float) $row->ctr, (float) $row->position),
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
                'recommendation' => $this->queryRecommendationForDisplay($intent, $relatedPage?->page_url),
            ];
        });

        return view('websites.show', [
            'website' => $website,
            'googleAccount' => GoogleAccount::with('sites')->where('user_id', Auth::id())->where('provider', 'google')->first(),
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
            'countryMetrics' => $countryMetrics,
            'deviceMetrics' => $deviceMetrics,
            'chartData' => $chartData,
            'openConversionOpportunities' => $hasGrowth ? $website->growthOpportunities()->where('status', 'open')->whereIn('opportunity_type', ['improve_booking_cta', 'mobile_conversion', 'increase_ctr_and_conversion'])->count() : 0,
            'topPriority' => $topPriority,
            'pageRecommendations' => $pageRecommendations,
            'filteredQueries' => $queries,
            'queryRows' => $queryRows,
            'queryIntents' => $queryIntents,
            'serviceOpportunities' => $serviceOpportunities,
            'brandedOpportunities' => $brandedOpportunities,
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

    private function isTargetCountry(string $country, array $targetCountryCodes): bool
    {
        $country = strtolower($country);

        return in_array($country, $targetCountryCodes, true);
    }

    private function countryRecommendation(string $country, bool $isTarget, int $clicks): string
    {
        if ($isTarget) {
            return $clicks > 0
                ? 'Protect target-country visibility and connect service pages to appointment CTAs.'
                : 'Improve target-country relevance on service pages and local trust signals.';
        }

        return 'Review whether this country is useful; avoid letting non-target traffic distract from appointment growth.';
    }

    private function deviceRecommendation(string $device, float $ctr, float $position): string
    {
        $device = strtolower($device);

        if ($device === 'mobile') {
            return 'Prioritize fast mobile booking CTAs, tap-to-call, and short service-page intros.';
        }

        if ($ctr < 2 && $position <= 12) {
            return 'Improve titles/meta and ensure the appointment path is visible above the fold.';
        }

        return 'Keep the conversion path clear and verify booking interactions are tracked.';
    }

    private function queryRecommendationForDisplay(string $intent, ?string $relatedPage): string
    {
        if (in_array($intent, ['branded_practitioner', 'review_reputation'], true)) {
            return 'Strengthen trust, credentials, review visibility, and the appointment CTA on the About/practitioner path.';
        }

        if (in_array($intent, ['service_intent', 'local_service_intent', 'condition_intent'], true)) {
            return $relatedPage
                ? 'Improve the related service page title/meta, patient-intent copy, internal links, and booking CTA.'
                : 'Map this query to a relevant service page and add a clear appointment path.';
        }

        return 'Review intent fit before prioritizing; do not let low-value searches dominate growth work.';
    }

    public function edit(Website $website): View
    {
        return view('websites.form', compact('website'));
    }

    public function update(Request $request, Website $website): RedirectResponse
    {
        $data = $this->validated($request);
        SafeUrl::assertPublicHttpUrl($data['url']);
        $website->update($data);

        return redirect()->route('websites.show', $website)->with('success', 'Website updated.');
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
                'recommendation' => $this->queryRecommendationForDisplay($intent, $relatedPage?->page_url),
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
            'recommendation' => $this->countryRecommendation((string) $row->country, $this->isTargetCountry((string) $row->country, $targetCountryCodes), (int) $row->clicks),
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
            'recommendation' => $this->deviceRecommendation((string) $row->device, (float) $row->ctr, (float) $row->position),
        ]);

        return view('websites.gsc-devices-index', compact('website', 'devices', 'deviceMetrics'));
    }

    public function growthOpportunities(Request $request, Website $website): View
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
                ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
                ->orderByDesc('score')
                ->paginate(25)
                ->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function destroy(Website $website): RedirectResponse
    {
        $website->delete();

        return redirect()->route('websites.index')->with('success', 'Website deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url:http,https', 'max:2048'],
            'type' => ['required', Rule::in(['osteopathy', 'auriculotherapy', 'sexology', 'other'])],
            'language' => ['required', 'string', 'max:40'],
            'target_location' => ['nullable', 'string', 'max:255'],
            'primary_services' => ['nullable', 'string', 'max:5000'],
            'target_locations' => ['nullable', 'string', 'max:5000'],
            'practitioner_names' => ['nullable', 'string', 'max:5000'],
            'brand_terms' => ['nullable', 'string', 'max:5000'],
            'priority_pages' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', Rule::in(['active', 'paused', 'archived'])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        foreach (['primary_services', 'target_locations', 'practitioner_names', 'brand_terms', 'priority_pages'] as $field) {
            $data[$field] = $this->parseList($data[$field] ?? '');
        }

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
}
