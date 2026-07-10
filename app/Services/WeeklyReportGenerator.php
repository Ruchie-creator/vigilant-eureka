<?php

namespace App\Services;

use App\Models\AiInsight;
use App\Models\MarketingTask;
use App\Models\SeoAudit;
use App\Models\WeeklyReport;
use Carbon\CarbonInterface;

class WeeklyReportGenerator
{
    public function generate(CarbonInterface $start, CarbonInterface $end): WeeklyReport
    {
        $audits = SeoAudit::with('website')->whereBetween('created_at', [$start, $end])->latest()->get();
        $insights = AiInsight::with('website')->whereBetween('created_at', [$start, $end])->latest()->get();
        $tasks = MarketingTask::with('website')->whereIn('status', ['pending', 'in_progress'])->latest()->limit(10)->get();
        $completed = MarketingTask::where('status', 'completed')->whereBetween('updated_at', [$start, $end])->count();

        return WeeklyReport::create([
            'title' => 'Weekly Marketing Report: '.$start->format('M j').' - '.$end->format('M j, Y'),
            'week_start' => $start->toDateString(),
            'week_end' => $end->toDateString(),
            'summary' => 'Generated from '.$audits->count().' SEO audits, '.$insights->count().' insights, and '.$tasks->count().' open tasks.',
            'wins' => $completed.' task(s) completed this week. '.($audits->where('is_indexable', true)->count()).' audit(s) confirmed indexable homepages.',
            'issues' => $audits->where('is_indexable', false)->count().' audit(s) need indexability review. '.$insights->where('priority', 'high')->count().' high priority insight(s) are open.',
            'recommendations' => $insights->take(5)->pluck('recommendation')->implode("\n") ?: 'Keep monitoring technical SEO, content clarity, and local search opportunities.',
            'next_actions' => $tasks->take(5)->map(fn (MarketingTask $task) => $task->title.' - '.$task->website->name)->implode("\n") ?: 'Create priority tasks from new insights.',
            'status' => 'ready',
        ]);
    }
}
