<?php

namespace App\Services;

use App\Models\GscDevice;
use App\Models\GscPage;
use App\Models\GscQuery;
use App\Models\GrowthOpportunity;
use App\Models\Website;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class GrowthOpportunityGenerator
{
    public function generate(Website $website, CarbonInterface $start, CarbonInterface $end): int
    {
        $created = 0;

        foreach (GscQuery::where('website_id', $website->id)->where('date_start', $start->toDateString())->where('date_end', $end->toDateString())->get() as $query) {
            $created += $this->queryRules($website, $query);
        }

        foreach (GscPage::where('website_id', $website->id)->where('date_start', $start->toDateString())->where('date_end', $end->toDateString())->get() as $page) {
            if ($page->clicks >= 3) {
                $created += $this->create($website, 'page', $page->page_url, 'improve_booking_cta', $page, 'This page already attracts search traffic and may be ready for conversion improvement.', 'Make the booking action clearer on this page with a visible CTA, phone action, and appointment path.', 'More visitors can find the next step toward booking.', 'medium');
            }
        }

        $mobile = GscDevice::where('website_id', $website->id)->where('device', 'mobile')->where('date_start', $start->toDateString())->where('date_end', $end->toDateString())->first();
        $desktop = GscDevice::where('website_id', $website->id)->where('device', 'desktop')->where('date_start', $start->toDateString())->where('date_end', $end->toDateString())->first();

        if ($mobile && (! $desktop || $mobile->clicks > $desktop->clicks || $mobile->ctr > $desktop->ctr)) {
            $created += $this->create($website, 'device', 'mobile', 'improve_booking_cta', $mobile, 'Mobile search performance is stronger than desktop, so the mobile appointment path matters.', 'Review mobile booking buttons, sticky CTA, phone click button, and appointment path.', 'Mobile visitors can more easily take a booking-oriented action.', 'high');
        }

        return $created;
    }

    private function queryRules(Website $website, GscQuery $query): int
    {
        $created = 0;

        if ($query->impressions >= 30 && $query->ctr < 2 && $query->position <= 15) {
            $created += $this->create($website, 'query', $query->query, 'increase_ctr', $query, 'This query has visibility but a low click-through rate.', 'Improve SEO title/meta description and make the search snippet more patient-focused.', 'More patients looking for this service may choose to visit the site.', 'high');
        }

        if ($query->position >= 5 && $query->position <= 12 && $query->impressions >= 10 && $query->clicks <= 1) {
            $created += $this->create($website, 'query', $query->query, 'improve_position', $query, 'This query is close to page-one visibility but is not earning meaningful clicks.', 'Strengthen content, headings, internal links, and local service relevance.', 'The page may move into a more visible search position for relevant visitors.', 'medium');
        }

        if ($query->impressions >= 20 && $query->clicks === 0) {
            $created += $this->create($website, 'query', $query->query, 'update_existing_page', $query, 'This query is visible in search but has not produced clicks.', 'Rewrite title/meta, improve intent match, and add a stronger CTA.', 'The search snippet can better match what patients looking for this topic expect.', 'high');
        }

        return $created;
    }

    private function create(Website $website, string $sourceType, string $sourceValue, string $type, object $metric, string $problem, string $recommendation, string $expectedResult, string $priority): int
    {
        $sourceValue = Str::limit($sourceValue, 512, '');

        $opportunity = GrowthOpportunity::firstOrCreate(
            [
                'website_id' => $website->id,
                'source_type' => $sourceType,
                'source_value' => $sourceValue,
                'source_hash' => hash('sha256', $sourceValue),
                'opportunity_type' => $type,
                'date_start' => $metric->date_start,
                'date_end' => $metric->date_end,
                'status' => 'open',
            ],
            [
                'clicks' => $metric->clicks,
                'impressions' => $metric->impressions,
                'ctr' => $metric->ctr,
                'position' => $metric->position,
                'problem' => $problem,
                'recommendation' => $recommendation,
                'expected_result' => $expectedResult,
                'priority' => $priority,
            ]
        );

        return $opportunity->wasRecentlyCreated ? 1 : 0;
    }
}
