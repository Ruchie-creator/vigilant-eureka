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
        ]);
    }

    public function store(Website $website, AiMarketingInsightService $aiService, RuleBasedInsightGenerator $fallbackGenerator): RedirectResponse
    {
        $audit = $website->seoAudits()->latest()->first();
        $recentInsights = $website->aiInsights()->latest()->limit(5)->get();
        $recentTasks = $website->marketingTasks()->whereIn('status', ['pending', 'in_progress'])->latest()->limit(5)->get();

        try {
            $insight = $aiService->generate($website, $audit, $recentInsights, $recentTasks);

            AiInsight::create([
                'website_id' => $website->id,
                'audit_id' => $audit?->id,
                'title' => $insight['title'],
                'summary' => $insight['summary'],
                'priority' => $insight['priority'],
                'category' => $insight['category'],
                'recommendation' => $insight['recommendation'],
                'expected_result' => $insight['expected_result'],
                'source' => 'ai',
            ]);

            return back()->with('success', 'AI insight generated.');
        } catch (\Throwable) {
            $fallbackGenerator->generate($website, $audit);

            return back()->with('success', 'AI insight generator is unavailable, so a rule-based fallback insight was created.');
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
