<?php

namespace Tests\Feature;

use App\Models\AiInsight;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AiInsightRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_nested_insight_evidence_renders_without_a_server_error(): void
    {
        $user = User::create(['name' => 'Owner', 'email' => 'owner@example.com', 'password' => Hash::make('password')]);
        $website = Website::create(['name' => 'Integrative Auriculo Medicine', 'url' => 'https://integrative-auriculo-med.eu/', 'type' => 'auriculotherapy', 'language' => 'en', 'status' => 'active', 'primary_conversion_goal' => 'appointment_booking']);
        AiInsight::create([
            'website_id' => $website->id,
            'title' => 'Improve the appointment path',
            'summary' => 'Search demand reaches a priority page.',
            'why_it_matters' => 'Visitors need a clear next action.',
            'priority' => 'high',
            'category' => 'conversion',
            'recommendation' => 'Clarify the appointment CTA.',
            'expected_result' => 'More qualified appointment actions.',
            'suggested_task' => 'Review the appointment CTA',
            'data_used' => [
                'data_sources' => ['Google Search Console', 'anonymous conversion events'],
                'supporting_conversions' => ['phone_click', 'email_click'],
                'metrics' => ['clicks' => 12, 'devices' => ['mobile', 'desktop']],
                'approval_required' => true,
            ],
            'status' => 'new',
            'source' => 'ai',
            'insight_key' => 'nested-evidence-regression',
        ]);

        $this->actingAs($user)->get(route('websites.show', $website))
            ->assertOk()
            ->assertSee('Google Search Console, anonymous conversion events')
            ->assertSee('clicks: 12, devices: mobile, desktop');

        $this->actingAs($user)->get(route('websites.ai-insights.index', $website))->assertOk();
    }
}
