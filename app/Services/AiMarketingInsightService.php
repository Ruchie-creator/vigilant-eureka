<?php

namespace App\Services;

use App\Models\MarketingTask;
use App\Models\SeoAudit;
use App\Models\Website;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
            ],
            'latest_seo_audit' => $auditData,
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

        return "Generate one practical marketing insight for this website. Prefer the highest-value next action and avoid duplicating recent work.\n\n".json_encode($payload, JSON_PRETTY_PRINT);
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
