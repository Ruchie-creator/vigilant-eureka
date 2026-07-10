<?php

namespace App\Services;

use App\Models\SeoAudit;
use App\Models\Website;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class SeoScanner
{
    public function scan(Website $website): SeoAudit
    {
        SafeUrl::assertPublicHttpUrl($website->url);

        try {
            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->accept('text/html')
                ->withUserAgent('clint-rono.dev SEO Monitor')
                ->get($website->url);

            $html = (string) $response->body();
            $fields = $this->extractFields($html);
            $sitemapAvailable = $this->assetAvailable($website->url, '/sitemap.xml');
            $robotsAvailable = $this->assetAvailable($website->url, '/robots.txt');
            $missing = $this->missingFields($fields, $sitemapAvailable, $robotsAvailable);
            $isIndexable = $response->successful()
                && ! Str::contains(Str::lower($fields['robots_meta'] ?? ''), ['noindex', 'none']);

            return SeoAudit::create([
                'website_id' => $website->id,
                'http_status' => $response->status(),
                'page_title' => $fields['page_title'],
                'meta_description' => $fields['meta_description'],
                'h1' => $fields['h1'],
                'canonical_url' => $fields['canonical_url'],
                'robots_meta' => $fields['robots_meta'],
                'og_title' => $fields['og_title'],
                'og_description' => $fields['og_description'],
                'sitemap_available' => $sitemapAvailable,
                'robots_txt_available' => $robotsAvailable,
                'is_indexable' => $isIndexable,
                'missing_fields' => $missing,
                'recommendations' => $this->recommendations($missing, $isIndexable, $response->status()),
            ]);
        } catch (Throwable $exception) {
            return SeoAudit::create([
                'website_id' => $website->id,
                'raw_error' => app()->isProduction() ? 'Scan failed. Check URL and hosting access.' : $exception->getMessage(),
                'missing_fields' => [],
                'recommendations' => ['Confirm the site is publicly reachable and allows standard HTTP requests.'],
            ]);
        }
    }

    private function extractFields(string $html): array
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html ?: '<html></html>');
        $xpath = new \DOMXPath($dom);

        return [
            'page_title' => $this->text($xpath, '//title'),
            'meta_description' => $this->meta($xpath, 'description'),
            'h1' => $this->text($xpath, '//h1'),
            'canonical_url' => $this->attr($xpath, '//link[translate(@rel, "CANONICAL", "canonical")="canonical"]', 'href'),
            'robots_meta' => $this->meta($xpath, 'robots'),
            'og_title' => $this->property($xpath, 'og:title'),
            'og_description' => $this->property($xpath, 'og:description'),
        ];
    }

    private function assetAvailable(string $baseUrl, string $path): bool
    {
        $parts = parse_url($baseUrl);
        $url = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '').$path;

        try {
            return Http::timeout(6)->connectTimeout(3)->head($url)->successful();
        } catch (Throwable) {
            return false;
        }
    }

    private function text(\DOMXPath $xpath, string $query): ?string
    {
        $node = $xpath->query($query)->item(0);

        return $node ? Str::of($node->textContent)->squish()->limit(255, '')->toString() : null;
    }

    private function meta(\DOMXPath $xpath, string $name): ?string
    {
        return $this->attr($xpath, '//meta[translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="'.$name.'"]', 'content');
    }

    private function property(\DOMXPath $xpath, string $property): ?string
    {
        return $this->attr($xpath, '//meta[@property="'.$property.'"]', 'content');
    }

    private function attr(\DOMXPath $xpath, string $query, string $attribute): ?string
    {
        $node = $xpath->query($query)->item(0);

        return $node && $node->attributes?->getNamedItem($attribute)
            ? Str::of($node->attributes->getNamedItem($attribute)->nodeValue)->squish()->limit(2048, '')->toString()
            : null;
    }

    private function missingFields(array $fields, bool $sitemapAvailable, bool $robotsAvailable): array
    {
        $missing = [];

        foreach ($fields as $key => $value) {
            if (blank($value)) {
                $missing[] = $key;
            }
        }

        if (! $sitemapAvailable) {
            $missing[] = 'sitemap.xml';
        }

        if (! $robotsAvailable) {
            $missing[] = 'robots.txt';
        }

        return $missing;
    }

    private function recommendations(array $missing, bool $isIndexable, ?int $status): array
    {
        $recommendations = [];

        if ($status && ($status < 200 || $status >= 400)) {
            $recommendations[] = 'Fix the homepage HTTP response so search engines can reliably access the page.';
        }

        if (! $isIndexable) {
            $recommendations[] = 'Review robots directives and make sure important pages are indexable.';
        }

        foreach ($missing as $field) {
            $recommendations[] = 'Add or improve '.$field.' for clearer search and social visibility.';
        }

        return $recommendations ?: ['Maintain current SEO foundations and monitor content opportunities weekly.'];
    }
}
