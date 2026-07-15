<?php

namespace App\Services;

use App\Models\AiInsight;
use App\Models\SeoAudit;
use App\Models\Website;

class RuleBasedInsightGenerator
{
    public function __construct(private readonly ConversionGoalProfileService $goalProfiles)
    {
    }

    public function generate(Website $website, ?SeoAudit $audit): ?AiInsight
    {
        $goal = $this->goalProfiles->forWebsite($website);
        if (! $audit) {
            return AiInsight::firstOrCreate(
                ['website_id' => $website->id, 'title' => 'Run the first SEO scan', 'category' => 'technical', 'status' => 'new'],
                [
                    'summary' => 'No audit is available yet, so the next best action is to capture a baseline.',
                    'priority' => 'medium',
                    'recommendation' => 'Run a homepage SEO audit and review the initial indexability findings.',
                    'expected_result' => 'A reliable baseline for future growth and conversion decisions.',
                    'source' => 'rule_based',
                ]
            );
        }

        $missing = $audit->missing_fields ?? [];
        if ($audit->is_indexable && count($missing) === 0) {
            return null;
        }

        $priority = count($missing) >= 4 || ! $audit->is_indexable ? 'high' : (count($missing) >= 2 ? 'medium' : 'low');
        $category = ! $audit->is_indexable ? 'technical' : 'seo';

        $title = $priority === 'high' ? 'Fix homepage SEO blockers' : 'Maintain homepage SEO completeness';

        return AiInsight::firstOrCreate(
            ['website_id' => $website->id, 'insight_key' => 'fallback-audit-'.$audit->id],
            [
                'audit_id' => $audit->id,
                'title' => $title,
                'category' => $category,
                'status' => 'new',
                'summary' => count($missing)
                    ? 'The latest scan found missing or weak SEO signals: '.implode(', ', $missing).'.'
                    : 'The latest scan has the primary homepage SEO signals in place.',
                'why_it_matters' => 'This fallback is based on the latest SEO scan, not Search Console opportunity data.',
                'priority' => $priority,
                'recommendation' => $audit->recommendations[0] ?? 'Review the homepage against the configured offer, target audience, and primary conversion action.',
                'expected_result' => 'Better crawl clarity, stronger search snippets, and a more useful conversion backlog.',
                'suggested_task' => $priority === 'high' ? 'Fix the homepage SEO blocker found in the audit' : 'Review minor homepage SEO maintenance items supporting '.$goal['primary_action_label'],
                'source' => 'rule_based',
            ]
        );
    }
}
