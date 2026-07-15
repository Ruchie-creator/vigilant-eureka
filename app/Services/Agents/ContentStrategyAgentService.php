<?php

namespace App\Services\Agents;

use App\Models\Website;

class ContentStrategyAgentService extends AgentService
{
    protected function action(Website $website, array $goal): array
    {
        $opportunity = $this->topOpportunity($website, ['service_page_growth', 'content']);
        $page = $website->gscPages()->orderByDesc('impressions')->first();

        if ($opportunity) {
            $source = $opportunity->related_page_url ?: $opportunity->source_value;
            return $this->baseAction($goal, 'Strengthen content on '.$source, $opportunity->problem, 'This page or topic can connect qualified demand to '.$goal['primary_action_label'].'.', $opportunity->recommendation, $opportunity->expected_result ?: 'Stronger relevance and a clearer conversion path.', 'Update content and internal links for '.$source, $opportunity->priority) + ['page' => $opportunity->related_page_url, 'query' => $opportunity->source_type === 'query' ? $opportunity->source_value : null, 'data_sources' => ['Google Search Console', 'growth opportunities']];
        }

        if ($page) {
            return $this->baseAction($goal, 'Review the highest-visibility page', $page->page_url.' has '.$page->impressions.' impressions at position '.number_format($page->position, 1).'.', 'High-visibility pages should explain the offer clearly and connect visitors to the configured primary action.', 'Review headings, intent match, internal links, and the '.$goal['cta_label'].' on this page.', 'Better content continuity from search result to conversion path.', 'Improve content and internal links on the highest-visibility page', 'medium') + ['page' => $page->page_url, 'data_sources' => ['Google Search Console']];
        }

        return $this->baseAction($goal, 'Map priority content to the conversion goal', 'No page-level search evidence is available yet.', 'A simple priority-page map gives future agents a reliable content scope.', 'Confirm the most important offer, product, or service pages for '.$goal['primary_action_label'].'.', 'More focused content recommendations after the next data sync.', 'Configure priority workspace pages', 'low') + ['approval_required' => false, 'data_sources' => ['workspace configuration']];
    }
}
