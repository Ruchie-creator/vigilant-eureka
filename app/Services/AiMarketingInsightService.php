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
    public function generate(Website $website, ?SeoAudit $audit, ?Collection $recentInsights = null, ?Collection $recentTasks = null): array
    {
        $apiKey = config('services.openai.key');

        if (blank($apiKey)) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        try {
            $response = Http::timeout((int) config('services.openai.timeout', 20))
                ->withToken($apiKey)
                ->acceptJson()
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.model', 'gpt-4o-mini'),
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $this->systemPrompt()],
                        ['role' => 'user', 'content' => $this->userPrompt($website, $audit, $recentInsights, $recentTasks)],
                    ],
                    'temperature' => 0.3,
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('OpenAI request failed with status '.$response->status().'.');
            }

            $content = data_get($response->json(), 'choices.0.message.content');
            $decoded = json_decode((string) $content, true, flags: JSON_THROW_ON_ERROR);

            return $this->validatedInsight($decoded);
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
You are an SEO and marketing analyst for public healthcare-related websites.

Safety rules:
- This is for public website SEO and marketing only.
- Do not request, infer, store, or process patient medical data.
- Do not make medical claims.
- Do not suggest guaranteed treatment outcomes.
- Use cautious wording such as "may help", "can support", and "patients looking for...".
- Focus on visibility, content clarity, local SEO, technical SEO, and conversion improvements.

Return only valid JSON with these keys:
title, summary, priority, category, recommendation, expected_result.

Allowed priority values: low, medium, high.
Allowed category values: seo, content, technical, conversion, local_seo.
PROMPT;
    }

    private function userPrompt(Website $website, ?SeoAudit $audit, ?Collection $recentInsights, ?Collection $recentTasks): string
    {
        $classifier = app(GrowthOpportunityGenerator::class);
        $latestSync = Schema::hasTable('gsc_syncs') ? $website->latestGscSync : null;
        $queries = $website->gscQueries()->latest()->limit(20)->get(['query', 'clicks', 'impressions', 'ctr', 'position']);
        $queriesByIntent = $queries->map(function ($query) use ($website, $classifier) {
            $intent = $classifier->classifyQueryIntent($query->query, $website);

            return [
                'query' => $query->query,
                'intent' => $intent,
                'category' => $classifier->opportunityCategoryForIntent($intent),
                'clicks' => $query->clicks,
                'impressions' => $query->impressions,
                'ctr' => $query->ctr,
                'position' => $query->position,
            ];
        });

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

        $payload = [
            'website' => [
                'name' => $website->name,
                'url' => $website->url,
                'type' => $website->type,
                'language' => $website->language,
                'target_location' => $website->target_location,
                'service_profile' => $website->serviceProfile(),
            ],
            'latest_seo_audit' => $auditData,
            'search_console' => [
                'latest_sync' => $latestSync ? [
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
                ] : null,
                'top_pages' => $website->gscPages()->latest()->limit(8)->get(['page_url', 'clicks', 'impressions', 'ctr', 'position'])->map(fn ($page) => [
                    'page_url' => $page->page_url,
                    'page_type' => $classifier->pageType($page->page_url, $website),
                    'is_priority_service_page' => $classifier->isPriorityServicePage($website, $page->page_url),
                    'clicks' => $page->clicks,
                    'impressions' => $page->impressions,
                    'ctr' => $page->ctr,
                    'position' => $page->position,
                ])->toArray(),
                'service_patient_intent_queries' => $queriesByIntent->whereIn('intent', ['service_intent', 'local_service_intent', 'condition_intent'])->values()->all(),
                'branded_reputation_queries' => $queriesByIntent->whereIn('intent', ['branded_practitioner', 'review_reputation'])->values()->all(),
                'other_queries' => $queriesByIntent->whereNotIn('intent', ['service_intent', 'local_service_intent', 'condition_intent', 'branded_practitioner', 'review_reputation'])->values()->all(),
                'devices' => $website->gscDevices()->latest()->limit(5)->get(['device', 'clicks', 'impressions', 'ctr', 'position'])->toArray(),
                'open_growth_opportunities' => $website->growthOpportunities()->where('status', 'open')->orderByDesc('score')->limit(5)->get(['opportunity_type', 'opportunity_category', 'source_type', 'source_value', 'related_page_url', 'score', 'priority', 'intent', 'problem', 'recommendation'])->toArray(),
            ],
            'recent_insights' => ($recentInsights ?? collect())->take(5)->map(fn ($insight) => [
                'title' => $insight->title,
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

        return <<<'PROMPT'
Generate one practical conversion-focused marketing insight for this website.

Use actual Search Console data. Mention the Search Console property, date range, and filters when available. Do not give generic advice unless it is directly tied to the website data.
Use "Google Business Profile", not "Google My Business".
Separate branded/practitioner-name searches from service or patient-intent searches.
Prioritize service pages, priority pages, and appointment conversions over branded visibility unless the insight is explicitly about trust or reputation conversion.
Use the website target location and target_locations exactly. Do not recommend a city or country that is not in the website settings.
Recommend actions tied to specific pages or query groups.

Your answer must cover:
- What is happening
- Why it matters
- Best conversion-focused action
- Page/query affected
- Expected result
- Priority
- Suggested task

Prefer opportunities that can increase visits or appointment actions.

Website data:
PROMPT."\n\n".json_encode($payload, JSON_PRETTY_PRINT);
    }

    private function validatedInsight(array $data): array
    {
        $priority = in_array($data['priority'] ?? null, ['low', 'medium', 'high'], true) ? $data['priority'] : 'medium';
        $category = in_array($data['category'] ?? null, ['seo', 'content', 'technical', 'conversion', 'local_seo'], true) ? $data['category'] : 'seo';

        return [
            'title' => Str::of($data['title'] ?? 'Review website marketing opportunity')->squish()->limit(255, '')->toString(),
            'summary' => Str::of($data['summary'] ?? 'The website has an opportunity to improve SEO and content clarity.')->squish()->toString(),
            'priority' => $priority,
            'category' => $category,
            'recommendation' => Str::of($data['recommendation'] ?? 'Review the latest SEO audit and improve the clearest missing signal.')->squish()->toString(),
            'expected_result' => Str::of($data['expected_result'] ?? 'Clearer search visibility and a more focused marketing backlog.')->squish()->toString(),
        ];
    }
}
