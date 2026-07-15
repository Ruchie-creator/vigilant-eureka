<?php

namespace App\Http\Controllers;

use App\Models\AiInsight;
use App\Models\GrowthOpportunity;
use App\Models\GscCountry;
use App\Models\GscDailyMetric;
use App\Models\GscDevice;
use App\Models\GscSync;
use App\Models\MarketingTask;
use App\Models\SeoAudit;
use App\Models\Website;
use App\Models\WeeklyReport;
use App\Services\ConversionGoalProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ConversionGoalProfileService $goalProfiles): View
    {
        $hasGscTables = Schema::hasTable('gsc_daily_metrics');
        $hasGscDevices = Schema::hasTable('gsc_devices');
        $hasGscCountries = Schema::hasTable('gsc_countries');
        $hasGscSyncs = Schema::hasTable('gsc_syncs');
        $hasGrowth = Schema::hasTable('growth_opportunities');
        $hasGrowthScore = $hasGrowth && Schema::hasColumn('growth_opportunities', 'score');
        $hasGrowthCategory = $hasGrowth && Schema::hasColumn('growth_opportunities', 'opportunity_category');

        $filters = [
            'website_id' => $request->query('website_id'),
            'date_start' => $request->query('date_start'),
            'date_end' => $request->query('date_end'),
            'country' => $request->query('country'),
            'device' => $request->query('device'),
            'intent' => $request->query('intent'),
            'priority' => $request->query('priority'),
            'sync_status' => $request->query('sync_status'),
        ];

        $syncQuery = GscSync::query()
            ->when(filled($filters['website_id']), fn ($query) => $query->where('website_id', $filters['website_id']))
            ->when(filled($filters['date_start']), fn ($query) => $query->where('date_start', '>=', $filters['date_start']))
            ->when(filled($filters['date_end']), fn ($query) => $query->where('date_end', '<=', $filters['date_end']))
            ->when(filled($filters['country']), fn ($query) => $query->where('country_filter', $filters['country']))
            ->when(filled($filters['device']), fn ($query) => $query->where('device_filter', $filters['device']))
            ->when(filled($filters['sync_status']), fn ($query) => $query->where('status', $filters['sync_status']));

        $metrics = $hasGscSyncs ? (clone $syncQuery)
            ->where('status', 'success')
            ->selectRaw('COALESCE(SUM(total_clicks), 0) as clicks, COALESCE(SUM(total_impressions), 0) as impressions, COALESCE(AVG(average_ctr), 0) as ctr, COALESCE(AVG(average_position), 0) as position')
            ->first() : ($hasGscTables ? GscDailyMetric::query()
            ->selectRaw('COALESCE(SUM(clicks), 0) as clicks, COALESCE(SUM(impressions), 0) as impressions, COALESCE(AVG(ctr), 0) as ctr, COALESCE(AVG(position), 0) as position')
            ->first() : (object) ['clicks' => 0, 'impressions' => 0, 'ctr' => 0, 'position' => 0]);

        $dateContext = $this->dateContext($filters, $hasGscSyncs ? (clone $syncQuery)->where('status', 'success')->latest('synced_at')->first() : null);
        $mobileClicks = $hasGscDevices ? (int) GscDevice::query()
            ->where('device', 'mobile')
            ->when(filled($filters['website_id']), fn ($query) => $query->where('website_id', $filters['website_id']))
            ->when(filled($filters['date_start']), fn ($query) => $query->where('date_start', '>=', $filters['date_start']))
            ->when(filled($filters['date_end']), fn ($query) => $query->where('date_end', '<=', $filters['date_end']))
            ->sum('clicks') : 0;

        $growthQuery = GrowthOpportunity::query()
            ->where('status', 'open')
            ->when(filled($filters['website_id']), fn ($query) => $query->where('website_id', $filters['website_id']))
            ->when(filled($filters['intent']), fn ($query) => $query->where('intent', $filters['intent']))
            ->when(filled($filters['priority']), fn ($query) => $query->where('priority', $filters['priority']));

        $latestSync = $hasGscSyncs ? (clone $syncQuery)
            ->when(filled($filters['sync_status']), fn ($query) => $query->where('status', $filters['sync_status']))
            ->latest('synced_at')
            ->first() : null;

        $trendRows = $hasGscTables ? GscDailyMetric::query()
            ->when(filled($filters['website_id']), fn ($query) => $query->where('website_id', $filters['website_id']))
            ->when(filled($filters['date_start']), fn ($query) => $query->where('date', '>=', $filters['date_start']))
            ->when(filled($filters['date_end']), fn ($query) => $query->where('date', '<=', $filters['date_end']))
            ->orderBy('date')
            ->get(['date', 'clicks', 'impressions', 'ctr', 'position']) : collect();

        $deviceRows = $hasGscDevices ? GscDevice::query()
            ->when(filled($filters['website_id']), fn ($query) => $query->where('website_id', $filters['website_id']))
            ->when(filled($filters['date_start']), fn ($query) => $query->where('date_start', '>=', $filters['date_start']))
            ->when(filled($filters['date_end']), fn ($query) => $query->where('date_end', '<=', $filters['date_end']))
            ->selectRaw('device, SUM(clicks) as clicks')
            ->groupBy('device')
            ->orderByDesc('clicks')
            ->get() : collect();

        $countryRows = $hasGscCountries ? GscCountry::query()
            ->when(filled($filters['website_id']), fn ($query) => $query->where('website_id', $filters['website_id']))
            ->when(filled($filters['date_start']), fn ($query) => $query->where('date_start', '>=', $filters['date_start']))
            ->when(filled($filters['date_end']), fn ($query) => $query->where('date_end', '<=', $filters['date_end']))
            ->selectRaw('country, SUM(clicks) as clicks')
            ->groupBy('country')
            ->orderByDesc('clicks')
            ->limit(8)
            ->get() : collect();

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

        $websites = Website::orderBy('name')->get(['id', 'name']);
        $selectedWebsite = filled($filters['website_id']) ? Website::find($filters['website_id']) : null;
        $goalContext = $selectedWebsite
            ? $goalProfiles->forWebsite($selectedWebsite)
            : [
                'label' => $websites->count() === 1 ? $goalProfiles->forWebsite(Website::find($websites->first()->id))['label'] : 'Multiple workspace goals',
                'primary_action_label' => $websites->count() === 1 ? $goalProfiles->forWebsite(Website::find($websites->first()->id))['primary_action_label'] : 'Select a workspace to focus the primary action',
                'data_sources' => ['connected workspace data sources'],
                'approval_required' => ['campaigns', 'messages', 'website_changes'],
            ];
        $countries = $hasGscCountries ? GscCountry::query()->select('country')->distinct()->orderBy('country')->pluck('country') : collect();

        return view('dashboard', [
            'filters' => $filters,
            'websites' => $websites,
            'goalContext' => $goalContext,
            'countries' => $countries,
            'dateContext' => $dateContext,
            'chartData' => $chartData,
            'websiteCount' => $websites->count(),
            'connectedWebsiteCount' => Schema::hasColumn('websites', 'search_console_site_id') ? Website::whereNotNull('search_console_site_id')->count() : 0,
            'totalClicks' => (int) $metrics->clicks,
            'totalImpressions' => (int) $metrics->impressions,
            'averageCtr' => (float) $metrics->ctr,
            'averagePosition' => (float) $metrics->position,
            'mobileClicks' => $mobileClicks,
            'openGrowthOpportunities' => $hasGrowth ? (clone $growthQuery)->count() : 0,
            'pendingConversionTasks' => MarketingTask::whereIn('status', ['pending', 'in_progress'])->count(),
            'lastSyncStatus' => $latestSync?->status ?? 'No sync yet',
            'latestSync' => $latestSync,
            'topConversionPriority' => $hasGrowth ? (clone $growthQuery)->with('website')
                ->when($hasGrowthCategory, fn ($query) => $query->whereIn('opportunity_category', ['acquisition_growth', 'service_page_growth', 'conversion_improvement']))
                ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
                ->when($hasGrowthScore, fn ($query) => $query->orderByDesc('score'))
                ->first() : null,
            'websiteRows' => Website::withCount(['marketingTasks as pending_tasks_count' => fn ($query) => $query->whereIn('status', ['pending', 'in_progress'])])
                ->when(filled($filters['website_id']), fn ($query) => $query->where('id', $filters['website_id']))
                ->when($hasGrowth, fn ($query) => $query->with(['growthOpportunities' => fn ($query) => $query->where('status', 'open')->when($hasGrowthCategory, fn ($query) => $query->whereNotIn('opportunity_category', ['branded_visibility', 'reputation_conversion', 'low_value']))->when($hasGrowthScore, fn ($query) => $query->orderByDesc('score'))->limit(1)]))
                ->latest()
                ->get()
                ->map(function (Website $website) use ($hasGscTables, $hasGscSyncs, $hasGrowth, $goalProfiles) {
                    $summary = $hasGscSyncs ? $website->gscSyncs()->where('status', 'success')->latest('synced_at')->first() : null;
                    $summary ??= $hasGscTables ? $website->gscDailyMetrics()
                        ->where('date', '>=', now()->subDays(28)->toDateString())
                        ->selectRaw('COALESCE(SUM(clicks), 0) as clicks, COALESCE(SUM(impressions), 0) as impressions, COALESCE(AVG(ctr), 0) as ctr, COALESCE(AVG(position), 0) as position')
                        ->first() : (object) ['clicks' => 0, 'impressions' => 0, 'ctr' => 0, 'position' => 0];

                    return [
                        'website' => $website,
                        'clicks' => (int) ($summary->total_clicks ?? $summary->clicks ?? 0),
                        'impressions' => (int) ($summary->total_impressions ?? $summary->impressions ?? 0),
                        'ctr' => (float) ($summary->average_ctr ?? $summary->ctr ?? 0),
                        'position' => (float) ($summary->average_position ?? $summary->position ?? 0),
                        'top_opportunity' => $hasGrowth ? $website->growthOpportunities->first() : null,
                        'pending_tasks' => $website->pending_tasks_count,
                        'sync_context' => $summary instanceof GscSync ? $summary : null,
                        'goal_profile' => $goalProfiles->forWebsite($website),
                    ];
                }),
            'latestAudits' => SeoAudit::with('website')->latest()->limit(5)->get(),
            'openTasks' => MarketingTask::with('website')->whereIn('status', ['pending', 'in_progress'])->latest()->limit(5)->get(),
            'latestInsights' => AiInsight::with('website')->latest()->limit(5)->get(),
            'latestReport' => WeeklyReport::latest('week_end')->first(),
        ]);
    }

    private function dateContext(array $filters, ?GscSync $latestSync): string
    {
        if (filled($filters['date_start']) || filled($filters['date_end'])) {
            return ($filters['date_start'] ?: 'Any start').' - '.($filters['date_end'] ?: 'Any end');
        }

        if ($latestSync) {
            return $latestSync->date_start->format('M j, Y').' - '.$latestSync->date_end->format('M j, Y');
        }

        return 'No synced date range';
    }
}
