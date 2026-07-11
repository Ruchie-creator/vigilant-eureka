<?php

namespace App\Http\Controllers;

use App\Models\AiInsight;
use App\Models\GrowthOpportunity;
use App\Models\GscDailyMetric;
use App\Models\GscSync;
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
        $hasGscSyncs = Schema::hasTable('gsc_syncs');
        $hasGrowth = Schema::hasTable('growth_opportunities');
        $hasGrowthScore = $hasGrowth && Schema::hasColumn('growth_opportunities', 'score');
        $hasGrowthCategory = $hasGrowth && Schema::hasColumn('growth_opportunities', 'opportunity_category');

        $metrics = $hasGscSyncs ? GscSync::query()
            ->where('status', 'success')
            ->selectRaw('COALESCE(SUM(total_clicks), 0) as clicks, COALESCE(SUM(total_impressions), 0) as impressions, COALESCE(AVG(average_ctr), 0) as ctr, COALESCE(AVG(average_position), 0) as position')
            ->first() : ($hasGscTables ? GscDailyMetric::query()
            ->selectRaw('COALESCE(SUM(clicks), 0) as clicks, COALESCE(SUM(impressions), 0) as impressions, COALESCE(AVG(ctr), 0) as ctr, COALESCE(AVG(position), 0) as position')
            ->first() : (object) ['clicks' => 0, 'impressions' => 0, 'ctr' => 0, 'position' => 0]);

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
                ->when($hasGrowthCategory, fn ($query) => $query->whereIn('opportunity_category', ['acquisition_growth', 'service_page_growth', 'conversion_improvement']))
                ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
                ->when($hasGrowthScore, fn ($query) => $query->orderByDesc('score'))
                ->first() : null,
            'websiteRows' => Website::withCount(['marketingTasks as pending_tasks_count' => fn ($query) => $query->whereIn('status', ['pending', 'in_progress'])])
                ->when($hasGrowth, fn ($query) => $query->with(['growthOpportunities' => fn ($query) => $query->where('status', 'open')->when($hasGrowthCategory, fn ($query) => $query->whereNotIn('opportunity_category', ['branded_visibility', 'reputation_conversion', 'low_value']))->when($hasGrowthScore, fn ($query) => $query->orderByDesc('score'))->limit(1)]))
                ->latest()
                ->get()
                ->map(function (Website $website) use ($hasGscTables, $hasGscSyncs, $hasGrowth) {
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
                    ];
                }),
            'latestAudits' => SeoAudit::with('website')->latest()->limit(5)->get(),
            'openTasks' => MarketingTask::with('website')->whereIn('status', ['pending', 'in_progress'])->latest()->limit(5)->get(),
            'latestInsights' => AiInsight::with('website')->latest()->limit(5)->get(),
            'latestReport' => WeeklyReport::latest('week_end')->first(),
        ]);
    }
}
