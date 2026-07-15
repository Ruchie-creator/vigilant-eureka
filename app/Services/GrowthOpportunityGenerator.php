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
    public function __construct(private readonly ConversionGoalProfileService $goalProfiles)
    {
    }

    public function generate(Website $website, CarbonInterface $start, CarbonInterface $end): int
    {
        $created = 0;
        $goal = $this->goalProfiles->forWebsite($website);
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

        foreach ($this->missingPriorityPages($website, $pages) as $priorityPageUrl) {
            $created += $this->save($website, [
                'source_type' => 'page',
                'source_value' => $priorityPageUrl,
                'opportunity_type' => 'priority_service_page',
                'opportunity_category' => 'service_page_growth',
                'problem' => 'This priority landing page represents an important offer but is not prominent in the synced Search Console page data.',
                'recommendation' => 'Strengthen internal links to this priority page, then make the '.$goal['cta_label'].' clear near the top.',
                'expected_result' => 'More '.$goal['audience_label'].' can discover the relevant offer and move toward '.$goal['primary_action_label'].'.',
                'priority' => 'high',
                'intent' => 'service_intent',
                'related_page_url' => $priorityPageUrl,
                'conversion_action' => 'Add stronger internal links and a visible '.$goal['cta_label'].' on the '.$goal['journey_label'].'.',
                'score' => 72,
            ], $this->virtualMetric($start, $end));
        }

        $mobile = GscDevice::where('website_id', $website->id)->where('device', 'mobile')->where('date_start', $start->toDateString())->where('date_end', $end->toDateString())->first();
        $desktop = GscDevice::where('website_id', $website->id)->where('device', 'desktop')->where('date_start', $start->toDateString())->where('date_end', $end->toDateString())->first();

        if ($mobile && (! $desktop || $mobile->clicks > $desktop->clicks || $mobile->ctr > $desktop->ctr)) {
            $created += $this->save($website, [
                'source_type' => 'device',
                'source_value' => 'mobile',
                'opportunity_type' => 'mobile_conversion',
                'opportunity_category' => 'conversion_improvement',
                'problem' => 'Mobile search performance is strong, so the mobile '.$goal['journey_label'].' matters most.',
                'recommendation' => 'Prioritize the mobile conversion experience with a visible '.$goal['cta_label'].', short value proposition, and low-friction next step.',
                'expected_result' => 'More mobile visitors can move toward '.$goal['primary_action_label'].'.',
                'priority' => 'high',
                'intent' => 'local_service_intent',
                'conversion_action' => 'Improve the mobile '.$goal['cta_label'].' and supporting conversion path.',
                'score' => $this->score($mobile, 'device', 'local_service_intent', null, true),
            ], $mobile);
        }

        return $created;
    }

    private function queryRules(Website $website, GscQuery $query, Collection $pages): int
    {
        $created = 0;
        $goal = $this->goalProfiles->forWebsite($website);
        $intent = $this->classifyQueryIntent($query->query, $website);
        $relatedPage = $this->mapQueryToPage($query->query, $pages, $website);
        $pageType = $relatedPage ? $this->pageType($relatedPage->page_url, $website) : 'unknown';
        $isServiceIntent = in_array($intent, ['service_intent', 'local_service_intent', 'condition_intent'], true);

        if (in_array($intent, ['competitor', 'irrelevant', 'unknown'], true)) {
            return 0;
        }

        if ($intent === 'review_reputation') {
            return $this->save($website, [
                'source_type' => 'query',
                'source_value' => $query->query,
                'opportunity_type' => 'review_trust_path',
                'opportunity_category' => 'reputation_conversion',
                'problem' => 'This search is reputation-led rather than service-discovery-led.',
                'recommendation' => 'Strengthen trust signals, review visibility, credentials, and the '.$goal['journey_label'].' on the relevant brand or representative page.',
                'expected_result' => 'Brand-aware visitors can reach a confident decision and move toward '.$goal['primary_action_label'].' faster.',
                'priority' => 'medium',
                'intent' => $intent,
                'related_page_url' => $relatedPage?->page_url,
                'conversion_action' => 'Add trust proof and a clear '.$goal['cta_label'].' near brand or representative information.',
                'score' => $this->score($query, 'query', $intent, $relatedPage?->page_url),
            ], $query);
        }

        if ($intent === 'branded_practitioner') {
            return $this->save($website, [
                'source_type' => 'query',
                'source_value' => $query->query,
                'opportunity_type' => 'branded_practitioner_conversion',
                'opportunity_category' => 'branded_visibility',
                'problem' => 'This brand or representative-name search is useful for trust and conversion but should not dominate acquisition growth.',
                'recommendation' => 'Improve the about or brand page with credentials, a '.$goal['cta_label'].', and links to the most relevant offer pages.',
                'expected_result' => 'Brand-aware visitors can convert more easily while high-intent pages carry acquisition growth.',
                'priority' => 'medium',
                'intent' => $intent,
                'related_page_url' => $relatedPage?->page_url,
                'conversion_action' => 'Connect branded traffic to high-intent pages and '.$goal['primary_action_label'].'.',
                'score' => $this->score($query, 'query', $intent, $relatedPage?->page_url),
            ], $query);
        }

        if ($query->impressions >= 30 && $query->ctr < 2 && $query->position <= 15 && $isServiceIntent) {
            $created += $this->save($website, [
                'source_type' => 'query',
                'source_value' => $query->query,
                'opportunity_type' => 'increase_service_ctr',
                'opportunity_category' => 'acquisition_growth',
                'problem' => 'This high-intent query has visibility but a low click-through rate.',
                'recommendation' => $this->queryRecommendation($website, $query, $relatedPage, 'Rewrite the title/meta for audience intent and align the page intro with the search need.'),
                'expected_result' => 'More visits from existing impressions and a clearer path toward '.$goal['primary_action_label'].'.',
                'priority' => $query->impressions >= 50 && $query->position <= 12 ? 'high' : 'medium',
                'intent' => $intent,
                'related_page_url' => $relatedPage?->page_url,
                'conversion_action' => 'Add or improve the '.$goal['cta_label'].' near the top of the mapped priority page.',
                'score' => $this->score($query, 'query', $intent, $relatedPage?->page_url),
            ], $query);
        }

        if ($isServiceIntent && $query->position >= 5 && $query->position <= 12 && $query->impressions >= 10) {
            $created += $this->save($website, [
                'source_type' => 'query',
                'source_value' => $query->query,
                'opportunity_type' => 'improve_service_position',
                'opportunity_category' => 'service_page_growth',
                'problem' => 'This service query is close to stronger visibility but needs more authority and relevance.',
                'recommendation' => $this->queryRecommendation($website, $query, $relatedPage, 'Strengthen the mapped priority page with better headings, internal links, relevant audience framing, and a '.$goal['cta_label'].'.'),
                'expected_result' => 'The page may earn more clicks from relevant visitors already searching for this service.',
                'priority' => 'high',
                'intent' => $intent,
                'related_page_url' => $relatedPage?->page_url,
                'conversion_action' => 'Improve content relevance and the '.$goal['cta_label'].' on the mapped priority page.',
                'score' => $this->score($query, 'query', $intent, $relatedPage?->page_url),
            ], $query);
        }

        if ($query->impressions >= 20 && $query->clicks === 0 && ($isServiceIntent || ($intent === 'informational' && $pageType === 'service_page'))) {
            $created += $this->save($website, [
                'source_type' => 'query',
                'source_value' => $query->query,
                'opportunity_type' => 'service_query_no_clicks',
                'opportunity_category' => $isServiceIntent ? 'acquisition_growth' : 'service_page_growth',
                'problem' => 'This useful query is visible in search but has not produced clicks.',
                'recommendation' => $relatedPage
                    ? $this->queryRecommendation($website, $query, $relatedPage, 'Rewrite title/meta, improve intent match, and add a stronger '.$goal['cta_label'].'.')
                    : 'Create or strengthen a page for this search intent.',
                'expected_result' => 'The search result can better match the target audience looking for this topic.',
                'priority' => $intent === 'informational' ? 'medium' : 'high',
                'intent' => $intent,
                'related_page_url' => $relatedPage?->page_url,
                'conversion_action' => 'Improve the snippet and connect the visitor to a relevant priority page and '.$goal['primary_action_label'].'.',
                'score' => $this->score($query, 'query', $intent, $relatedPage?->page_url),
            ], $query);
        }

        return $created;
    }

    private function pageRules(Website $website, GscPage $page): int
    {
        $created = 0;
        $goal = $this->goalProfiles->forWebsite($website);
        $type = $this->pageType($page->page_url, $website);

        if ($type === 'legal') {
            return 0;
        }

        if ($page->clicks >= 3 || $this->isPriorityServicePage($website, $page->page_url)) {
            $created += $this->save($website, [
                'source_type' => 'page',
                'source_value' => $page->page_url,
                'opportunity_type' => $type === 'service_page' ? 'service_page_conversion' : 'improve_booking_cta',
                'opportunity_category' => $type === 'service_page' ? 'service_page_growth' : 'conversion_improvement',
                'problem' => $type === 'service_page'
                    ? 'This priority offer page can support acquisition and the configured primary conversion.'
                    : 'This page receives search traffic and should guide visitors toward '.$goal['primary_action_label'].'.',
                'recommendation' => $type === 'blog'
                    ? 'Add a contextual link from this content to the relevant priority page and include a soft '.$goal['cta_label'].'.'
                    : 'Improve the page-specific '.$goal['cta_label'].', repeat it after key sections, and link to the most relevant '.$goal['journey_label'].'.',
                'expected_result' => 'More visitors can move toward '.$goal['primary_action_label'].' from existing traffic.',
                'priority' => $type === 'service_page' ? 'high' : 'medium',
                'intent' => $type === 'service_page' ? 'service_intent' : 'informational',
                'related_page_url' => $page->page_url,
                'conversion_action' => $this->conversionRecommendationForPage($page, $website),
                'score' => $this->score($page, 'page', $type === 'service_page' ? 'service_intent' : 'informational', $page->page_url),
            ], $page);
        }

        if ($type === 'service_page' && $page->impressions >= 50 && $page->ctr < 2 && $page->position <= 12) {
            $created += $this->save($website, [
                'source_type' => 'page',
                'source_value' => $page->page_url,
                'opportunity_type' => 'increase_service_page_ctr',
                'opportunity_category' => 'service_page_growth',
                'problem' => 'This priority offer page has strong visibility, low CTR, and conversion potential.',
                'recommendation' => 'Rewrite title/meta for target-audience intent and improve the landing page '.$goal['cta_label'].'.',
                'expected_result' => 'More clicks from existing impressions and more progress toward '.$goal['primary_action_label'].'.',
                'priority' => 'high',
                'intent' => 'service_intent',
                'related_page_url' => $page->page_url,
                'conversion_action' => 'Improve title/meta and the above-the-fold '.$goal['cta_label'].'.',
                'score' => $this->score($page, 'page', 'service_intent', $page->page_url),
            ], $page);
        }

        return $created;
    }

    public function classifyQueryIntent(string $query, ?Website $website = null): string
    {
        $q = $this->normalize($query);
        $profile = $website?->serviceProfile() ?? [];
        $names = $this->normalizedList($profile['practitioner_names'] ?? []);
        $brandTerms = $this->normalizedList($profile['brand_terms'] ?? []);
        $services = $this->normalizedList($profile['primary_services'] ?? []);
        $locations = $this->normalizedList($profile['target_locations'] ?? []);

        $hasName = $this->containsAny($q, $names);
        $hasService = $this->containsAny($q, array_merge($services, $this->serviceTerms()));
        $hasLocation = $this->containsAny($q, $locations);
        $hasCondition = $this->containsAny($q, $this->conditionTerms());

        if ($hasName && Str::contains($q, ['avis', 'review', 'opinion', 'temoignage'])) {
            return 'review_reputation';
        }

        if ($hasName || $this->containsAny($q, $brandTerms)) {
            return 'branded_practitioner';
        }

        if (Str::contains($q, ['doctolib', 'pages jaunes', 'mappy', 'site:', 'linkedin'])) {
            return 'competitor';
        }

        if (Str::contains($q, ['gratuit', 'emploi', 'formation', 'salaire']) || strlen($q) < 3) {
            return 'irrelevant';
        }

        if ($hasService && $hasLocation) {
            return 'local_service_intent';
        }

        if ($hasCondition) {
            return 'condition_intent';
        }

        if ($hasService || Str::contains($q, ['rendez vous', 'rdv', 'cabinet', 'pres de moi'])) {
            return $hasLocation ? 'local_service_intent' : 'service_intent';
        }

        if (Str::contains($q, ['c est quoi', 'definition', 'comment', 'pourquoi', 'symptome', 'bienfaits', 'danger'])) {
            return 'informational';
        }

        return 'unknown';
    }

    public function opportunityCategoryForIntent(string $intent): string
    {
        return match ($intent) {
            'service_intent', 'local_service_intent', 'condition_intent' => 'acquisition_growth',
            'review_reputation' => 'reputation_conversion',
            'branded_practitioner' => 'branded_visibility',
            'competitor', 'irrelevant', 'unknown' => 'low_value',
            default => 'conversion_improvement',
        };
    }

    public function mapQueryToPage(string $query, Collection $pages, ?Website $website = null): ?GscPage
    {
        $q = $this->normalize($query);
        $profile = $website?->serviceProfile() ?? [];
        $priorityPages = $profile['priority_pages'] ?? [];

        foreach ($priorityPages as $priorityPage) {
            $priorityPath = $this->normalize(parse_url($priorityPage, PHP_URL_PATH) ?: $priorityPage);
            if ($this->containsAny($priorityPath, explode(' ', $q)) || $this->containsAny($q, explode('-', str_replace('/', ' ', $priorityPath)))) {
                $match = $pages->first(fn (GscPage $page) => $this->sameUrl($page->page_url, $priorityPage));
                if ($match) {
                    return $match;
                }
            }
        }

        $map = [
            ['terms' => ['cranio', 'crani', 'sacrale'], 'slug' => 'cranio'],
            ['terms' => ['drainage', 'lymphatique'], 'slug' => 'drainage'],
            ['terms' => ['sport'], 'slug' => 'sport'],
            ['terms' => ['lyon 2', 'lyon-2'], 'slug' => 'lyon-2'],
            ['terms' => ['osteopathe', 'osteopathie'], 'slug' => 'osteo'],
            ['terms' => ['traitement', 'traitements', 'sexologie'], 'slug' => 'traitements-sexologie'],
            ['terms' => ['ejaculation', 'precoce'], 'slug' => 'ejaculation-precoce'],
            ['terms' => ['desir', 'libido'], 'slug' => 'desir-sexuel'],
            ['terms' => ['orgasme'], 'slug' => 'orgasme'],
        ];

        foreach ($map as $rule) {
            if (Str::contains($q, $rule['terms'])) {
                $match = $pages->first(fn (GscPage $page) => Str::contains($this->normalize($page->page_url), $rule['slug']));
                if ($match) {
                    return $match;
                }
            }
        }

        return $pages->sortByDesc(fn (GscPage $page) => $this->isPriorityServicePage($website, $page->page_url) ? $page->clicks + 100 : $page->clicks)->first();
    }

    public function pageType(string $url, ?Website $website = null): string
    {
        $path = $this->normalize(parse_url($url, PHP_URL_PATH) ?: '/');

        if ($path === '/' || $path === '') {
            return 'homepage';
        }

        if (Str::contains($path, ['mentions-legales', 'politique', 'confidentialite', 'conditions', 'privacy', 'legal'])) {
            return 'legal';
        }

        if (Str::contains($path, ['blog', 'article', 'actualite', 'ressource'])) {
            return 'blog';
        }

        if ($this->isPriorityServicePage($website, $url) || Str::contains($path, ['traitements', 'sexologie', 'ejaculation', 'desir', 'orgasme', 'therapie', 'osteopath', 'drainage', 'cranio', 'sport', 'digestif', 'consultation', 'auriculo', 'pricing', 'signup', 'register', 'trial', 'features', 'loyalty', 'rewards', 'campaigns', 'business'])) {
            return 'service_page';
        }

        return 'unknown';
    }

    public function isPriorityServicePage(?Website $website, string $url): bool
    {
        if (! $website) {
            return false;
        }

        foreach ($website->serviceProfile()['priority_pages'] as $priorityPage) {
            if ($this->sameUrl($url, $priorityPage)) {
                return true;
            }
        }

        return false;
    }

    public function conversionRecommendationForPage(GscPage $page, ?Website $website = null): string
    {
        $goal = $website ? $this->goalProfiles->forWebsite($website) : $this->goalProfiles->profiles()['custom'];

        return match ($this->pageType($page->page_url, $website)) {
            'homepage' => 'Check the hero '.$goal['cta_label'].' and '.$goal['journey_label'].'.',
            'service_page' => 'Improve the page-specific '.$goal['cta_label'].' and add internal links from high-traffic pages.',
            'blog' => 'Add a contextual link to the relevant priority page and '.$goal['primary_action_label'].'.',
            'legal' => 'No conversion action needed.',
            default => $page->clicks > 0 ? 'Clarify the next action toward '.$goal['primary_action_label'].'.' : 'Review title/meta or noindex if low value.',
        };
    }

    private function queryRecommendation(Website $website, GscQuery $query, ?GscPage $relatedPage, string $fallback): string
    {
        $location = collect($website->serviceProfile()['target_locations'])->first() ?: $website->target_location ?: 'the target location';
        $goal = $this->goalProfiles->forWebsite($website);

        if ($relatedPage) {
            return $fallback.' Use '.$location.' relevance only where it matches the website settings.';
        }

        return 'Create or strengthen a priority page for "'.$query->query.'" and connect it to the '.$goal['journey_label'].' for '.$location.'.';
    }

    private function missingPriorityPages(Website $website, Collection $pages): array
    {
        return collect($website->serviceProfile()['priority_pages'])
            ->reject(fn (string $priorityPage) => $pages->contains(fn (GscPage $page) => $this->sameUrl($page->page_url, $priorityPage)))
            ->values()
            ->all();
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
            'local_service_intent' => 28,
            'service_intent', 'condition_intent' => 24,
            'informational' => 8,
            'review_reputation' => 2,
            'branded_practitioner' => -12,
            'competitor', 'irrelevant', 'unknown' => -35,
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
            'clicks' => $metric->clicks ?? 0,
            'impressions' => $metric->impressions ?? 0,
            'ctr' => $metric->ctr ?? 0,
            'position' => $metric->position ?? 0,
            'status' => 'open',
            'opportunity_category' => $data['opportunity_category'] ?? $this->opportunityCategoryForIntent($data['intent'] ?? 'unknown'),
        ]);

        if ($opportunity) {
            $opportunity->update($payload);
            return 0;
        }

        GrowthOpportunity::create(array_merge($attributes, $payload));

        return 1;
    }

    private function virtualMetric(CarbonInterface $start, CarbonInterface $end): object
    {
        return (object) [
            'date_start' => $start->toDateString(),
            'date_end' => $end->toDateString(),
            'clicks' => 0,
            'impressions' => 0,
            'ctr' => 0,
            'position' => 0,
        ];
    }

    private function serviceTerms(): array
    {
        return ['osteopathe', 'osteopathie', 'drainage lymphatique', 'cranio', 'crani', 'sexologue', 'sexologie', 'consultation', 'therapie', 'traitement', 'traitements', 'sport', 'sante sexuelle'];
    }

    private function conditionTerms(): array
    {
        return ['trouble', 'troubles', 'douleur', 'douleurs', 'symptome', 'baisse desir', 'desir sexuel', 'ejaculation precoce', 'orgasme', 'digestif', 'stress', 'libido'];
    }

    private function normalizedList(array $values): array
    {
        return collect($values)->map(fn ($value) => $this->normalize((string) $value))->filter()->values()->all();
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        $needles = array_values(array_filter($needles, fn ($needle) => filled($needle) && strlen((string) $needle) > 1));

        return $needles !== [] && Str::contains($haystack, $needles);
    }

    private function normalize(string $value): string
    {
        return Str::of($value)->lower()->ascii()->replace(['_', '+'], ' ')->squish()->toString();
    }

    private function sameUrl(string $first, string $second): bool
    {
        return rtrim($this->normalize($first), '/') === rtrim($this->normalize($second), '/');
    }
}
