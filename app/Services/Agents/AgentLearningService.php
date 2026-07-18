<?php

namespace App\Services\Agents;

use App\Models\ActionOutcome;
use App\Models\Agent;
use App\Models\AgentAction;
use App\Models\AgentMemory;
use App\Models\Website;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AgentLearningService
{
    public function learnFromOutcome(ActionOutcome $outcome): ?AgentMemory
    {
        if (! in_array($outcome->status, ['improved', 'no_change', 'declined', 'inconclusive'], true)) return null;
        $outcome->loadMissing(['website', 'agentAction.run.agent']);
        $action = $outcome->agentAction;
        $agent = $action->run->agent;
        $type = $outcome->status === 'improved' ? 'successful_action' : (in_array($outcome->status, ['declined', 'no_change'], true) ? 'unsuccessful_action' : 'inconclusive_action');
        $affected = $action->related_page_url ?: ($action->related_query ?: data_get($action->metadata, 'affected_audience', 'workspace conversion goal'));
        $confidence = match ($outcome->confidence) { 'high' => .9, 'medium' => .7, default => .4 };
        $value = ucfirst(str_replace('_', ' ', $outcome->status)).' result for '.$action->action_type.' affecting '.$affected.'. '.$outcome->outcome_summary;
        $metadata = ['outcome_id' => $outcome->id, 'action_id' => $action->id, 'action_type' => $action->action_type, 'affected_source' => $affected, 'conversion_goal' => $outcome->website->primary_conversion_goal, 'measured_result' => $outcome->status, 'outcome_confidence' => $outcome->confidence, 'metric_changes' => $outcome->metric_changes];

        $memory = AgentMemory::where('source_type', 'action_outcome')->where('source_id', $outcome->id)->first();
        if ($memory) {
            $memory->update(['agent_id' => $agent->id, 'website_id' => $outcome->website_id, 'memory_type' => $type, 'memory_key' => 'outcome-learning:'.$outcome->id, 'memory_value' => $value, 'confidence' => $confidence, 'learning_metadata' => $metadata]);
        } else {
            $memory = AgentMemory::create(['agent_id' => $agent->id, 'website_id' => $outcome->website_id, 'memory_type' => $type, 'memory_key' => 'outcome-learning:'.$outcome->id, 'memory_value' => $value, 'confidence' => $confidence, 'enabled' => true, 'source_type' => 'action_outcome', 'source_id' => $outcome->id, 'learning_metadata' => $metadata]);
        }

        $this->refreshPatternMemory($agent, $outcome->website, $action->action_type);
        return $memory->fresh();
    }

    public function buildLearningContext(Agent $agent, Website $website): array
    {
        $query = $this->activeLearningQuery($website)->when($agent->slug !== 'marketing-director', fn (Builder $query) => $query->where('agent_id', $agent->id));
        $memories = $query->with('agent')->latest()->limit(30)->get();
        $memories->each(fn (AgentMemory $memory) => $memory->updateQuietly(['last_used_at' => now()]));

        return [
            'successful_patterns' => $this->contextRows($memories->whereIn('memory_type', ['successful_action', 'performance_pattern'])),
            'unsuccessful_patterns' => $this->contextRows($memories->where('memory_type', 'unsuccessful_action')),
            'inconclusive_results' => $this->contextRows($memories->where('memory_type', 'inconclusive_action')),
            'open_tasks' => $website->marketingTasks()->whereIn('status', ['pending', 'in_progress'])->latest()->limit(10)->pluck('title')->all(),
            'ignored_recommendations' => $website->agentActions()->where('status', 'ignored')->latest()->limit(10)->pluck('title')->all(),
            'awaiting_evaluation' => $website->actionOutcomes()->whereIn('status', ['pending_baseline', 'baseline_captured', 'waiting', 'evaluating'])->count(),
            'unresolved_outcomes' => $website->actionOutcomes()->whereIn('status', ['failed', 'inconclusive'])->count(),
        ];
    }

    public function findSimilarPastActions(Agent $agent, Website $website, string $actionType, ?string $affected = null): Collection
    {
        return $this->activeLearningQuery($website)->where('agent_id', $agent->id)
            ->whereIn('memory_type', ['successful_action', 'unsuccessful_action', 'inconclusive_action', 'performance_pattern'])
            ->where('learning_metadata->action_type', $actionType)
            ->when($affected, fn (Builder $query) => $query->where('learning_metadata->affected_source', $affected))
            ->latest()->get();
    }

    public function calculateRecommendationConfidence(int $successes, int $failures, int $inconclusive = 0, string $evidenceStrength = 'medium'): float
    {
        $base = match ($evidenceStrength) { 'high' => .7, 'low' => .45, default => .58 };
        return round(max(.1, min(.95, $base + min(.2, $successes * .08) - min(.3, $failures * .1) - min(.15, $inconclusive * .03))), 2);
    }

    public function identifyRepeatedFailures(Agent $agent, Website $website): Collection
    {
        return $this->activeLearningQuery($website)->where('agent_id', $agent->id)->where('memory_type', 'unsuccessful_action')->get()->groupBy('learning_metadata.action_type')->filter(fn (Collection $items) => $items->count() >= 2);
    }

    public function identifySuccessfulPatterns(Agent $agent, Website $website): Collection
    {
        return $this->activeLearningQuery($website)->where('agent_id', $agent->id)->whereIn('memory_type', ['successful_action', 'performance_pattern'])->orderByDesc('confidence')->get();
    }

    public function scoreRecommendation(Agent $agent, Website $website, array $action): array
    {
        $affected = $action['page'] ?? ($action['query'] ?? ($action['audience'] ?? null));
        $similar = $this->findSimilarPastActions($agent, $website, $action['type'], $affected);
        $successes = $similar->whereIn('memory_type', ['successful_action', 'performance_pattern'])->count();
        $failures = $similar->where('memory_type', 'unsuccessful_action')->count();
        $inconclusive = $similar->where('memory_type', 'inconclusive_action')->count();
        $sync = $website->gscSyncs()->whereIn('status', ['success', 'completed'])->latest('synced_at')->first();
        $strength = $sync ? ((int) $sync->total_impressions >= 200 ? 'high' : ((int) $sync->total_impressions >= 20 ? 'medium' : 'low')) : (filled($action['data_sources'] ?? []) ? 'high' : 'medium');
        $conflicts = $affected ? $website->marketingTasks()->whereIn('status', ['pending', 'in_progress'])->where('related_page_url', $affected)->count() : 0;
        $confidence = max(.1, $this->calculateRecommendationConfidence($successes, $failures, $inconclusive, $strength) - min(.15, $conflicts * .05));
        $summary = $successes > 0 ? $successes.' similar action(s) previously produced a positive measured result.' : 'This recommendation is a new experiment without a directly matching positive outcome.';
        if ($failures > 0) $summary .= ' '.$failures.' similar action(s) were unsuccessful; retry only with a materially changed approach and human review.';
        if ($inconclusive > 0) $summary .= ' Evidence is weaker because '.$inconclusive.' similar result(s) were inconclusive.';
        if ($conflicts > 0) $summary .= ' '.$conflicts.' open task(s) already affect the same source, so confidence is reduced to avoid conflicting work.';
        return ['confidence_score' => round($confidence, 2), 'learning_score' => round(($successes * 2) - ($failures * 2.5) - ($inconclusive * .5) - ($conflicts * .5), 3), 'evidence_strength' => $strength, 'similar_success_count' => $successes, 'similar_failure_count' => $failures, 'learning_summary' => $summary];
    }

    private function activeLearningQuery(Website $website): Builder
    {
        return AgentMemory::where('website_id', $website->id)->where('enabled', true)->whereIn('memory_type', ['successful_action', 'unsuccessful_action', 'inconclusive_action', 'performance_pattern'])->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    private function contextRows(Collection $memories): array
    {
        return $memories->map(fn (AgentMemory $memory) => ['agent' => $memory->agent?->slug, 'summary' => $memory->memory_value, 'confidence' => $memory->confidence, 'action_type' => data_get($memory->learning_metadata, 'action_type'), 'affected_source' => data_get($memory->learning_metadata, 'affected_source')])->values()->all();
    }

    private function refreshPatternMemory(Agent $agent, Website $website, string $actionType): void
    {
        $successes = AgentMemory::where('agent_id', $agent->id)->where('website_id', $website->id)->where('memory_type', 'successful_action')->where('learning_metadata->action_type', $actionType)->count();
        if ($successes < 2) return;
        AgentMemory::updateOrCreate(['agent_id' => $agent->id, 'website_id' => $website->id, 'memory_type' => 'performance_pattern', 'memory_key' => 'successful-pattern:'.$actionType], ['memory_value' => $actionType.' has produced positive measured outcomes '.$successes.' times in this workspace.', 'confidence' => min(.95, .65 + ($successes * .05)), 'enabled' => true, 'source_type' => 'outcome_pattern', 'learning_metadata' => ['action_type' => $actionType, 'measured_result' => 'improved', 'success_count' => $successes, 'conversion_goal' => $website->primary_conversion_goal]]);
    }
}
