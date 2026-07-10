<?php

namespace App\Http\Controllers;

use App\Models\GoogleAccount;
use App\Services\GoogleSearchConsoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class GoogleSearchConsoleController extends Controller
{
    public function connect(GoogleSearchConsoleService $service): RedirectResponse
    {
        if (blank(config('services.google.client_id')) || blank(config('services.google.client_secret'))) {
            return back()->with('error', 'Google OAuth credentials are not configured yet.');
        }

        return redirect()->away($service->oauthUrl());
    }

    public function callback(Request $request, GoogleSearchConsoleService $service): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
            'state' => ['required', 'string'],
        ]);

        try {
            $account = $service->handleCallback($request->user(), $request->string('code')->toString(), $request->string('state')->toString());
            $count = $service->syncSites($account);

            return redirect()->route('google.search-console.sites')->with('success', 'Google Search Console connected. '.$count.' properties loaded.');
        } catch (\Throwable $exception) {
            Log::warning('Google Search Console callback failed.', ['user_id' => $request->user()?->id, 'error' => $exception->getMessage()]);

            return redirect()->route('settings')->with('error', 'Google Search Console could not be connected. Please check credentials and try again.');
        }
    }

    public function disconnect(): RedirectResponse
    {
        GoogleAccount::where('user_id', Auth::id())->where('provider', 'google')->delete();

        return back()->with('success', 'Google Search Console disconnected.');
    }

    public function sites(GoogleSearchConsoleService $service): View|RedirectResponse
    {
        $account = GoogleAccount::with('sites')->where('user_id', Auth::id())->where('provider', 'google')->first();

        if (! $account) {
            return redirect()->route('settings')->with('error', 'Connect Google Search Console first.');
        }

        try {
            $service->syncSites($account);
            $account->refresh()->load('sites');
        } catch (\Throwable $exception) {
            Log::warning('Google Search Console sites refresh failed.', ['account_id' => $account->id, 'error' => $exception->getMessage()]);
        }

        return view('google.search-console-sites', compact('account'));
    }
}
