<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\GoogleAccount;
use App\Services\SafeUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WebsiteController extends Controller
{
    public function index(): View
    {
        return view('websites.index', [
            'websites' => Website::withCount(['seoAudits', 'aiInsights', 'marketingTasks'])->latest()->paginate(12),
        ]);
    }

    public function create(): View
    {
        return view('websites.form', ['website' => new Website()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        SafeUrl::assertPublicHttpUrl($data['url']);
        Website::create($data);

        return redirect()->route('websites.index')->with('success', 'Website added.');
    }

    public function show(Website $website): View
    {
        return view('websites.show', [
            'website' => $website->load([
                'searchConsoleSite',
                'seoAudits' => fn ($query) => $query->latest()->limit(8),
                'aiInsights' => fn ($query) => $query->latest()->limit(8),
                'marketingTasks' => fn ($query) => $query->latest()->limit(8),
                'gscQueries' => fn ($query) => $query->latest()->limit(10),
                'gscPages' => fn ($query) => $query->latest()->limit(10),
                'gscDevices' => fn ($query) => $query->latest()->limit(10),
                'growthOpportunities' => fn ($query) => $query->where('status', 'open')->latest()->limit(10),
            ]),
            'googleAccount' => GoogleAccount::with('sites')->where('user_id', Auth::id())->where('provider', 'google')->first(),
            'gscSummary' => $website->gscDailyMetrics()
                ->where('date', '>=', now()->subDays(28)->toDateString())
                ->selectRaw('COALESCE(SUM(clicks), 0) as clicks, COALESCE(SUM(impressions), 0) as impressions, COALESCE(AVG(ctr), 0) as ctr, COALESCE(AVG(position), 0) as position')
                ->first(),
        ]);
    }

    public function edit(Website $website): View
    {
        return view('websites.form', compact('website'));
    }

    public function update(Request $request, Website $website): RedirectResponse
    {
        $data = $this->validated($request);
        SafeUrl::assertPublicHttpUrl($data['url']);
        $website->update($data);

        return redirect()->route('websites.show', $website)->with('success', 'Website updated.');
    }

    public function destroy(Website $website): RedirectResponse
    {
        $website->delete();

        return redirect()->route('websites.index')->with('success', 'Website deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url:http,https', 'max:2048'],
            'type' => ['required', Rule::in(['osteopathy', 'auriculotherapy', 'sexology', 'other'])],
            'language' => ['required', 'string', 'max:40'],
            'target_location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'paused', 'archived'])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
