<?php

namespace App\Http\Controllers;

use App\Models\GoogleAccount;
use App\Models\SearchConsoleSite;
use App\Models\Website;
use App\Services\GoogleSearchConsoleService;
use App\Services\ConversionCheckService;
use App\Services\GrowthOpportunityGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WebsiteSearchConsoleController extends Controller
{
    public function assign(Request $request, Website $website): RedirectResponse
    {
        $data = $request->validate([
            'search_console_site_id' => ['required', 'exists:search_console_sites,id'],
        ]);

        $site = SearchConsoleSite::whereHas('googleAccount', fn ($query) => $query->where('user_id', Auth::id()))
            ->findOrFail($data['search_console_site_id']);

        $website->update(['search_console_site_id' => $site->id]);

        return back()->with('success', 'Search Console property selected.');
    }

    public function sync(Request $request, Website $website, GoogleSearchConsoleService $service, GrowthOpportunityGenerator $opportunities, ConversionCheckService $checks): RedirectResponse
    {
        $data = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $start = isset($data['start_date']) ? Carbon::parse($data['start_date']) : now()->subDays(28);
        $end = isset($data['end_date']) ? Carbon::parse($data['end_date']) : now()->subDay();

        try {
            $summary = $service->syncWebsite($website, $start, $end);
            $created = $opportunities->generate($website, $summary['start'], $summary['end']);
            $checks->ensureDefaults($website);

            return back()->with('success', 'Search data synced. '.$created.' growth opportunities created.');
        } catch (\Throwable $exception) {
            Log::warning('Website Search Console sync failed.', ['website_id' => $website->id, 'error' => $exception->getMessage()]);

            return back()->with('error', 'Search Console sync could not be completed. Check the selected property and Google connection.');
        }
    }

    public function disconnect(Website $website): RedirectResponse
    {
        $website->update(['search_console_site_id' => null]);

        return back()->with('success', 'Search Console property removed from this website.');
    }

    public static function connectedAccount()
    {
        return GoogleAccount::with('sites')->where('user_id', Auth::id())->where('provider', 'google')->first();
    }
}
