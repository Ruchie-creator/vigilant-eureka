<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\GoogleAccount;
use App\Services\SafeUrl;
use App\Services\GrowthOpportunityGenerator;
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

    public function show(Website $website, GrowthOpportunityGenerator $classifier): View
    {
        $hasGscTables = Schema::hasTable('gsc_daily_metrics') && Schema::hasTable('gsc_queries') && Schema::hasTable('gsc_pages') && Schema::hasTable('gsc_devices');
        $hasGrowth = Schema::hasTable('growth_opportunities');
        $hasGrowthScore = $hasGrowth && Schema::hasColumn('growth_opportunities', 'score');
        $hasConversionChecks = Schema::hasTable('conversion_checks');

        $mobileClicks = $hasGscTables ? (int) $website->gscDevices()->where('device', 'mobile')->latest()->value('clicks') : 0;
        $topPriority = $hasGrowth
            ? $website->growthOpportunities()
                ->where('status', 'open')
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

        if ($hasGscTables) {
            $relations['gscQueries'] = fn ($query) => $query->orderByDesc('clicks')->limit(5);
            $relations['gscPages'] = fn ($query) => $query->orderByDesc('clicks')->limit(5);
            $relations['gscDevices'] = fn ($query) => $query->latest()->limit(10);
        }

        if ($hasGrowth) {
            $relations['growthOpportunities'] = fn ($query) => $query
                ->where('status', 'open')
                ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
                ->when($hasGrowthScore, fn ($query) => $query->orderByDesc('score'))
                ->limit(5);
        }

        if ($hasConversionChecks) {
            $relations['conversionChecks'] = fn ($query) => $query->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")->limit(10);
        }

        return view('websites.show', [
            'website' => $website->load($relations),
            'googleAccount' => GoogleAccount::with('sites')->where('user_id', Auth::id())->where('provider', 'google')->first(),
            'gscSummary' => $hasGscTables ? $website->gscDailyMetrics()
                ->where('date', '>=', now()->subDays(28)->toDateString())
                ->selectRaw('COALESCE(SUM(clicks), 0) as clicks, COALESCE(SUM(impressions), 0) as impressions, COALESCE(AVG(ctr), 0) as ctr, COALESCE(AVG(position), 0) as position')
                ->first() : (object) ['clicks' => 0, 'impressions' => 0, 'ctr' => 0, 'position' => 0],
            'mobileClicks' => $mobileClicks,
            'openConversionOpportunities' => $hasGrowth ? $website->growthOpportunities()->where('status', 'open')->whereIn('opportunity_type', ['improve_booking_cta', 'mobile_conversion', 'increase_ctr_and_conversion'])->count() : 0,
            'topPriority' => $topPriority,
            'pageRecommendations' => $hasGscTables ? $website->gscPages()->orderByDesc('clicks')->limit(5)->get()->map(fn ($page) => [
                'page' => $page,
                'page_type' => $classifier->pageType($page->page_url),
                'recommendation' => $classifier->conversionRecommendationForPage($page),
            ]) : collect(),
            'queryIntents' => $hasGscTables ? $website->gscQueries()->orderByDesc('impressions')->limit(5)->get()->mapWithKeys(fn ($query) => [$query->id => $classifier->classifyQueryIntent($query->query)]) : collect(),
        ]);
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

    public function destroy(Website $website): RedirectResponse
    {
        $website->delete();

        return redirect()->route('websites.index')->with('success', 'Website deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url:http,https', 'max:2048'],
            'type' => ['required', Rule::in(['osteopathy', 'auriculotherapy', 'sexology', 'other'])],
            'language' => ['required', 'string', 'max:40'],
            'target_location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'paused', 'archived'])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
