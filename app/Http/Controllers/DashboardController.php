<?php

namespace App\Http\Controllers;

use App\Models\AiInsight;
use App\Models\MarketingTask;
use App\Models\SeoAudit;
use App\Models\Website;
use App\Models\WeeklyReport;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'websiteCount' => Website::count(),
            'latestAudits' => SeoAudit::with('website')->latest()->limit(5)->get(),
            'openTasks' => MarketingTask::with('website')->whereIn('status', ['pending', 'in_progress'])->latest()->limit(5)->get(),
            'latestInsights' => AiInsight::with('website')->latest()->limit(5)->get(),
            'latestReport' => WeeklyReport::latest('week_end')->first(),
        ]);
    }
}
