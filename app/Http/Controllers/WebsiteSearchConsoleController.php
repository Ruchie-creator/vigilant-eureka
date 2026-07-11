<?php

namespace App\Http\Controllers;

use App\Models\GoogleAccount;
use App\Models\GrowthOpportunity;
use App\Models\GscCountry;
use App\Models\GscDailyMetric;
use App\Models\GscDevice;
use App\Models\GscPage;
use App\Models\GscQuery;
use App\Models\GscSync;
use App\Models\SearchConsoleSite;
use App\Models\Website;
use App\Services\GoogleSearchConsoleService;
use App\Services\ConversionCheckService;
use App\Services\GrowthOpportunityGenerator;
use App\Services\SearchConsolePropertyMatcher;
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

        if (! SearchConsolePropertyMatcher::matches($website->url, $site->site_url)) {
            return back()->with('error', 'This Search Console property does not match the website URL.');
        }

        $website->update(['search_console_site_id' => $site->id]);

        return back()->with('success', 'Search Console property selected.');
    }

    public function sync(Request $request, Website $website, GoogleSearchConsoleService $service, GrowthOpportunityGenerator $opportunities, ConversionCheckService $checks): RedirectResponse
    {
        $data = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'country_filter' => ['nullable', 'string', 'max:12'],
            'device_filter' => ['nullable', 'string', 'max:40'],
        ]);

        $start = isset($data['start_date']) ? Carbon::parse($data['start_date']) : now()->subDays(28);
        $end = isset($data['end_date']) ? Carbon::parse($data['end_date']) : now()->subDay();
        $countryFilter = blank($data['country_filter'] ?? null) ? null : $data['country_filter'];
        $deviceFilter = blank($data['device_filter'] ?? null) ? null : $data['device_filter'];

        try {
            $summary = $service->syncWebsite($website, $start, $end, 'web', $countryFilter, $deviceFilter);
            $created = $opportunities->generate($website, $summary['start'], $summary['end']);
            $checks->ensureDefaults($website);

            return back()->with('success', 'Search data synced for '.$summary['property_url'].' from '.$summary['start']->toDateString().' to '.$summary['end']->toDateString().'. '.$created.' growth opportunities created.');
        } catch (\Throwable $exception) {
            Log::warning('Website Search Console sync failed.', ['website_id' => $website->id, 'error' => $exception->getMessage()]);

            return back()->with('error', $exception->getMessage() === 'This Search Console property does not match the website URL.'
                ? 'This Search Console property does not match the website URL.'
                : 'Search Console sync could not be completed. Check the selected property and Google connection.');
        }
    }

    public function disconnect(Website $website): RedirectResponse
    {
        $website->update(['search_console_site_id' => null]);

        return back()->with('success', 'Search Console property removed from this website.');
    }

    public function reset(Website $website): RedirectResponse
    {
        GscDailyMetric::where('website_id', $website->id)->delete();
        GscQuery::where('website_id', $website->id)->delete();
        GscPage::where('website_id', $website->id)->delete();
        GscDevice::where('website_id', $website->id)->delete();
        GscCountry::where('website_id', $website->id)->delete();
        GscSync::where('website_id', $website->id)->delete();
        GrowthOpportunity::where('website_id', $website->id)->delete();

        $website->update(['gsc_last_synced_at' => null]);

        return back()->with('success', 'Search Console metrics and growth opportunities were reset for this website. Existing tasks were kept.');
    }

    public static function connectedAccount()
    {
        return GoogleAccount::with('sites')->where('user_id', Auth::id())->where('provider', 'google')->first();
    }
}
