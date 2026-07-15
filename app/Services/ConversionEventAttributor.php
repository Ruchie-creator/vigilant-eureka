<?php

namespace App\Services;

use App\Models\GrowthOpportunity;
use App\Models\Website;

class ConversionEventAttributor
{
    public function match(Website $website, string $pageUrl, ?int $opportunityId = null): ?GrowthOpportunity
    {
        if ($opportunityId) {
            $explicit = $website->growthOpportunities()->find($opportunityId);

            if ($explicit) {
                return $explicit;
            }
        }

        $normalizedPage = $this->normalizeUrl($pageUrl);

        if (! $normalizedPage) {
            return null;
        }

        return $website->growthOpportunities()
            ->whereIn('status', ['open', 'reviewed', 'in_progress'])
            ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
            ->orderByDesc('score')
            ->get()
            ->first(function (GrowthOpportunity $opportunity) use ($normalizedPage): bool {
                $candidate = $opportunity->related_page_url
                    ?: ($opportunity->source_type === 'page' ? $opportunity->source_value : null);

                return $candidate && $this->normalizeUrl($candidate) === $normalizedPage;
            });
    }

    private function normalizeUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $parts = parse_url($url);

        if (! is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $path = '/'.trim($parts['path'] ?? '/', '/');

        return strtolower($parts['host']).($path === '/' ? '/' : rtrim($path, '/'));
    }
}
