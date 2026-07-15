<?php

namespace App\Services\Agents;

use App\Models\Agent;
use App\Models\AgentAction;
use App\Models\AgentHandoff;
use App\Models\AgentRun;
use App\Models\Website;
use Illuminate\Support\Collection;

class AgentHandoffService
{
    public function createHandoff(Website $website, Agent $from, Agent $to, string $reason, array $context = [], ?string $expectedOutput = null, ?AgentRun $run = null): AgentHandoff
    {
        $existing = AgentHandoff::where('website_id', $website->id)->where('from_agent_id', $from->id)->where('to_agent_id', $to->id)->where('reason', $reason)->where('status', 'pending')->get()
            ->first(fn (AgentHandoff $handoff) => data_get($handoff->context, 'related_page_url') === ($context['related_page_url'] ?? null) && data_get($handoff->context, 'related_query') === ($context['related_query'] ?? null));

        return $existing ?: AgentHandoff::create(['website_id' => $website->id, 'agent_run_id' => $run?->id, 'from_agent_id' => $from->id, 'to_agent_id' => $to->id, 'reason' => $reason, 'context' => $context, 'expected_output' => $expectedOutput, 'status' => 'pending']);
    }

    public function acceptHandoff(AgentHandoff $handoff): AgentHandoff { $handoff->update(['status' => 'accepted', 'accepted_at' => now()]); return $handoff->fresh(); }
    public function completeHandoff(AgentHandoff $handoff): AgentHandoff { $handoff->update(['status' => 'completed', 'accepted_at' => $handoff->accepted_at ?: now(), 'completed_at' => now()]); return $handoff->fresh(); }
    public function ignoreHandoff(AgentHandoff $handoff): AgentHandoff { $handoff->update(['status' => 'ignored', 'completed_at' => now()]); return $handoff->fresh(); }
    public function failHandoff(AgentHandoff $handoff): AgentHandoff { $handoff->update(['status' => 'failed', 'completed_at' => now()]); return $handoff->fresh(); }

    public function getPendingHandoffsForAgent(Agent $agent, ?Website $website = null): Collection
    {
        return AgentHandoff::with(['fromAgent', 'website'])->where('to_agent_id', $agent->id)->where('status', 'pending')->when($website, fn ($query) => $query->where('website_id', $website->id))->latest()->get();
    }

    public function buildHandoffContext(Agent $agent, Website $website): array
    {
        return AgentHandoff::with('fromAgent')->where('website_id', $website->id)->where('to_agent_id', $agent->id)->where('status', 'accepted')->latest()->limit(10)->get()->map(fn (AgentHandoff $handoff) => [
            'id' => $handoff->id, 'from_agent' => $handoff->fromAgent->slug, 'reason' => $handoff->reason, 'source_data' => $handoff->context, 'expected_output' => $handoff->expected_output,
        ])->all();
    }

    public function createStructuredHandoffs(AgentAction $action, AgentRun $run): Collection
    {
        $from = $run->agent;
        $website = $run->website;
        $context = ['originating_action_id' => $action->id, 'originating_run_id' => $run->id, 'related_page_url' => $action->related_page_url, 'related_query' => $action->related_query, 'affected_audience' => data_get($action->metadata, 'affected_audience'), 'source_data' => data_get($action->metadata, 'data_sources', [])];
        $created = collect();
        $make = function (string $toSlug, string $reason, string $expected) use (&$created, $website, $from, &$context, $run): void {
            $to = Agent::where('slug', $toSlug)->where('status', 'active')->first();
            if ($to) $created->push($this->createHandoff($website, $from, $to, $reason, $context, $expected, $run));
        };

        if ($from->slug === 'analytics-reporting') {
            $weakPage = $website->gscPages()->where('impressions', '>=', 30)->where('ctr', '<', 2)->orderByDesc('impressions')->first();
            if ($weakPage) { $context['related_page_url'] = $weakPage->page_url; $context['source_data'] = ['impressions' => $weakPage->impressions, 'ctr' => $weakPage->ctr, 'position' => $weakPage->position]; $make('acquisition-growth', 'Strong page impressions with weak click-through rate.', 'Identify the search demand and acquisition action for this page.'); }
            $trafficPage = $website->gscPages()->where('clicks', '>', 0)->orderByDesc('clicks')->first();
            if ($trafficPage && ($website->conversionChecks()->whereIn('status', ['missing', 'partial'])->exists() || $website->conversionEvents()->count() === 0)) { $context['related_page_url'] = $trafficPage->page_url; $make('conversion', 'A page has search traffic but weak or unverified conversion actions.', 'Recommend a measurable CTA or conversion-path improvement.'); }
        }
        if ($from->slug === 'acquisition-growth' && $action->related_query && ! $action->related_page_url) $make('content-strategy', 'A relevant query has no strong matching page.', 'Define the page or content improvement that should satisfy this query.');
        if ($from->slug === 'content-strategy' && $action->related_page_url) $make('conversion', 'The proposed page improvement also needs CTA or conversion-path work.', 'Specify the CTA and conversion path for the improved page.');
        if (! in_array($from->slug, ['marketing-director', 'task-manager'], true) && in_array($action->priority, ['high', 'critical'], true)) $make('marketing-director', 'A specialist produced a high-priority action.', 'Rank this action against other specialist recommendations.');

        if ($run->run_type === 'full_team') $created = $created->map(fn (AgentHandoff $handoff) => $handoff->status === 'pending' ? $this->acceptHandoff($handoff) : $handoff);
        return $created;
    }
}
