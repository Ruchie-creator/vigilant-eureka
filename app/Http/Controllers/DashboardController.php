<?php

namespace App\Http\Controllers;

use App\Models\AiInsight;
use App\Models\GrowthOpportunity;
use App\Models\GscDailyMetric;
use App\Models\MarketingTask;
use App\Models\SeoAudit;
use App\Models\Website;
use App\Models\WeeklyReport;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $metrics = GscDailyMetric::query()
            ->selectRaw('COALESCE(SUM(clicks), 0) as clicks, COALESCE(SUM(impressions), 0) as impressions, COALESCE(AVG(ctr), 0) as ctr, COALESCE(AVG(position), 0) as position')
            ->first();

        return view('dashboard', [
            'websiteCount' => Website::count(),
            'totalClicks' => (int) $metrics->clicks,
            'totalImpressions' => (int) $metrics->impressions,
            'averageCtr' => (float) $metrics->ctr,
            'averagePosition' => (float) $metrics->position,
            'openGrowthOpportunities' => GrowthOpportunity::where('status', 'open')->count(),
            'pendingConversionTasks' => MarketingTask::whereIn('status', ['pending', 'in_progress'])->where('title', 'like', '%booking%')->count(),
            'latestAudits' => SeoAudit::with('website')->latest()->limit(5)->get(),
            'openTasks' => MarketingTask::with('website')->whereIn('status', ['pending', 'in_progress'])->latest()->limit(5)->get(),
            'latestInsights' => AiInsight::with('website')->latest()->limit(5)->get(),
            'latestReport' => WeeklyReport::latest('week_end')->first(),
        ]);
    }
}
