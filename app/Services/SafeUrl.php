<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class SafeUrl
{
    public static function assertPublicHttpUrl(string $url): void
    {
        $parts = parse_url($url);

        if (! in_array($parts['scheme'] ?? null, ['http', 'https'], true) || empty($parts['host'])) {
            throw ValidationException::withMessages(['url' => 'Enter a valid public http or https URL.']);
        }

        $host = $parts['host'];

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (! filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw ValidationException::withMessages(['url' => 'Private, internal, and reserved network URLs cannot be scanned.']);
            }

            return;
        }

        $records = @dns_get_record($host, DNS_A + DNS_AAAA);

        if ($records === false || $records === []) {
            throw ValidationException::withMessages(['url' => 'The host could not be resolved.']);
        }

        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;

            if ($ip && ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw ValidationException::withMessages(['url' => 'Private, internal, and reserved network URLs cannot be scanned.']);
            }
        }
    }
}
