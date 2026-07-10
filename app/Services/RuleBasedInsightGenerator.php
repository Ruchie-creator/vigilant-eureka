<?php

namespace App\Services;

use App\Models\AiInsight;
use App\Models\SeoAudit;
use App\Models\Website;

class RuleBasedInsightGenerator
{
    public function generate(Website $website, ?SeoAudit $audit): AiInsight
    {
        if (! $audit) {
            return AiInsight::create([
                'website_id' => $website->id,
                'title' => 'Run the first SEO scan',
                'summary' => 'No audit is available yet, so the next best action is to capture a baseline.',
                'priority' => 'medium',
                'category' => 'technical',
                'recommendation' => 'Run a homepage SEO audit and review the initial indexability findings.',
                'expected_result' => 'A reliable baseline for future content and technical decisions.',
            ]);
        }

        $missing = $audit->missing_fields ?? [];
        $priority = count($missing) >= 4 || ! $audit->is_indexable ? 'high' : (count($missing) >= 2 ? 'medium' : 'low');
        $category = ! $audit->is_indexable ? 'technical' : 'seo';

        return AiInsight::create([
            'website_id' => $website->id,
            'audit_id' => $audit->id,
            'title' => $priority === 'high' ? 'Fix homepage SEO blockers' : 'Improve homepage SEO completeness',
            'summary' => count($missing)
                ? 'The latest scan found missing or weak SEO signals: '.implode(', ', $missing).'.'
                : 'The latest scan has the primary homepage SEO signals in place.',
            'priority' => $priority,
            'category' => $category,
            'recommendation' => $audit->recommendations[0] ?? 'Review the homepage against priority healthcare service keywords.',
            'expected_result' => 'Better crawl clarity, stronger search snippets, and a more useful weekly optimization backlog.',
        ]);
    }
}
