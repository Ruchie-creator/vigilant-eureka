<?php

namespace App\Services;

use App\Models\MarketingTask;
use App\Models\SeoAudit;
use App\Models\Website;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class AiMarketingInsightService
{
    public function __construct(private readonly ConversionGoalProfileService $goalProfiles)
    {
    }

    public function generate(Website $website, ?SeoAudit $audit, ?Collection $recentInsights = null, ?Collection $recentTasks = null): array
    {
        $apiKey = config('services.openai.key');

        if (blank($apiKey)) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        try {
            $context = $this->insightContext($website, $audit, $recentInsights, $recentTasks);

            $response = Http::timeout((int) config('services.openai.timeout', 20))
                ->withToken($apiKey)
                ->acceptJson()
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.model', 'gpt-4o-mini'),
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $this->systemPrompt()],
                        ['role' => 'user', 'content' => $this->userPrompt($context)],
                    ],
                    'temperature' => 0.3,
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('OpenAI request failed with status '.$response->status().'.');
            }

            $content = data_get($response->json(), 'choices.0.message.content');
            $decoded = json_decode((string) $content, true, flags: JSON_THROW_ON_ERROR);

            return $this->validatedInsight($decoded, $context);
        } catch (Throwable $exception) {
            Log::warning('OpenAI marketing insight generation failed.', [
                'website_id' => $website->id,
                'audit_id' => $audit?->id,
                'error' => $exception->getMessage(),
            ]);

            throw new RuntimeException('AI insight generation is temporarily unavailable.', previous: $exception);
        }
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are a growth and conversion analyst for goal-based website workspaces.

Safety rules:
- Use the workspace's configured primary conversion goal, supporting conversions, target audience, and business model.
- Use only connected data sources included in the supplied context. Never invent product, revenue, patient, customer, or campaign data.
- Do not request, infer, store, or process sensitive personal or medical data.
- Do not make medical claims or guarantee business outcomes.
- Treat campaigns, messages, and website changes as proposed work that requires human approval before execution.
- Focus on evidence-backed visibility, content clarity, technical quality, acquisition, activation, conversion, and retention as appropriate to the configured goal.

Return only valid JSON with these keys:
insight_key, title, summary, why_it_matters, priority, category, recommendation, expected_result, suggested_task, affected_source_type, affected_source_value.

Allowed priority values: low, medium, high.
Allowed category values: seo, content, technical, conversion, local_seo.
PROMPT;
    }

    private function insightContext(Website $website, ?SeoAudit $audit, ?Collection $recentInsights, ?Collection $recentTasks): array
    {
        $classifier = app(GrowthOpportunityGenerator::class);
        $goalProfile = $this->goalProfiles->forWebsite($website);
        $latestSync = Schema::hasTable('gsc_syncs') ? $website->latestGscSync : null;
        $pages = $website->gscPages()
            ->when($latestSync, fn ($query) => $query->where('date_start', $latestSync->date_start->toDateString())->where('date_end', $latestSync->date_end->toDateString()))
            ->orderByDesc('impressions')
            ->limit(20)
            ->get(['page_url', 'clicks', 'impressions', 'ctr', 'position', 'date_start', 'date_end']);
        $queries = $website->gscQueries()
            ->when($latestSync, fn ($query) => $query->where('date_start', $latestSync->date_start->toDateString())->where('date_end', $latestSync->date_end->toDateString()))
            ->orderByDesc('impressions')
            ->limit(30)
            ->get(['query', 'clicks', 'impressions', 'ctr', 'position', 'date_start', 'date_end']);

        $queriesByIntent = $queries->map(function ($query) use ($website, $classifier, $pages) {
            $intent = $classifier->classifyQueryIntent($query->query, $website);
            $relatedPage = $classifier->mapQueryToPage($query->query, $pages, $website);

            return [
                'query' => $query->query,
                'intent' => $intent,
                'category' => $classifier->opportunityCategoryForIntent($intent),
                'related_page' => $relatedPage?->page_url,
                'clicks' => $query->clicks,
                'impressions' => $query->impressions,
                'ctr' => $query->ctr,
                'position' => $query->position,
            ];
        });

        $pageRows = $pages->map(fn ($page) => [
            'page_url' => $page->page_url,
            'page_type' => $classifier->pageType($page->page_url, $website),
            'is_priority_service_page' => $classifier->isPriorityServicePage($website, $page->page_url),
            'clicks' => $page->clicks,
            'impressions' => $page->impressions,
            'ctr' => $page->ctr,
            'position' => $page->position,
        ]);

        $rowStart = collect([$pages->min('date_start'), $queries->min('date_start')])->filter()->min();
        $rowEnd = collect([$pages->max('date_end'), $queries->max('date_end')])->filter()->max();
        $syncContext = $latestSync ? [
            'property_url' => $latestSync->property_url,
            'date_start' => $latestSync->date_start->toDateString(),
            'date_end' => $latestSync->date_end->toDateString(),
            'search_type' => $latestSync->search_type,
            'country_filter' => $latestSync->country_filter,
            'device_filter' => $latestSync->device_filter,
            'total_clicks' => $latestSync->total_clicks,
            'total_impressions' => $latestSync->total_impressions,
            'average_ctr' => $latestSync->average_ctr,
            'average_position' => $latestSync->average_position,
        ] : ($rowStart && $rowEnd ? [
            'property_url' => $website->searchConsoleSite?->site_url ?: $website->url,
            'date_start' => $rowStart->toDateString(),
            'date_end' => $rowEnd->toDateString(),
            'search_type' => 'web',
            'country_filter' => null,
            'device_filter' => null,
            'total_clicks' => $pages->sum('clicks'),
            'total_impressions' => $pages->sum('impressions'),
            'average_ctr' => round((float) $pages->avg('ctr'), 2),
            'average_position' => round((float) $pages->avg('position'), 1),
        ] : null);

        $serviceCandidates = $queriesByIntent
            ->whereIn('intent', ['service_intent', 'local_service_intent', 'condition_intent'])
            ->sortByDesc(fn ($row) => ($row['impressions'] * 2) + ($row['clicks'] * 10) + ((float) $row['position'] <= 12 ? 50 : 0))
            ->take(8)
            ->values();

        $brandedCandidates = $queriesByIntent
            ->whereIn('intent', ['branded_practitioner', 'review_reputation'])
            ->sortByDesc('impressions')
            ->take(6)
            ->values();

        $lowValueCandidates = $queriesByIntent
            ->whereIn('intent', ['competitor', 'irrelevant', 'unknown'])
            ->sortByDesc('impressions')
            ->take(6)
            ->values();

        $priorityPages = $pageRows
            ->where('is_priority_service_page', true)
            ->sortByDesc('impressions')
            ->take(8)
            ->values();

        $candidates = collect()
            ->merge($priorityPages->map(fn ($page) => [
                'insight_key' => 'service-page:'.md5($page['page_url']),
                'group' => 'high_intent_acquisition',
                'source_type' => 'page',
                'source_value' => $page['page_url'],
                'related_page' => $page['page_url'],
                'why_candidate' => 'Priority landing page with Search Console visibility.',
                ...$page,
            ]))
            ->merge($serviceCandidates->map(fn ($query) => [
                'insight_key' => 'service-query:'.md5($query['query'].'|'.($query['related_page'] ?? '')),
                'group' => 'high_intent_acquisition',
                'source_type' => 'query',
                'source_value' => $query['query'],
                'why_candidate' => 'High-intent query related to the configured offer and target audience.',
                ...$query,
            ]))
            ->merge($brandedCandidates->map(fn ($query) => [
                'insight_key' => 'branded-query:'.md5($query['query']),
                'group' => 'branded_reputation',
                'source_type' => 'query',
                'source_value' => $query['query'],
                'why_candidate' => 'Brand, representative-name, or reputation query.',
                ...$query,
            ]))
            ->merge($lowValueCandidates->map(fn ($query) => [
                'insight_key' => 'low-value-query:'.md5($query['query']),
                'group' => 'low_value_irrelevant',
                'source_type' => 'query',
                'source_value' => $query['query'],
                'why_candidate' => 'Low-value, competitor, irrelevant, or unclear query.',
                ...$query,
            ]))
            ->values();

        $auditData = $audit ? [
            'http_status' => $audit->http_status,
            'page_title' => $audit->page_title,
            'meta_description' => $audit->meta_description,
            'h1' => $audit->h1,
            'canonical_url' => $audit->canonical_url,
            'robots_meta' => $audit->robots_meta,
            'og_title' => $audit->og_title,
            'og_description' => $audit->og_description,
            'sitemap_available' => $audit->sitemap_available,
            'robots_txt_available' => $audit->robots_txt_available,
            'is_indexable' => $audit->is_indexable,
            'missing_fields' => $audit->missing_fields ?? [],
            'current_recommendations' => $audit->recommendations ?? [],
        ] : ['message' => 'No SEO audit is available yet.'];

        $targetLocation = $this->targetLocation($website);
        $conversionEvents = Schema::hasTable('conversion_events')
            ? $website->conversionEvents()->where('occurred_at', '>=', now()->subDays(30))->selectRaw('event_type, COUNT(*) as events')->groupBy('event_type')->pluck('events', 'event_type')->all()
            : [];
        $connectedDataSources = array_values(array_filter([
            $latestSync ? 'Google Search Console' : null,
            $conversionEvents !== [] ? 'anonymous conversion events' : null,
            $audit ? 'SEO audit' : null,
        ]));

        return [
            'website' => [
                'name' => $website->name,
                'url' => $website->url,
                'type' => $website->type,
                'language' => $website->language,
                'target_location' => $targetLocation,
                'audience_search_profile' => $website->serviceProfile(),
                'conversion_goal' => $goalProfile,
                'connected_data_sources' => $connectedDataSources,
            ],
            'latest_seo_audit' => $auditData,
            'search_console' => [
                'latest_sync' => $syncContext,
                'top_pages' => $pageRows->take(10)->values()->all(),
                'high_intent_acquisition_queries' => $serviceCandidates->all(),
                'branded_reputation_queries' => $brandedCandidates->all(),
                'low_value_irrelevant_queries' => $lowValueCandidates->all(),
                'devices' => $website->gscDevices()
                    ->when($latestSync, fn ($query) => $query->where('date_start', $latestSync->date_start->toDateString())->where('date_end', $latestSync->date_end->toDateString()))
                    ->orderByDesc('clicks')
                    ->limit(5)
                    ->get(['device', 'clicks', 'impressions', 'ctr', 'position'])
                    ->toArray(),
                'open_growth_opportunities' => $website->growthOpportunities()->where('status', 'open')->orderByDesc('score')->limit(5)->get(['opportunity_type', 'opportunity_category', 'source_type', 'source_value', 'related_page_url', 'score', 'priority', 'intent', 'problem', 'recommendation'])->toArray(),
                'ranked_insight_candidates_choose_one' => $candidates->take(16)->all(),
                'conversion_events_last_30_days' => $conversionEvents,
            ],
            'recent_insights' => ($recentInsights ?? collect())->take(5)->map(fn ($insight) => [
                'title' => $insight->title,
                'insight_key' => $insight->insight_key,
                'priority' => $insight->priority,
                'category' => $insight->category,
                'status' => $insight->status,
            ])->values(),
            'recent_open_tasks' => ($recentTasks ?? collect())->take(5)->map(fn (MarketingTask $task) => [
                'title' => $task->title,
                'priority' => $task->priority,
                'status' => $task->status,
            ])->values(),
        ];
    }

    private function userPrompt(array $payload): string
    {
        return <<<'PROMPT'
Generate one practical, goal-aligned growth insight for this workspace.

Use only the connected evidence included in the payload. Mention the supporting data source, Search Console property, date range, and filters when available. Do not give generic advice unless it is directly tied to that evidence.
Use "Google Business Profile", not "Google My Business".
State what conversion means for this workspace and which action matters most.
Separate brand/reputation searches from high-intent acquisition searches.
Name low-value or irrelevant searches only when the best action is to deprioritize them.
Prioritize the configured primary conversion, supporting conversions, focus areas, and priority pages over vanity visibility.
Use the website target location and target_locations exactly. Do not recommend a city or country that is not in the website settings.
Recommend actions tied to specific pages or query groups.
Choose exactly one item from search_console.ranked_insight_candidates_choose_one and return its insight_key, source_type, and source_value. Do not invent a page or query.
Do not recommend "Optimize Google Business Profile" unless the chosen data mentions branded/reputation/local visibility and frame it as secondary.
Avoid duplicate advice that is already represented by recent_insights or recent_open_tasks.
Do not claim that a campaign, message, or website change has been executed. Recommend a draft or task and state that approval is required.

Your answer must cover:
- What is happening
- Why it matters
- Best conversion-focused action
- Page/query affected
- Expected result
- Priority
- Suggested task

Prefer opportunities that can move the target audience toward the configured primary action or a supporting conversion.

Website data:
PROMPT."\n\n".json_encode($payload, JSON_PRETTY_PRINT);
    }

    private function validatedInsight(array $data, array $context): array
    {
        $priority = in_array($data['priority'] ?? null, ['low', 'medium', 'high'], true) ? $data['priority'] : 'medium';
        $category = in_array($data['category'] ?? null, ['seo', 'content', 'technical', 'conversion', 'local_seo'], true) ? $data['category'] : 'seo';
        $candidates = collect(data_get($context, 'search_console.ranked_insight_candidates_choose_one', []));
        $candidate = $candidates->firstWhere('insight_key', $data['insight_key'] ?? null) ?: $candidates->first();
        $latestSync = data_get($context, 'search_console.latest_sync');
        $dataPeriod = $latestSync ? $latestSync['date_start'].' - '.$latestSync['date_end'] : 'No Search Console sync period available';
        $propertyUrl = $latestSync['property_url'] ?? null;
        $sourceType = $candidate['source_type'] ?? ($data['affected_source_type'] ?? null);
        $sourceValue = $candidate['source_value'] ?? ($data['affected_source_value'] ?? null);
        $sourceLabel = $sourceValue ? Str::of($sourceValue)->limit(90, '')->toString() : 'Search Console data';
        $goalAction = (string) data_get($context, 'website.conversion_goal.primary_action_label', 'Complete primary action');
        $goalJourney = (string) data_get($context, 'website.conversion_goal.journey_label', 'conversion path');
        $title = Str::of($data['title'] ?? 'Improve conversion action for '.$sourceLabel)->squish()->limit(255, '')->toString();

        if ($sourceValue && ! Str::contains(Str::lower($title), Str::lower(Str::of($sourceValue)->afterLast('/')->before('?')->replace(['-', '_'], ' ')->trim()->limit(40, '')->toString()))) {
            $title = Str::of($title.' - '.$sourceLabel)->limit(255, '')->toString();
        }

        $dataUsed = [
            'data_period' => $dataPeriod,
            'property_url' => $propertyUrl,
            'search_type' => $latestSync['search_type'] ?? null,
            'country_filter' => $latestSync['country_filter'] ?? null,
            'device_filter' => $latestSync['device_filter'] ?? null,
            'candidate_group' => $candidate['group'] ?? null,
            'affected_source_type' => $sourceType,
            'affected_source_value' => $sourceValue,
            'related_page' => $candidate['related_page'] ?? $candidate['page_url'] ?? null,
            'clicks' => $candidate['clicks'] ?? null,
            'impressions' => $candidate['impressions'] ?? null,
            'ctr' => $candidate['ctr'] ?? null,
            'position' => $candidate['position'] ?? null,
            'target_location' => data_get($context, 'website.target_location'),
            'primary_conversion_goal' => data_get($context, 'website.conversion_goal.key'),
            'primary_action' => data_get($context, 'website.conversion_goal.primary_action'),
            'supporting_conversions' => data_get($context, 'website.conversion_goal.secondary_conversion_goals', []),
            'supporting_data_sources' => data_get($context, 'website.conversion_goal.data_sources', []),
        ];
        $targetLocation = (string) data_get($context, 'website.target_location', '');
        $recommendation = Str::of($data['recommendation'] ?? 'Improve the affected page or query path with a clearer conversion action tied to the target location.')->squish()->toString();

        if (filled($targetLocation) && $targetLocation !== 'Not set' && ! Str::contains(Str::lower($recommendation), Str::lower($targetLocation))) {
            $recommendation .= ' Keep the framing specific to '.$targetLocation.'.';
        }

        return [
            'insight_key' => $candidate['insight_key'] ?? Str::slug($category.'-'.$sourceType.'-'.$sourceValue),
            'title' => $title,
            'summary' => Str::of($data['summary'] ?? 'Search Console data shows a specific visibility and conversion opportunity for '.$sourceLabel.'.')->squish()->toString(),
            'why_it_matters' => Str::of($data['why_it_matters'] ?? 'This evidence shows where the workspace can create a clearer '.$goalJourney.' toward '.$goalAction.'.')->squish()->toString(),
            'priority' => $priority,
            'category' => $category,
            'recommendation' => $recommendation,
            'expected_result' => Str::of($data['expected_result'] ?? 'More qualified visitors can understand the offer and move toward '.$goalAction.'.')->squish()->toString(),
            'suggested_task' => Str::of($data['suggested_task'] ?? 'Create a focused conversion task for '.$sourceLabel)->squish()->limit(512, '')->toString(),
            'data_period' => $dataPeriod,
            'property_url' => $propertyUrl,
            'affected_source_type' => $sourceType,
            'affected_source_value' => $sourceValue,
            'data_used' => $dataUsed,
        ];
    }

    private function targetLocation(Website $website): string
    {
        $profileLocations = collect($website->serviceProfile()['target_locations'] ?? []);
        $joined = Str::lower($profileLocations->implode(' '));

        if (Str::contains($joined, ['geneve', 'geneva', 'suisse', 'switzerland'])) {
            return 'Geneva / Switzerland';
        }

        if (Str::contains($joined, ['lyon', 'france'])) {
            return 'Lyon / France';
        }

        return $website->target_location ?: $profileLocations->implode(' / ') ?: 'Not set';
    }
}
