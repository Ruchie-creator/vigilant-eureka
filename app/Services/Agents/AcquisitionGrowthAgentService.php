<?php

namespace App\Services\Agents;

use App\Models\Website;

class AcquisitionGrowthAgentService extends AgentService
{
    protected function action(Website $website, array $goal): array
    {
        $opportunity = $this->topOpportunity($website, ['acquisition_growth', 'service_page_growth']);
        $query = $website->gscQueries()->orderByDesc('impressions')->first();

        if ($opportunity) {
            return $this->baseAction($goal, 'Capture more qualified demand from '.$this->source($opportunity), $opportunity->problem, 'This evidence points to existing demand that can produce more progress toward '.$goal['primary_action_label'].'.', $opportunity->recommendation, $opportunity->expected_result ?: 'More qualified visits and progress toward the primary conversion.', 'Implement the acquisition recommendation for '.$this->source($opportunity), $opportunity->priority) + ['page' => $opportunity->related_page_url, 'query' => $opportunity->source_type === 'query' ? $opportunity->source_value : null, 'data_sources' => ['Google Search Console', 'growth opportunities']];
        }

        if ($query) {
            return $this->baseAction($goal, 'Improve acquisition from a visible query', '"'.$query->query.'" has '.$query->impressions.' impressions and '.$query->clicks.' clicks.', 'The query already has search visibility and can be evaluated against the configured conversion goal.', 'Review its result snippet and map it to the clearest page and '.$goal['cta_label'].'.', 'A clearer intent match can increase qualified visits.', 'Review query intent and improve its mapped landing page', 'medium') + ['query' => $query->query, 'data_sources' => ['Google Search Console']];
        }

        return $this->baseAction($goal, 'Establish an acquisition evidence baseline', 'No Search Console query or acquisition opportunity is available yet.', 'The team needs connected demand data before it can make a specific acquisition recommendation.', 'Sync the workspace Search Console property and confirm the reporting period.', 'Future acquisition actions can use real query and page evidence.', 'Sync Search Console acquisition data', 'low') + ['approval_required' => false, 'data_sources' => ['workspace configuration']];
    }

    private function source(object $opportunity): string
    {
        return $opportunity->related_page_url ?: $opportunity->source_value;
    }
}
