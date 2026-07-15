<?php

namespace App\Services;

use App\Models\ConversionCheck;
use App\Models\Website;

class ConversionCheckService
{
    public function __construct(private readonly ConversionGoalProfileService $goalProfiles)
    {
    }

    public function ensureDefaults(Website $website): void
    {
        foreach ($this->defaults($website) as $check) {
            $check['check_hash'] = hash('sha256', ($check['page_url'] ?? '').'|'.$check['item']);
            ConversionCheck::firstOrCreate(
                ['website_id' => $website->id, 'check_hash' => $check['check_hash']],
                $check
            );
        }
    }

    private function defaults(Website $website): array
    {
        $goal = $this->goalProfiles->forWebsite($website);

        if ($goal['key'] !== 'appointment_booking') {
            $checks = [
                ['item' => $goal['primary_action_label'].' CTA visible on priority pages', 'priority' => 'high', 'status' => 'missing', 'recommendation' => 'Add a clear '.$goal['cta_label'].' to the pages with the strongest qualified traffic.'],
                ['item' => $goal['primary_action_label'].' event tracked', 'priority' => 'high', 'status' => 'missing', 'recommendation' => 'Track '.$goal['conversion_labels'][$goal['primary_action']].' as the primary conversion event.'],
                ['item' => 'Primary conversion path reviewed on mobile', 'priority' => 'high', 'status' => 'missing', 'recommendation' => 'Review the mobile '.$goal['journey_label'].' for clarity, speed, and unnecessary steps.'],
                ['item' => 'Priority landing pages reviewed for conversion', 'priority' => 'medium', 'status' => 'missing', 'recommendation' => 'Review high-intent landing pages against the configured audience and primary action.'],
                ['item' => 'Connected evidence sources verified', 'priority' => 'medium', 'status' => 'missing', 'recommendation' => 'Verify that the connected sources can measure the primary and supporting conversion goals.'],
            ];

            foreach ($goal['secondary_conversion_goals'] as $eventKey) {
                $label = $goal['conversion_labels'][$eventKey] ?? str_replace('_', ' ', $eventKey);
                $checks[] = ['item' => $label.' tracked', 'priority' => 'medium', 'status' => 'missing', 'recommendation' => 'Track '.$label.' as a supporting conversion event.'];
            }

            return $checks;
        }

        return [
            ['item' => 'Booking CTA visible above the fold on mobile', 'priority' => 'high', 'status' => 'missing', 'recommendation' => 'Add a clear appointment CTA in the first mobile viewport.'],
            ['item' => 'Sticky mobile booking button', 'priority' => 'high', 'status' => 'missing', 'recommendation' => 'Add a sticky appointment button for mobile visitors.'],
            ['item' => 'Booking CTA repeated after key content sections', 'priority' => 'medium', 'status' => 'missing', 'recommendation' => 'Repeat appointment actions after service explanations and proof sections.'],
            ['item' => 'Phone click button visible on mobile', 'priority' => 'high', 'status' => 'missing', 'recommendation' => 'Add tap-to-call where phone appointments are accepted.'],
            ['item' => 'External booking platform clicks tracked', 'priority' => 'high', 'status' => 'missing', 'recommendation' => 'Track outbound booking clicks as conversion events.'],
            ['item' => 'Contact form submissions tracked', 'priority' => 'medium', 'status' => 'missing', 'recommendation' => 'Track form submissions as conversion events.'],
            ['item' => 'GA4 conversion events configured', 'priority' => 'high', 'status' => 'missing', 'recommendation' => 'Mark booking and lead actions as GA4 conversions.'],
            ['item' => 'Top service pages reviewed for conversion', 'priority' => 'high', 'status' => 'missing', 'recommendation' => 'Review top landing service pages for CTA clarity and appointment path.'],
            ['item' => 'Google Business Profile link/calls/reviews monitored manually', 'priority' => 'medium', 'status' => 'missing', 'recommendation' => 'Monitor Google Business Profile actions, calls, reviews, and service relevance.'],
            ['item' => 'Page speed/mobile UX reviewed for top landing pages', 'priority' => 'medium', 'status' => 'missing', 'recommendation' => 'Review Core Web Vitals and mobile UX on top landing pages.'],
        ];
    }
}
