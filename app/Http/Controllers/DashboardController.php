<?php

namespace App\Http\Controllers;

use App\Models\AiInsight;
use App\Models\GrowthOpportunity;
use App\Models\GscDailyMetric;
use App\Models\MarketingTask;
use App\Models\SeoAudit;
use App\Models\Website;
use App\Models\WeeklyReport;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $hasGscTables = Schema::hasTable('gsc_daily_metrics');
        $hasGrowth = Schema::hasTable('growth_opportunities');
        $hasGrowthScore = $hasGrowth && Schema::hasColumn('growth_opportunities', 'score');

        $metrics = $hasGscTables ? GscDailyMetric::query()
            ->selectRaw('COALESCE(SUM(clicks), 0) as clicks, COALESCE(SUM(impressions), 0) as impressions, COALESCE(AVG(ctr), 0) as ctr, COALESCE(AVG(position), 0) as position')
            ->first() : (object) ['clicks' => 0, 'impressions' => 0, 'ctr' => 0, 'position' => 0];

        return view('dashboard', [
            'websiteCount' => Website::count(),
            'connectedWebsiteCount' => Schema::hasColumn('websites', 'search_console_site_id') ? Website::whereNotNull('search_console_site_id')->count() : 0,
            'totalClicks' => (int) $metrics->clicks,
            'totalImpressions' => (int) $metrics->impressions,
            'averageCtr' => (float) $metrics->ctr,
            'averagePosition' => (float) $metrics->position,
            'openGrowthOpportunities' => $hasGrowth ? GrowthOpportunity::where('status', 'open')->count() : 0,
            'pendingConversionTasks' => MarketingTask::whereIn('status', ['pending', 'in_progress'])->where('title', 'like', '%booking%')->count(),
            'topConversionPriority' => $hasGrowth ? GrowthOpportunity::with('website')
                ->where('status', 'open')
                ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
                ->when($hasGrowthScore, fn ($query) => $query->orderByDesc('score'))
                ->first() : null,
            'websiteRows' => Website::withCount(['marketingTasks as pending_tasks_count' => fn ($query) => $query->whereIn('status', ['pending', 'in_progress'])])
                ->when($hasGrowth, fn ($query) => $query->with(['growthOpportunities' => fn ($query) => $query->where('status', 'open')->when($hasGrowthScore, fn ($query) => $query->orderByDesc('score'))->limit(1)]))
                ->latest()
                ->get()
                ->map(function (Website $website) use ($hasGscTables, $hasGrowth) {
                    $summary = $hasGscTables ? $website->gscDailyMetrics()
                        ->where('date', '>=', now()->subDays(28)->toDateString())
                        ->selectRaw('COALESCE(SUM(clicks), 0) as clicks, COALESCE(SUM(impressions), 0) as impressions, COALESCE(AVG(ctr), 0) as ctr, COALESCE(AVG(position), 0) as position')
                        ->first() : (object) ['clicks' => 0, 'impressions' => 0, 'ctr' => 0, 'position' => 0];

                    return [
                        'website' => $website,
                        'clicks' => (int) ($summary->clicks ?? 0),
                        'impressions' => (int) ($summary->impressions ?? 0),
                        'ctr' => (float) ($summary->ctr ?? 0),
                        'position' => (float) ($summary->position ?? 0),
                        'top_opportunity' => $hasGrowth ? $website->growthOpportunities->first() : null,
                        'pending_tasks' => $website->pending_tasks_count,
                    ];
                }),
            'latestAudits' => SeoAudit::with('website')->latest()->limit(5)->get(),
            'openTasks' => MarketingTask::with('website')->whereIn('status', ['pending', 'in_progress'])->latest()->limit(5)->get(),
            'latestInsights' => AiInsight::with('website')->latest()->limit(5)->get(),
            'latestReport' => WeeklyReport::latest('week_end')->first(),
        ]);
    }
}
