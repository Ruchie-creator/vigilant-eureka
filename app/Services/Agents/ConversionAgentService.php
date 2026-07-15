<?php

namespace App\Services\Agents;

use App\Models\Website;

class ConversionAgentService extends AgentService
{
    protected function action(Website $website, array $goal): array
    {
        $opportunity = $this->topOpportunity($website, ['conversion_improvement']);
        $check = $website->conversionChecks()->whereIn('status', ['missing', 'partial'])->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")->first();
        $eventCount = $website->conversionEvents()->where('occurred_at', '>=', now()->subDays(30))->count();

        if ($opportunity) {
            $source = $opportunity->related_page_url ?: $opportunity->source_value;
            return $this->baseAction($goal, 'Improve the conversion path on '.$source, $opportunity->problem, 'The affected path should make '.$goal['primary_action_label'].' easier without assuming an unmeasured outcome.', $opportunity->recommendation, $opportunity->expected_result ?: 'A clearer path to the primary conversion.', 'Implement and verify the conversion improvement for '.$source, $opportunity->priority) + ['page' => $opportunity->related_page_url, 'query' => $opportunity->source_type === 'query' ? $opportunity->source_value : null, 'data_sources' => ['growth opportunities', 'anonymous conversion events'], 'metadata' => ['recent_conversion_events' => $eventCount]];
        }

        if ($check) {
            return $this->baseAction($goal, 'Resolve conversion check: '.$check->item, $check->item.' is marked '.$check->status.'.', 'The missing or partial element can interrupt the '.$goal['journey_label'].'.', $check->recommendation, 'A more complete and measurable path toward '.$goal['primary_action_label'].'.', $check->recommendation, $check->priority) + ['page' => $check->page_url, 'data_sources' => ['conversion checks', 'anonymous conversion events'], 'metadata' => ['recent_conversion_events' => $eventCount]];
        }

        return $this->baseAction($goal, 'Validate the '.$goal['journey_label'], $eventCount.' configured conversion events were recorded in the last 30 days.', 'Reliable event evidence is needed to identify where the conversion path loses momentum.', 'Verify tracking for '.$goal['primary_action_label'].' and its supporting conversions.', 'A trustworthy conversion baseline for future recommendations.', 'Validate primary and supporting conversion tracking', $eventCount > 0 ? 'low' : 'medium') + ['approval_required' => false, 'data_sources' => ['anonymous conversion events'], 'metadata' => ['recent_conversion_events' => $eventCount]];
    }
}
