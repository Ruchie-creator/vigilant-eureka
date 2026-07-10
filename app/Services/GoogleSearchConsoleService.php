<?php

namespace App\Services;

use App\Models\GoogleAccount;
use App\Models\GscDailyMetric;
use App\Models\GscDevice;
use App\Models\GscPage;
use App\Models\GscQuery;
use App\Models\SearchConsoleSite;
use App\Models\User;
use App\Models\Website;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class GoogleSearchConsoleService
{
    public function oauthUrl(): string
    {
        $state = Str::random(40);
        session(['google_search_console_state' => $state]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect_uri'),
            'response_type' => 'code',
            'scope' => config('services.google.search_console_scope'),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);
    }

    public function handleCallback(User $user, string $code, string $state): GoogleAccount
    {
        if (! hash_equals((string) session('google_search_console_state'), $state)) {
            throw new RuntimeException('Google connection state did not match.');
        }

        $token = Http::asForm()->timeout(20)->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => config('services.google.redirect_uri'),
            'grant_type' => 'authorization_code',
            'code' => $code,
        ]);

        if (! $token->successful()) {
            Log::warning('Google OAuth token exchange failed.', ['status' => $token->status()]);
            throw new RuntimeException('Google Search Console could not be connected.');
        }

        $payload = $token->json();
        $email = $this->fetchGoogleEmail($payload['access_token'] ?? null);

        return GoogleAccount::updateOrCreate(
            ['user_id' => $user->id, 'provider' => 'google'],
            [
                'email' => $email,
                'access_token' => $payload['access_token'],
                'refresh_token' => $payload['refresh_token'] ?? GoogleAccount::where('user_id', $user->id)->where('provider', 'google')->value('refresh_token'),
                'expires_at' => now()->addSeconds((int) ($payload['expires_in'] ?? 3600) - 60),
                'scopes' => explode(' ', (string) ($payload['scope'] ?? config('services.google.search_console_scope'))),
            ]
        );
    }

    public function refreshIfNeeded(GoogleAccount $account): GoogleAccount
    {
        if ($account->expires_at && $account->expires_at->isFuture()) {
            return $account;
        }

        if (blank($account->refresh_token)) {
            throw new RuntimeException('Google refresh token is missing. Reconnect Search Console.');
        }

        $token = Http::asForm()->timeout(20)->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'grant_type' => 'refresh_token',
            'refresh_token' => $account->refresh_token,
        ]);

        if (! $token->successful()) {
            Log::warning('Google OAuth token refresh failed.', ['account_id' => $account->id, 'status' => $token->status()]);
            throw new RuntimeException('Google Search Console connection expired. Reconnect Google.');
        }

        $payload = $token->json();
        $account->update([
            'access_token' => $payload['access_token'],
            'expires_at' => now()->addSeconds((int) ($payload['expires_in'] ?? 3600) - 60),
            'scopes' => isset($payload['scope']) ? explode(' ', $payload['scope']) : $account->scopes,
        ]);

        return $account->refresh();
    }

    public function syncSites(GoogleAccount $account): int
    {
        $account = $this->refreshIfNeeded($account);

        $response = Http::withToken($account->access_token)
            ->timeout(20)
            ->get('https://www.googleapis.com/webmasters/v3/sites');

        if (! $response->successful()) {
            Log::warning('Google Search Console sites list failed.', ['account_id' => $account->id, 'status' => $response->status()]);
            throw new RuntimeException('Could not load Search Console sites.');
        }

        $count = 0;
        foreach ($response->json('siteEntry', []) as $site) {
            SearchConsoleSite::updateOrCreate(
                ['google_account_id' => $account->id, 'site_url' => $site['siteUrl']],
                ['permission_level' => $site['permissionLevel'] ?? null]
            );
            $count++;
        }

        return $count;
    }

    public function syncWebsite(Website $website, ?CarbonInterface $start = null, ?CarbonInterface $end = null): array
    {
        $site = $website->searchConsoleSite?->load('googleAccount');
        if (! $site) {
            throw new RuntimeException('Select a Google Search Console property for this website first.');
        }

        $account = $this->refreshIfNeeded($site->googleAccount);
        $start ??= now()->subDays(28);
        $end ??= now()->subDay();

        $daily = $this->queryAnalytics($account, $site->site_url, $start, $end, ['date'], 200);
        $queries = $this->queryAnalytics($account, $site->site_url, $start, $end, ['query'], 100);
        $pages = $this->queryAnalytics($account, $site->site_url, $start, $end, ['page'], 100);
        $devices = $this->queryAnalytics($account, $site->site_url, $start, $end, ['device'], 20);

        foreach ($daily as $row) {
            GscDailyMetric::updateOrCreate(
                ['website_id' => $website->id, 'date' => $row['keys'][0]],
                $this->metricsPayload($website, $site, $row)
            );
        }

        foreach ($queries as $row) {
            $queryText = Str::limit($row['keys'][0], 512, '');
            GscQuery::updateOrCreate(
                ['website_id' => $website->id, 'query' => $queryText, 'date_start' => $start->toDateString(), 'date_end' => $end->toDateString()],
                $this->metricsPayload($website, $site, $row)
            );
        }

        foreach ($pages as $row) {
            $pageUrl = Str::limit($row['keys'][0], 512, '');
            GscPage::updateOrCreate(
                ['website_id' => $website->id, 'page_url' => $pageUrl, 'date_start' => $start->toDateString(), 'date_end' => $end->toDateString()],
                $this->metricsPayload($website, $site, $row)
            );
        }

        foreach ($devices as $row) {
            GscDevice::updateOrCreate(
                ['website_id' => $website->id, 'device' => Str::lower($row['keys'][0]), 'date_start' => $start->toDateString(), 'date_end' => $end->toDateString()],
                $this->metricsPayload($website, $site, $row)
            );
        }

        $website->update(['gsc_last_synced_at' => now()]);

        return [
            'daily' => count($daily),
            'queries' => count($queries),
            'pages' => count($pages),
            'devices' => count($devices),
            'start' => $start,
            'end' => $end,
        ];
    }

    public function queryAnalytics(GoogleAccount $account, string $siteUrl, CarbonInterface $start, CarbonInterface $end, array $dimensions, int $limit = 100): array
    {
        $response = Http::withToken($account->access_token)
            ->timeout(30)
            ->post('https://www.googleapis.com/webmasters/v3/sites/'.rawurlencode($siteUrl).'/searchAnalytics/query', [
                'startDate' => $start->toDateString(),
                'endDate' => $end->toDateString(),
                'dimensions' => $dimensions,
                'rowLimit' => $limit,
            ]);

        if (! $response->successful()) {
            Log::warning('Google Search Console analytics query failed.', ['site_url' => $siteUrl, 'dimensions' => $dimensions, 'status' => $response->status()]);
            throw new RuntimeException('Could not sync Search Console performance data.');
        }

        return $response->json('rows', []);
    }

    private function fetchGoogleEmail(?string $accessToken): ?string
    {
        if (blank($accessToken)) {
            return null;
        }

        try {
            $response = Http::withToken($accessToken)->timeout(10)->get('https://www.googleapis.com/oauth2/v2/userinfo');
            return $response->successful() ? $response->json('email') : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function metricsPayload(Website $website, SearchConsoleSite $site, array $row): array
    {
        return [
            'website_id' => $website->id,
            'search_console_site_id' => $site->id,
            'clicks' => (int) ($row['clicks'] ?? 0),
            'impressions' => (int) ($row['impressions'] ?? 0),
            'ctr' => round(((float) ($row['ctr'] ?? 0)) * 100, 4),
            'position' => round((float) ($row['position'] ?? 0), 2),
        ];
    }
}
