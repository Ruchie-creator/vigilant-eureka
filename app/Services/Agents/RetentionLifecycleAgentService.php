<?php

namespace App\Services\Agents;

use App\Models\Website;

class RetentionLifecycleAgentService extends AgentService
{
    protected function action(Website $website, array $goal): array
    {
        $supportsLifecycle = in_array($goal['key'], ['saas_signup', 'saas_signup_and_subscription', 'trial_activation', 'paid_subscription', 'loyalty_retention'], true);
        $labels = collect($goal['secondary_conversion_goals'])->map(fn ($key) => $goal['conversion_labels'][$key] ?? str_replace('_', ' ', $key))->implode(', ');
        $counts = $website->conversionEvents()->where('occurred_at', '>=', now()->subDays(30))->selectRaw('event_type, COUNT(*) as total')->groupBy('event_type')->pluck('total', 'event_type');

        if (! $supportsLifecycle) {
            return $this->baseAction($goal, 'Keep lifecycle analysis out of the active priority queue', 'This workspace uses '.$goal['label'].', which does not currently define a SaaS or retention lifecycle.', 'Adding lifecycle recommendations without configured evidence would dilute the primary conversion goal.', 'Keep this agent inactive for full-team runs unless lifecycle goals are added.', 'The team remains focused on the configured conversion outcome.', 'No lifecycle task required', 'low') + ['type' => 'scope_note', 'approval_required' => false, 'data_sources' => ['workspace conversion goal']];
        }

        $missing = collect($goal['secondary_conversion_goals'])->first(fn ($event) => (int) ($counts[$event] ?? 0) === 0);
        $missingLabel = $missing ? ($goal['conversion_labels'][$missing] ?? str_replace('_', ' ', $missing)) : null;
        $found = $missingLabel
            ? 'No '.$missingLabel.' event was recorded in the last 30 days; configured lifecycle stages are '.$labels.'.'
            : 'Configured lifecycle events are being recorded across '.$labels.'.';
        $recommended = $missingLabel
            ? 'Validate '.$missingLabel.' tracking and review the product step immediately before it for friction.'
            : 'Compare lifecycle event volumes and prioritize the largest measurable drop between consecutive stages.';

        return $this->baseAction($goal, $missingLabel ? 'Validate '.$missingLabel : 'Review lifecycle stage conversion', $found, 'Trial activation, onboarding, first value, paid conversion, and retention should be evaluated as a connected journey.', $recommended, 'A measurable lifecycle baseline and a clearer next optimization target.', $missingLabel ? 'Validate '.$missingLabel.' tracking and journey step' : 'Analyze lifecycle stage drop-off', $missingLabel ? 'high' : 'medium') + ['data_sources' => ['anonymous conversion events', 'workspace conversion goal'], 'metadata' => ['event_counts' => $counts->all()]];
    }
}
