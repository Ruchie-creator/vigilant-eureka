<?php

namespace App\Services\Agents;

use App\Models\AgentRun;
use App\Models\MarketingTask;
use App\Models\Website;
use App\Models\WeeklyMarketingPlan;
use Carbon\CarbonInterface;

class WeeklyMarketingPlanService
{
    public function generate(Website $website, ?AgentRun $directorRun = null, ?CarbonInterface $start = null, ?CarbonInterface $end = null): WeeklyMarketingPlan
    {
        $start ??= now()->startOfWeek();
        $end ??= now()->endOfWeek();
        $actions = $website->agentActions()->with('run.agent')->whereIn('status', ['pending', 'reviewed', 'approved'])->orderByDesc('learning_score')->latest()->limit(30)->get();
        $openTasks = $website->marketingTasks()->whereIn('status', ['pending', 'in_progress'])->get();
        $priorities = $actions->reject(function ($action) use ($openTasks) {
            return $openTasks->contains(fn (MarketingTask $task) => $task->title === data_get($action->metadata, 'suggested_task', $action->title) || ($action->related_page_url && $task->related_page_url === $action->related_page_url));
        })->unique(fn ($action) => strtolower(data_get($action->metadata, 'suggested_task', $action->title)).'|'.$action->related_page_url)->take(3)->map(fn ($action) => [
            'action_id' => $action->id,
            'title' => $action->title,
            'responsible_agent' => $action->run->agent->name,
            'supporting_data' => data_get($action->metadata, 'what_i_found'),
            'recommended_task' => data_get($action->metadata, 'suggested_task', $action->title),
            'expected_result' => $action->expected_result,
            'related_page_url' => $action->related_page_url,
            'learning_summary' => $action->learning_summary,
            'recommendation_confidence' => $action->confidence_score,
        ])->values()->all();
        $sync = $website->gscSyncs()->where('status', 'success')->latest('synced_at')->first();
        $unresolvedHandoffs = $website->agentHandoffs()->with(['fromAgent', 'toAgent'])->whereIn('status', ['pending', 'accepted', 'failed'])->latest()->limit(10)->get()->map(fn ($handoff) => ['from' => $handoff->fromAgent->name, 'to' => $handoff->toAgent->name, 'reason' => $handoff->reason, 'status' => $handoff->status])->all();

        return WeeklyMarketingPlan::updateOrCreate(
            ['website_id' => $website->id, 'period_start' => $start->toDateString(), 'period_end' => $end->toDateString()],
            ['agent_run_id' => $directorRun?->id, 'primary_goal' => $website->primary_conversion_goal, 'status' => 'draft', 'executive_summary' => count($priorities).' evidence-backed priorities prepared for '.$website->name.'. All implementation remains subject to approval.', 'performance_summary' => $sync ? ['date_start' => $sync->date_start->toDateString(), 'date_end' => $sync->date_end->toDateString(), 'clicks' => $sync->total_clicks, 'impressions' => $sync->total_impressions, 'ctr' => $sync->average_ctr, 'position' => $sync->average_position] : [], 'top_priorities' => $priorities, 'agent_contributions' => $actions->groupBy(fn ($action) => $action->run->agent->name)->map->pluck('title')->all(), 'expected_results' => collect($priorities)->pluck('expected_result')->filter()->values()->all(), 'unresolved_actions' => ['handoffs' => $unresolvedHandoffs, 'awaiting_approval' => $actions->where('status', 'pending')->pluck('title')->values()->all()]]
        );
    }
}
