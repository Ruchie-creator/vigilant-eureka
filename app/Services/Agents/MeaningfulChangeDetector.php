<?php

namespace App\Services\Agents;

use App\Models\GscSync;
use App\Models\Website;
use App\Services\GrowthOpportunityGenerator;

class MeaningfulChangeDetector
{
    public function __construct(private readonly GrowthOpportunityGenerator $classifier) {}

    public function detect(Website $website, GscSync $current): array
    {
        $previous = GscSync::where('website_id', $website->id)->where('status', 'success')->whereKeyNot($current->id)->where('synced_at', '<=', $current->synced_at)->latest('synced_at')->first();
        $triggers = [];
        $currentPages = $website->gscPages()->whereDate('date_start', $current->date_start)->whereDate('date_end', $current->date_end)->get();
        $previousPages = $previous ? $website->gscPages()->whereDate('date_start', $previous->date_start)->whereDate('date_end', $previous->date_end)->get()->keyBy('page_url') : collect();
        $currentQueries = $website->gscQueries()->whereDate('date_start', $current->date_start)->whereDate('date_end', $current->date_end)->get();
        $previousQueries = $previous ? $website->gscQueries()->whereDate('date_start', $previous->date_start)->whereDate('date_end', $previous->date_end)->get()->keyBy('query') : collect();
        $minimumImpressions = config('agent-operations.minimum_impressions');

        foreach ($currentPages as $page) {
            $old = $previousPages->get($page->page_url);
            $servicePage = $this->classifier->pageType($page->page_url, $website) === 'service_page';
            if ($servicePage && $old && $page->impressions >= $minimumImpressions && ($old->ctr - $page->ctr) >= config('agent-operations.ctr_change_points')) {
                $triggers['acquisition-growth'] ??= $this->trigger('acquisition-growth', 'Service-page CTR declined meaningfully.', $page->page_url, null, ['previous_ctr' => $old->ctr, 'current_ctr' => $page->ctr, 'impressions' => $page->impressions], 'Recommend a specific acquisition change for the affected service page.');
            }
            $clickGain = $old ? $this->percentChange($old->clicks, $page->clicks) : ($page->clicks >= config('agent-operations.minimum_clicks') ? 100 : 0);
            if ($servicePage && $clickGain >= config('agent-operations.clicks_change_percent') && $page->clicks >= config('agent-operations.minimum_clicks') && $this->hasConversionGap($website, $page->page_url)) {
                $triggers['conversion'] ??= $this->trigger('conversion', 'A service page gained search traffic without complete conversion tracking.', $page->page_url, null, ['previous_clicks' => $old?->clicks ?? 0, 'current_clicks' => $page->clicks, 'click_change_percent' => $clickGain], 'Recommend a tracked conversion action and CTA-path improvement.');
            }
            if ($old && $this->classifier->pageType($page->page_url, $website) === 'blog' && $this->percentChange($old->impressions, $page->impressions) <= -config('agent-operations.impressions_change_percent')) {
                $triggers['content-strategy'] ??= $this->trigger('content-strategy', 'An existing content page lost meaningful search visibility.', $page->page_url, null, ['previous_impressions' => $old->impressions, 'current_impressions' => $page->impressions], 'Diagnose the content loss and recommend one focused recovery action.');
            }
        }

        foreach ($currentQueries as $query) {
            $intent = $this->classifier->classifyQueryIntent($query->query, $website);
            if (! in_array($intent, ['service_intent', 'local_service_intent', 'condition_intent'], true)) continue;
            $old = $previousQueries->get($query->query);
            $gain = $old ? $this->percentChange($old->impressions, $query->impressions) : 100;
            if ($query->impressions >= $minimumImpressions && $query->ctr < 2 && ($gain >= config('agent-operations.impressions_change_percent') || ($query->position >= 5 && $query->position <= 12))) {
                $triggers['acquisition-growth'] ??= $this->trigger('acquisition-growth', 'A service query gained visibility but remains low CTR or near page one.', null, $query->query, ['previous_impressions' => $old?->impressions ?? 0, 'current_impressions' => $query->impressions, 'ctr' => $query->ctr, 'position' => $query->position], 'Recommend the query-to-page acquisition action.');
            }
            $mappedPage = $this->classifier->mapQueryToPage($query->query, $currentPages, $website);
            if (! $old && $query->impressions >= config('agent-operations.new_query_impressions') && (! $mappedPage || $this->classifier->pageType($mappedPage->page_url, $website) !== 'service_page')) {
                $triggers['content-strategy'] ??= $this->trigger('content-strategy', 'A new service-intent query has no strong matching service page.', $mappedPage?->page_url, $query->query, ['impressions' => $query->impressions, 'ctr' => $query->ctr, 'position' => $query->position], 'Define the best matching page or content improvement.');
            }
        }

        $mobile = $website->gscDevices()->whereDate('date_start', $current->date_start)->whereDate('date_end', $current->date_end)->where('device', 'mobile')->first();
        $oldMobile = $previous ? $website->gscDevices()->whereDate('date_start', $previous->date_start)->whereDate('date_end', $previous->date_end)->where('device', 'mobile')->first() : null;
        if ($mobile && $mobile->clicks >= config('agent-operations.minimum_clicks') && $this->percentChange($oldMobile?->clicks ?? 0, $mobile->clicks) >= config('agent-operations.clicks_change_percent') && $website->conversionChecks()->whereIn('status', ['missing', 'partial'])->exists()) {
            $triggers['conversion'] ??= $this->trigger('conversion', 'Mobile search traffic increased while conversion checks remain incomplete.', null, null, ['device' => 'mobile', 'previous_clicks' => $oldMobile?->clicks ?? 0, 'current_clicks' => $mobile->clicks], 'Review the mobile CTA and tracked conversion path.');
        }

        if ($this->supportsLifecycle($website) && $website->conversionEvents()->whereIn('event_type', ['trial_started', 'onboarding_completed', 'subscription_started'])->exists()) {
            $triggers['retention-lifecycle'] ??= $this->trigger('retention-lifecycle', 'Supported lifecycle activity is available for retention review.', null, null, ['data_source' => 'anonymous lifecycle conversion events'], 'Identify one evidence-backed lifecycle action.');
        }

        return array_values($triggers);
    }

    public function comparison(?GscSync $previous, GscSync $current): array
    {
        if (! $previous) return [];
        return ['clicks_change_percent' => $this->percentChange($previous->total_clicks, $current->total_clicks), 'impressions_change_percent' => $this->percentChange($previous->total_impressions, $current->total_impressions), 'ctr_change_points' => round($current->average_ctr - $previous->average_ctr, 2), 'position_change' => round($current->average_position - $previous->average_position, 2)];
    }

    private function trigger(string $agent, string $reason, ?string $page, ?string $query, array $evidence, string $expected): array
    {
        return ['agent' => $agent, 'reason' => $reason, 'context' => ['related_page_url' => $page, 'related_query' => $query, 'source_data' => $evidence], 'expected_output' => $expected];
    }
    private function percentChange(float|int $old, float|int $new): float { return $old == 0 ? ($new > 0 ? 100 : 0) : round((($new - $old) / abs($old)) * 100, 2); }
    private function hasConversionGap(Website $website, string $page): bool { return $website->conversionChecks()->where(fn ($query) => $query->whereNull('page_url')->orWhere('page_url', $page))->whereIn('status', ['missing', 'partial'])->exists() || ! $website->conversionEvents()->where('page_url', $page)->exists(); }
    private function supportsLifecycle(Website $website): bool { return in_array($website->primary_conversion_goal, ['saas_signup', 'saas_signup_and_subscription', 'trial_activation', 'paid_subscription', 'loyalty_retention'], true); }
}
