<?php

namespace App\Services\Agents;

use App\Models\AgentAction;
use App\Models\Website;

class MarketingDirectorAgentService extends AgentService
{
    protected function action(Website $website, array $goal): array
    {
        $priority = AgentAction::query()
            ->with('run.agent')
            ->where('website_id', $website->id)
            ->where('status', 'pending')
            ->whereHas('run.agent', fn ($query) => $query->where('slug', '!=', 'marketing-director'))
            ->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')")
            ->latest()
            ->first();

        if ($priority) {
            $specialist = $priority->run->agent->name;
            return $this->baseAction($goal, 'Director priority: '.$priority->title, $specialist.' found: '.($priority->metadata['what_i_found'] ?? $priority->description), 'This is the strongest current specialist action aligned to '.$goal['primary_action_label'].'.', 'Review and approve the proposed task before any campaign, message, or website change is executed: '.$priority->description, $priority->expected_result ?: 'Progress toward the configured primary conversion.', $priority->metadata['suggested_task'] ?? $priority->title, $priority->priority) + ['type' => 'director_priority', 'page' => $priority->related_page_url, 'query' => $priority->related_query, 'data_sources' => $priority->metadata['data_sources'] ?? $goal['data_sources'], 'metadata' => ['source_agent_action_id' => $priority->id, 'source_agent' => $specialist]];
        }

        return $this->baseAction($goal, 'Build the first evidence-backed team priority', 'No pending specialist action is available for this workspace.', 'The Director should coordinate specialist evidence rather than invent a standalone recommendation.', 'Run the specialist agents, then rerun the Marketing Director.', 'A ranked action plan aligned with '.$goal['primary_action_label'].'.', 'Run the full marketing team', 'low') + ['type' => 'director_priority', 'approval_required' => false, 'data_sources' => ['workspace conversion goal']];
    }
}
