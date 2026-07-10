<?php

namespace App\Services;

use App\Models\ConversionCheck;
use App\Models\Website;

class ConversionCheckService
{
    public function ensureDefaults(Website $website): void
    {
        foreach ($this->defaults() as $check) {
            $check['check_hash'] = hash('sha256', ($check['page_url'] ?? '').'|'.$check['item']);
            ConversionCheck::firstOrCreate(
                ['website_id' => $website->id, 'check_hash' => $check['check_hash']],
                $check
            );
        }
    }

    private function defaults(): array
    {
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
