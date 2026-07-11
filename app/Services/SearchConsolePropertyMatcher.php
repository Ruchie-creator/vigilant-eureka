<?php

namespace App\Services;

class SearchConsolePropertyMatcher
{
    public static function matches(string $websiteUrl, string $propertyUrl): bool
    {
        $websiteHost = self::host($websiteUrl);
        $propertyHost = self::host($propertyUrl);

        if (! $websiteHost || ! $propertyHost) {
            return false;
        }

        return $websiteHost === $propertyHost || str_ends_with($websiteHost, '.'.$propertyHost);
    }

    public static function host(string $value): ?string
    {
        $value = trim($value);

        if (str_starts_with($value, 'sc-domain:')) {
            return self::normalizeHost(substr($value, 10));
        }

        $host = parse_url($value, PHP_URL_HOST);

        if (! $host && ! str_contains($value, '://')) {
            $host = parse_url('https://'.$value, PHP_URL_HOST);
        }

        return self::normalizeHost((string) $host);
    }

    private static function normalizeHost(string $host): ?string
    {
        $host = strtolower(trim($host));
        $host = preg_replace('/^www\./', '', $host);

        return filled($host) ? $host : null;
    }
}
