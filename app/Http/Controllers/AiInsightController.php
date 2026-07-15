<?php

namespace App\Http\Controllers;

use App\Models\AiInsight;
use App\Models\Website;
use App\Services\AiMarketingInsightService;
use App\Services\RuleBasedInsightGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AiInsightController extends Controller
{
    public function index(): View
    {
        return view('ai-insights.index', [
            'insights' => AiInsight::with(['website', 'audit'])->latest()->paginate(15),
            'heading' => 'AI Insights',
        ]);
    }

    public function websiteIndex(Website $website): View
    {
        return view('ai-insights.index', [
            'insights' => $website->aiInsights()->with(['website', 'audit'])->latest()->paginate(15),
            'heading' => $website->name.' - AI Insights',
            'website' => $website,
        ]);
    }

    public function store(Website $website, AiMarketingInsightService $aiService, RuleBasedInsightGenerator $fallbackGenerator): RedirectResponse
    {
        $audit = $website->seoAudits()->latest()->first();
        $recentInsights = $website->aiInsights()->latest()->limit(5)->get();
        $recentTasks = $website->marketingTasks()->whereIn('status', ['pending', 'in_progress'])->latest()->limit(5)->get();

        try {
            $insight = $aiService->generate($website, $audit, $recentInsights, $recentTasks);

            $existing = AiInsight::firstOrNew([
                'website_id' => $website->id,
                'insight_key' => $insight['insight_key'],
            ]);

            $existing->fill([
                'audit_id' => $audit?->id,
                'title' => $insight['title'],
                'summary' => $insight['summary'],
                'why_it_matters' => $insight['why_it_matters'],
                'priority' => $insight['priority'],
                'category' => $insight['category'],
                'recommendation' => $insight['recommendation'],
                'expected_result' => $insight['expected_result'],
                'suggested_task' => $insight['suggested_task'],
                'data_period' => $insight['data_period'],
                'property_url' => $insight['property_url'],
                'affected_source_type' => $insight['affected_source_type'],
                'affected_source_value' => $insight['affected_source_value'],
                'data_used' => $insight['data_used'],
                'status' => $existing->exists ? $existing->status : 'new',
                'source' => 'ai',
            ]);
            $existing->save();

            return back()->with('success', 'AI insight generated.');
        } catch (\Throwable) {
            $fallback = $fallbackGenerator->generate($website, $audit);

            return back()->with('success', $fallback
                ? 'AI insight generator is unavailable, so a rule-based fallback insight was created.'
                : 'AI insight generator is unavailable, and the latest SEO audit is healthy, so no fallback blocker was created.');
        }
    }

    public function update(Request $request, AiInsight $aiInsight): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'reviewed', 'implemented', 'ignored'])],
        ]);

        $aiInsight->update($data);

        return back()->with('success', 'Insight updated.');
    }
}
