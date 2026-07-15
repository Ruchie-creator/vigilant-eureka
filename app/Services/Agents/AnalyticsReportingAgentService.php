<?php

namespace App\Services\Agents;

use App\Models\Website;

class AnalyticsReportingAgentService extends AgentService
{
    protected function action(Website $website, array $goal): array
    {
        $sync = $website->gscSyncs()->whereIn('status', ['success', 'completed'])->latest('synced_at')->first();
        $events = $website->conversionEvents()->where('occurred_at', '>=', now()->subDays(30))->count();
        $openActions = $website->growthOpportunities()->whereIn('status', ['open', 'reviewed', 'in_progress'])->count();

        if ($sync) {
            $period = $sync->date_start->format('M j').' - '.$sync->date_end->format('M j, Y');
            $found = number_format($sync->total_clicks).' clicks from '.number_format($sync->total_impressions).' impressions ('.number_format($sync->average_ctr, 2).'% CTR, position '.number_format($sync->average_position, 1).') for '.$period.'. '.$events.' configured conversion events were recorded in the last 30 days.';
            return $this->baseAction($goal, 'Performance summary for '.$period, $found, 'This establishes the evidence window behind the team recommendations and keeps search activity separate from measured conversion actions.', 'Review the '.$openActions.' open opportunities against '.$goal['primary_action_label'].' and select one measurable priority.', 'A focused weekly plan grounded in the latest connected period.', 'Review performance summary and select one weekly priority', 'medium') + ['type' => 'performance_summary', 'approval_required' => false, 'data_sources' => ['Google Search Console: '.$sync->property_url, 'anonymous conversion events'], 'metadata' => ['date_start' => $sync->date_start->toDateString(), 'date_end' => $sync->date_end->toDateString(), 'clicks' => $sync->total_clicks, 'impressions' => $sync->total_impressions, 'ctr' => $sync->average_ctr, 'position' => $sync->average_position, 'conversion_events_30d' => $events]];
        }

        return $this->baseAction($goal, 'Connect a reporting baseline', 'No completed Search Console sync is available for this workspace. '.$events.' configured conversion events were recorded in the last 30 days.', 'A dated source period is required before the agent can explain acquisition performance responsibly.', 'Complete a Search Console sync, then rerun Analytics & Reporting.', 'A reliable, dated performance summary for the team.', 'Sync Search Console and rerun analytics', 'medium') + ['type' => 'performance_summary', 'approval_required' => false, 'data_sources' => ['workspace configuration', 'anonymous conversion events']];
    }
}
