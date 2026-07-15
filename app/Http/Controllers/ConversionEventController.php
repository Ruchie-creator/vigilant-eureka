<?php

namespace App\Http\Controllers;

use App\Models\ConversionEvent;
use App\Models\Website;
use App\Services\ConversionEventAttributor;
use App\Services\ConversionGoalProfileService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class ConversionEventController extends Controller
{
    public function store(Request $request, string $trackingKey, ConversionEventAttributor $attributor, ConversionGoalProfileService $goalProfiles): Response
    {
        $website = Website::where('tracking_key', $trackingKey)->firstOrFail();
        $goalProfile = $goalProfiles->forWebsite($website);
        $data = $request->validate([
            'event_uuid' => ['required', 'uuid'],
            'event_type' => ['required', Rule::in(array_keys($goalProfile['conversion_labels']))],
            'action_label' => ['nullable', 'string', 'max:60', 'regex:/\A[a-z0-9_-]+\z/i'],
            'page_url' => ['required', 'url:http,https', 'max:2048'],
            'target_url' => ['nullable', 'string', 'max:2048'],
            'device_type' => ['nullable', Rule::in(['mobile', 'tablet', 'desktop'])],
            'opportunity_id' => ['nullable', 'integer'],
        ]);

        $pageUrl = $this->canonicalHttpUrl($data['page_url']);
        $websiteHost = $this->normalizedHost($website->url);

        if (! $pageUrl || $this->normalizedHost($pageUrl) !== $websiteHost) {
            throw ValidationException::withMessages(['page_url' => 'The page URL must belong to the tracked website.']);
        }

        $opportunity = $attributor->match($website, $pageUrl, $data['opportunity_id'] ?? null);

        $event = ConversionEvent::firstOrCreate(
            ['website_id' => $website->id, 'event_uuid' => $data['event_uuid']],
            [
                'growth_opportunity_id' => $opportunity?->id,
                'event_type' => $data['event_type'],
                'action_label' => filled($data['action_label'] ?? null) ? strtolower($data['action_label']) : null,
                'page_url' => $pageUrl,
                'target_url' => $this->canonicalTargetUrl($data['target_url'] ?? null),
                'device_type' => $data['device_type'] ?? null,
                'occurred_at' => now(),
            ]
        );

        return response()->noContent($event->wasRecentlyCreated ? 202 : 204);
    }

    public function script(string $trackingKey, ConversionGoalProfileService $goalProfiles): Response
    {
        $website = Website::where('tracking_key', $trackingKey)->firstOrFail();
        $goalProfile = $goalProfiles->forWebsite($website);
        $endpoint = json_encode(route('conversion-events.store', $website->tracking_key), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES);
        $eventKeys = array_keys($goalProfile['conversion_labels']);
        $allowedEvents = json_encode($eventKeys, JSON_UNESCAPED_SLASHES);
        $selectors = ['[data-conversion-event]'];
        if (in_array('phone_click', $eventKeys, true)) {
            $selectors[] = 'a[href^="tel:"]';
        }
        if (in_array('email_click', $eventKeys, true)) {
            $selectors[] = 'a[href^="mailto:"]';
        }
        if ($goalProfile['key'] === 'appointment_booking') {
            array_push($selectors, '[data-appointment-action]', 'a[href*="doctolib"]', 'a[href*="booking"]', 'a[href*="appointment"]', 'a[href*="rendez-vous"]', 'a[href*="/rdv"]');
        }
        $selector = implode(',', $selectors);
        $selector = json_encode($selector, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        $script = <<<'JS'
(() => {
    'use strict';

    const endpoint = __TRACKING_ENDPOINT__;
    const allowedEvents = __ALLOWED_EVENTS__;
    const selector = __TRACKING_SELECTOR__;

    const uuid = () => globalThis.crypto?.randomUUID?.() || 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, character => {
        const random = Math.random() * 16 | 0;
        return (character === 'x' ? random : (random & 0x3 | 0x8)).toString(16);
    });

    const pageUrl = () => `${location.origin}${location.pathname}`;

    const targetUrl = element => {
        const href = element.getAttribute('href');
        if (!href) return null;
        if (href.toLowerCase().startsWith('tel:')) return 'tel:';
        if (href.toLowerCase().startsWith('mailto:')) return 'mailto:';

        try {
            const parsed = new URL(href, location.origin);
            return `${parsed.origin}${parsed.pathname}`;
        } catch {
            return null;
        }
    };

    const deviceType = () => innerWidth < 768 ? 'mobile' : (innerWidth < 1024 ? 'tablet' : 'desktop');

    const send = (element, eventType) => {
        if (!allowedEvents.includes(eventType)) return;
        const payload = new URLSearchParams({
            event_uuid: uuid(),
            event_type: eventType,
            action_label: (element.dataset.appointmentAction || eventType).toLowerCase().replace(/[^a-z0-9_-]/g, '_').slice(0, 60),
            page_url: pageUrl(),
            device_type: deviceType(),
        });
        const target = targetUrl(element);
        const opportunity = element.dataset.growthOpportunity;
        if (target) payload.set('target_url', target);
        if (opportunity && /^\d+$/.test(opportunity)) payload.set('opportunity_id', opportunity);
        navigator.sendBeacon(endpoint, payload);
    };

    document.addEventListener('click', event => {
        const action = event.target.closest?.(selector);
        if (!action || action.tagName === 'FORM') return;
        const href = action.getAttribute('href')?.toLowerCase() || '';
        const eventType = action.dataset.conversionEvent || (href.startsWith('tel:') ? 'phone_click' : (href.startsWith('mailto:') ? 'email_click' : 'booking_click'));
        send(action, eventType);
    }, { capture: true });

    document.addEventListener('submit', event => {
        const form = event.target.closest?.('form[data-conversion-event],form[data-appointment-action]');
        if (form) send(form, form.dataset.conversionEvent || 'form_submit');
    }, { capture: true });
})();
JS;

        $script = str_replace('__TRACKING_ENDPOINT__', $endpoint, $script);
        $script = str_replace('__ALLOWED_EVENTS__', $allowedEvents, $script);
        $script = str_replace('__TRACKING_SELECTOR__', $selector, $script);

        return response($script, 200, [
            'Access-Control-Allow-Origin' => '*',
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
            'Cross-Origin-Resource-Policy' => 'cross-origin',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function canonicalHttpUrl(string $url): ?string
    {
        $parts = parse_url($url);

        if (! is_array($parts) || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || empty($parts['host'])) {
            return null;
        }

        $path = '/'.ltrim($parts['path'] ?? '/', '/');

        return strtolower($parts['scheme']).'://'.strtolower($parts['host']).($path === '/' ? '/' : rtrim($path, '/'));
    }

    private function canonicalTargetUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        if (in_array(strtolower($url), ['tel:', 'mailto:'], true)) {
            return strtolower($url);
        }

        return $this->canonicalHttpUrl($url);
    }

    private function normalizedHost(string $url): ?string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $host ? preg_replace('/^www\./', '', $host) : null;
    }
}
