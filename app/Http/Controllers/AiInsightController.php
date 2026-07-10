<?php

namespace App\Http\Controllers;

use App\Models\AiInsight;
use App\Models\Website;
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

    public function store(Website $website, RuleBasedInsightGenerator $generator): RedirectResponse
    {
        $generator->generate($website, $website->seoAudits()->latest()->first());

        return back()->with('success', 'Rule-based AI insight generated.');
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
