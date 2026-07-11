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
            'opportunity_priority' => $request->query('opportunity_priority'),
        ];

        $mobileClicks = $hasGscTables ? (int) $website->gscDevices()->where('device', 'mobile')->latest()->value('clicks') : 0;
        $topPriority = $hasGrowth
            ? $website->growthOpportunities()
                ->where('status', 'open')
                ->when($hasGrowthCategory, fn ($query) => $query->whereIn('opportunity_category', ['acquisition_growth', 'service_page_growth', 'conversion_improvement']))
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
                ->where('status', 'open')
                ->when($hasGrowthCategory, fn ($query) => $query->whereNotIn('opportunity_category', ['branded_visibility', 'reputation_conversion', 'low_value']))
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
            ->where('status', 'open')
            ->when($hasGrowthCategory, fn ($query) => $query->whereIn('opportunity_category', ['acquisition_growth', 'service_page_growth']))
            ->when(filled($filters['opportunity_priority']), fn ($query) => $query->where('priority', $filters['opportunity_priority']))
            ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
            ->when($hasGrowthScore, fn ($query) => $query->orderByDesc('score'))
            ->limit(8)
            ->get() : collect();

        $brandedOpportunities = $hasGrowth ? $website->growthOpportunities()
            ->where('status', 'open')
            ->when($hasGrowthCategory, fn ($query) => $query->whereIn('opportunity_category', ['branded_visibility', 'reputation_conversion']))
            ->when(filled($filters['opportunity_priority']), fn ($query) => $query->where('priority', $filters['opportunity_priority']))
            ->orderByDesc('impressions')
            ->limit(6)
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
            'openConversionOpportunities' => $hasGrowth ? $website->growthOpportunities()->where('status', 'open')->whereIn('opportunity_type', ['improve_booking_cta', 'mobile_conversion', 'increase_ctr_and_conversion'])->count() : 0,
            'topPriority' => $topPriority,
            'pageRecommendations' => $pageRows->map(fn ($page) => [
                'page' => $page,
                'page_type' => $classifier->pageType($page->page_url, $website),
                'is_priority_service_page' => $classifier->isPriorityServicePage($website, $page->page_url),
                'recommendation' => $classifier->conversionRecommendationForPage($page, $website),
            ]),
            'filteredQueries' => $queries,
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
        $queryIntents = $queries->getCollection()->mapWithKeys(function ($query) use ($website, $classifier) {
            $intent = $classifier->classifyQueryIntent($query->query, $website);

            return [$query->id => [
                'intent' => $intent,
                'category' => $classifier->opportunityCategoryForIntent($intent),
            ]];
        });

        return view('websites.gsc-queries-index', compact('website', 'queries', 'queryIntents'));
    }

    public function gscPages(Website $website, GrowthOpportunityGenerator $classifier): View
    {
        $pages = $website->gscPages()->orderByDesc('impressions')->paginate(50);
        $pageRecommendations = $pages->getCollection()->map(fn ($page) => [
            'page' => $page,
            'page_type' => $classifier->pageType($page->page_url, $website),
            'is_priority_service_page' => $classifier->isPriorityServicePage($website, $page->page_url),
            'recommendation' => $classifier->conversionRecommendationForPage($page, $website),
        ]);

        return view('websites.gsc-pages-index', compact('website', 'pages', 'pageRecommendations'));
    }

    public function growthOpportunities(Website $website): View
    {
        return view('websites.growth-opportunities-index', [
            'website' => $website,
            'opportunities' => $website->growthOpportunities()
                ->where('status', 'open')
                ->orderByDesc('score')
                ->paginate(25),
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
