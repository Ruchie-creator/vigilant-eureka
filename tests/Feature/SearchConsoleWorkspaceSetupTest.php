<?php

namespace Tests\Feature;

use App\Models\GoogleAccount;
use App\Models\SearchConsoleSite;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SearchConsoleWorkspaceSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_workspace_redirects_to_its_search_console_setup_page(): void
    {
        $response = $this->actingAs($this->user())->post(route('websites.store'), [
            'name' => 'Integrative Auriculo Medicine',
            'url' => 'https://integrative-auriculo-med.eu/',
            'type' => 'auriculotherapy',
            'language' => 'en',
            'status' => 'active',
            'primary_conversion_goal' => 'appointment_booking',
        ]);

        $website = Website::where('url', 'https://integrative-auriculo-med.eu/')->firstOrFail();
        $response->assertRedirect(route('websites.show', $website));
    }

    public function test_workspace_page_lists_matching_property_and_can_assign_it(): void
    {
        $user = $this->user();
        $website = $this->website();
        $account = GoogleAccount::create(['user_id' => $user->id, 'provider' => 'google', 'access_token' => 'encrypted-by-model-cast', 'refresh_token' => 'encrypted-by-model-cast']);
        $matching = SearchConsoleSite::create(['google_account_id' => $account->id, 'site_url' => 'https://integrative-auriculo-med.eu/', 'permission_level' => 'siteOwner']);
        SearchConsoleSite::create(['google_account_id' => $account->id, 'site_url' => 'https://unrelated.example/', 'permission_level' => 'siteOwner']);

        $this->actingAs($user)->get(route('websites.show', $website))
            ->assertOk()
            ->assertSee('Select a property for this workspace')
            ->assertSee($matching->site_url)
            ->assertDontSee('https://unrelated.example/');

        $this->actingAs($user)->post(route('websites.search-console.assign', $website), ['search_console_site_id' => $matching->id])->assertRedirect();
        $this->assertSame($matching->id, $website->fresh()->search_console_site_id);
    }

    public function test_property_from_another_google_account_cannot_be_assigned(): void
    {
        $user = $this->user();
        $website = $this->website();
        $other = User::create(['name' => 'Other User', 'email' => 'other@example.com', 'password' => Hash::make('password')]);
        $account = GoogleAccount::create(['user_id' => $other->id, 'provider' => 'google', 'access_token' => 'token']);
        $site = SearchConsoleSite::create(['google_account_id' => $account->id, 'site_url' => $website->url, 'permission_level' => 'siteOwner']);

        $this->actingAs($user)->post(route('websites.search-console.assign', $website), ['search_console_site_id' => $site->id])->assertNotFound();
        $this->assertNull($website->fresh()->search_console_site_id);
    }

    private function user(): User
    {
        return User::firstOrCreate(['email' => 'owner@example.com'], ['name' => 'Owner', 'password' => Hash::make('password')]);
    }

    private function website(): Website
    {
        return Website::create([
            'name' => 'Integrative Auriculo Medicine',
            'url' => 'https://integrative-auriculo-med.eu/',
            'type' => 'auriculotherapy',
            'language' => 'en',
            'status' => 'active',
            'primary_conversion_goal' => 'appointment_booking',
        ]);
    }
}
