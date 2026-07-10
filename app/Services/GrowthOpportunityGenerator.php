<?php

namespace App\Services;

use App\Models\GscDevice;
use App\Models\GscPage;
use App\Models\GscQuery;
use App\Models\GrowthOpportunity;
use App\Models\Website;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GrowthOpportunityGenerator
{
    public function generate(Website $website, CarbonInterface $start, CarbonInterface $end): int
    {
        $created = 0;
        $pages = GscPage::where('website_id', $website->id)
            ->where('date_start', $start->toDateString())
            ->where('date_end', $end->toDateString())
            ->get();

        foreach (GscQuery::where('website_id', $website->id)->where('date_start', $start->toDateString())->where('date_end', $end->toDateString())->get() as $query) {
            $created += $this->queryRules($website, $query, $pages);
        }

        foreach ($pages as $page) {
            $created += $this->pageRules($website, $page);
        }

        $mobile = GscDevice::where('website_id', $website->id)->where('device', 'mobile')->where('date_start', $start->toDateString())->where('date_end', $end->toDateString())->first();
        $desktop = GscDevice::where('website_id', $website->id)->where('device', 'desktop')->where('date_start', $start->toDateString())->where('date_end', $end->toDateString())->first();

        if ($mobile && (! $desktop || $mobile->clicks > $desktop->clicks || $mobile->ctr > $desktop->ctr)) {
            $created += $this->save($website, [
                'source_type' => 'device',
                'source_value' => 'mobile',
                'opportunity_type' => 'mobile_conversion',
                'problem' => 'Mobile search performance is stronger than desktop, so the mobile appointment path matters most.',
                'recommendation' => 'Prioritize mobile-first booking experience: sticky appointment button, tap-to-call, shorter intro, and a clear appointment section.',
                'expected_result' => 'More appointment actions from mobile visitors.',
                'priority' => 'high',
                'intent' => 'patient_intent',
                'conversion_action' => 'Improve mobile booking CTA and tap-to-call path.',
                'score' => $this->score($mobile, 'device', 'patient_intent', null, true),
            ], $mobile);
        }

        return $created;
    }

    private function queryRules(Website $website, GscQuery $query, Collection $pages): int
    {
        $created = 0;
        $intent = $this->classifyQueryIntent($query->query);
        $relatedPage = $this->mapQueryToPage($query->query, $pages);
        $pageType = $relatedPage ? $this->pageType($relatedPage->page_url) : 'unknown';
        $isUsefulIntent = in_array($intent, ['patient_intent', 'local_service'], true) || ($intent === 'informational' && $pageType === 'service_page');

        if (in_array($intent, ['branded', 'competitor', 'irrelevant'], true)) {
            return 0;
        }

        if ($query->impressions >= 30 && $query->ctr < 2 && $query->position <= 15 && $isUsefulIntent) {
            $created += $this->save($website, [
                'source_type' => 'query',
                'source_value' => $query->query,
                'opportunity_type' => 'increase_ctr',
                'problem' => 'This patient-intent query has visibility but a low click-through rate.',
                'recommendation' => $relatedPage
                    ? 'Rewrite the title/meta for patient intent and align the page intro with the search need.'
                    : 'Create or strengthen a page for this search intent, then write a patient-focused title and meta description.',
                'expected_result' => 'More visits from existing impressions and a clearer path toward appointment actions.',
                'priority' => $query->impressions >= 50 && $query->position <= 12 ? 'high' : 'medium',
                'intent' => $intent,
                'related_page_url' => $relatedPage?->page_url,
                'conversion_action' => 'Add or improve an appointment CTA near the top of the mapped page.',
                'score' => $this->score($query, 'query', $intent, $relatedPage?->page_url),
            ], $query);
        }

        if ($isUsefulIntent && $query->position >= 5 && $query->position <= 12 && $query->impressions >= 10) {
            $created += $this->save($website, [
                'source_type' => 'query',
                'source_value' => $query->query,
                'opportunity_type' => 'improve_position',
                'problem' => 'This patient-intent query is close to stronger visibility but needs more authority and relevance.',
                'recommendation' => 'Strengthen the mapped service page with better headings, internal links, local Lyon relevance, and a booking CTA.',
                'expected_result' => 'The page may earn more clicks from relevant visitors already searching for this service.',
                'priority' => 'high',
                'intent' => $intent,
                'related_page_url' => $relatedPage?->page_url,
                'conversion_action' => 'Improve content relevance and booking CTA on the mapped service page.',
                'score' => $this->score($query, 'query', $intent, $relatedPage?->page_url),
            ], $query);
        }

        if ($query->impressions >= 20 && $query->clicks === 0 && $isUsefulIntent) {
            $created += $this->save($website, [
                'source_type' => 'query',
                'source_value' => $query->query,
                'opportunity_type' => 'update_existing_page',
                'problem' => 'This useful query is visible in search but has not produced clicks.',
                'recommendation' => $relatedPage
                    ? 'Rewrite title/meta, improve intent match, and add a stronger appointment CTA.'
                    : 'Create or strengthen a page for this search intent.',
                'expected_result' => 'The search result can better match patients looking for this topic.',
                'priority' => $intent === 'informational' ? 'medium' : 'high',
                'intent' => $intent,
                'related_page_url' => $relatedPage?->page_url,
                'conversion_action' => 'Improve snippet and connect the visitor to a relevant service page.',
                'score' => $this->score($query, 'query', $intent, $relatedPage?->page_url),
            ], $query);
        }

        return $created;
    }

    private function pageRules(Website $website, GscPage $page): int
    {
        $created = 0;
        $type = $this->pageType($page->page_url);

        if ($type === 'legal') {
            return 0;
        }

        if ($page->clicks >= 3) {
            $created += $this->save($website, [
                'source_type' => 'page',
                'source_value' => $page->page_url,
                'opportunity_type' => 'improve_booking_cta',
                'problem' => 'This page already receives search traffic. It should be optimized to convert visitors into appointment actions.',
                'recommendation' => $type === 'blog'
                    ? 'Add a contextual link from this content to the relevant service page and include a soft appointment CTA.'
                    : 'Add or improve booking CTA above the fold, repeat appointment button after key sections, and track booking clicks.',
                'expected_result' => 'More appointment actions from existing traffic.',
                'priority' => $type === 'service_page' ? 'high' : 'medium',
                'intent' => $type === 'service_page' ? 'patient_intent' : 'informational',
                'related_page_url' => $page->page_url,
                'conversion_action' => $this->conversionRecommendationForPage($page),
                'score' => $this->score($page, 'page', $type === 'service_page' ? 'patient_intent' : 'informational', $page->page_url),
            ], $page);
        }

        if ($type === 'service_page' && $page->impressions >= 50 && $page->ctr < 2 && $page->position <= 12) {
            $created += $this->save($website, [
                'source_type' => 'page',
                'source_value' => $page->page_url,
                'opportunity_type' => 'increase_ctr_and_conversion',
                'problem' => 'This service page has strong visibility, low CTR, and conversion potential.',
                'recommendation' => 'Rewrite title/meta for patient intent and improve the landing page appointment CTA.',
                'expected_result' => 'More clicks from existing impressions and more appointment actions from visitors.',
                'priority' => 'high',
                'intent' => 'patient_intent',
                'related_page_url' => $page->page_url,
                'conversion_action' => 'Improve title/meta and above-the-fold booking CTA.',
                'score' => $this->score($page, 'page', 'patient_intent', $page->page_url),
            ], $page);
        }

        return $created;
    }

    public function classifyQueryIntent(string $query): string
    {
        $q = Str::ascii(Str::lower($query));

        if (Str::contains($q, ['thomas baptiste weiss', 'baptiste weiss', 'site:', 'marjorie'])) {
            return 'branded';
        }

        if (Str::contains($q, ['ikopositive', 'doctolib', 'pages jaunes', 'mappy'])) {
            return 'competitor';
        }

        if (Str::contains($q, ['gratuit', 'emploi', 'formation', 'salaire']) || strlen($q) < 3) {
            return 'irrelevant';
        }

        if (Str::contains($q, ['lyon', 'pres de moi', 'rendez vous', 'rdv', 'cabinet'])) {
            return 'local_service';
        }

        if (Str::contains($q, ['osteopathe', 'osteopathie', 'drainage lymphatique', 'cranio', 'crani', 'auriculo', 'sexologue', 'therapie', 'sport', 'senior'])) {
            return 'patient_intent';
        }

        if (Str::contains($q, ['c est quoi', 'definition', 'comment', 'pourquoi', 'symptome', 'bienfaits', 'danger'])) {
            return 'informational';
        }

        return 'unknown';
    }

    public function mapQueryToPage(string $query, Collection $pages): ?GscPage
    {
        $q = Str::ascii(Str::lower($query));
        $map = [
            ['terms' => ['cranio', 'crani', 'sacrale'], 'slug' => 'cranio'],
            ['terms' => ['drainage', 'lymphatique'], 'slug' => 'drainage'],
            ['terms' => ['sport'], 'slug' => 'sport'],
            ['terms' => ['lyon 2', 'lyon-2'], 'slug' => 'lyon-2'],
            ['terms' => ['senior'], 'slug' => 'senior'],
            ['terms' => ['osteopathe', 'osteopathie'], 'slug' => 'osteo'],
        ];

        foreach ($map as $rule) {
            if (Str::contains($q, $rule['terms'])) {
                $match = $pages->first(fn (GscPage $page) => Str::contains(Str::ascii(Str::lower($page->page_url)), $rule['slug']));
                if ($match) {
                    return $match;
                }
            }
        }

        return $pages->sortByDesc('clicks')->first();
    }

    public function pageType(string $url): string
    {
        $path = Str::lower(parse_url($url, PHP_URL_PATH) ?: '/');

        if ($path === '/' || $path === '') {
            return 'homepage';
        }

        if (Str::contains($path, ['mentions-legales', 'politique', 'confidentialite', 'conditions', 'privacy', 'legal'])) {
            return 'legal';
        }

        if (Str::contains($path, ['blog', 'article', 'actualite', 'ressource'])) {
            return 'blog';
        }

        if (Str::contains($path, ['osteopath', 'therapie', 'drainage', 'cranio', 'sport', 'senior', 'consultation', 'sexolog', 'auriculo'])) {
            return 'service_page';
        }

        return 'unknown';
    }

    public function conversionRecommendationForPage(GscPage $page): string
    {
        return match ($this->pageType($page->page_url)) {
            'homepage' => 'Check hero CTA and appointment path.',
            'service_page' => 'Improve appointment CTA.',
            'blog' => 'Add contextual link to the relevant service page.',
            'legal' => 'No conversion action needed.',
            default => $page->clicks > 0 ? 'Clarify the next appointment action.' : 'Review title/meta or noindex if low value.',
        };
    }

    private function score(object $metric, string $sourceType, string $intent, ?string $pageUrl, bool $mobileStrong = false): int
    {
        $score = 0;
        $score += min(35, (int) floor(($metric->impressions ?? 0) / 10));
        $score += min(20, (int) (($metric->clicks ?? 0) * 3));

        if (($metric->ctr ?? 0) < 2 && ($metric->impressions ?? 0) >= 30) {
            $score += 20;
        }

        if (($metric->position ?? 99) <= 12) {
            $score += 15;
        }

        $score += match ($sourceType) {
            'page' => 12,
            'query' => 8,
            'device' => 10,
            default => 0,
        };

        $score += match ($intent) {
            'patient_intent', 'local_service' => 20,
            'informational' => 8,
            'branded' => -10,
            'competitor', 'irrelevant' => -30,
            default => 0,
        };

        if ($pageUrl && $this->pageType($pageUrl) === 'service_page') {
            $score += 15;
        }

        if ($mobileStrong) {
            $score += 15;
        }

        return max(0, min(100, $score));
    }

    private function save(Website $website, array $data, object $metric): int
    {
        $sourceValue = Str::limit($data['source_value'], 512, '');
        $attributes = [
            'website_id' => $website->id,
            'source_type' => $data['source_type'],
            'source_hash' => hash('sha256', $sourceValue),
            'opportunity_type' => $data['opportunity_type'],
            'date_start' => $metric->date_start,
            'date_end' => $metric->date_end,
        ];

        $opportunity = GrowthOpportunity::where($attributes)->where('status', 'open')->first();

        $payload = array_merge($data, [
            'source_value' => $sourceValue,
            'source_hash' => $attributes['source_hash'],
            'clicks' => $metric->clicks,
            'impressions' => $metric->impressions,
            'ctr' => $metric->ctr,
            'position' => $metric->position,
            'status' => 'open',
        ]);

        if ($opportunity) {
            $opportunity->update($payload);
            return 0;
        }

        GrowthOpportunity::create(array_merge($attributes, $payload));

        return 1;
    }
}
