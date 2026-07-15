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
        $insight = AiInsight::create([
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
            ->assertSee('Google Search Console')
            ->assertSee('anonymous conversion events')
            ->assertSee('supporting conversions')
            ->assertSee('phone_click')
            ->assertSee('devices')
            ->assertSee('mobile');

        $this->actingAs($user)->get(route('websites.ai-insights.index', $website))->assertOk();

        // AI providers can return structured JSON for fields that are usually prose.
        // Render the unsaved shape directly to ensure Blade never passes an array to e().
        $insight->title = ['Appointment path', ['priority' => 'Mobile first']];
        $insight->summary = [
            'finding' => 'Search demand reaches a priority page.',
            'signals' => ['high impressions', ['device' => 'mobile', 'clicks' => 12]],
        ];
        $insight->why_it_matters = null;
        $insight->recommendation = ['Clarify the appointment CTA.', 'Keep the phone action visible.'];
        $insight->expected_result = 24;
        $insight->suggested_task = ['title' => 'Review the CTA', 'approval_required' => true];
        $insight->affected_source_type = 'page';
        $insight->affected_source_value = ['/appointments', ['country' => 'France']];

        $this->actingAs($user)
            ->view('ai-insights.partials.card', ['insight' => $insight])
            ->assertSee('Appointment path')
            ->assertSee('Mobile first')
            ->assertSee('high impressions')
            ->assertSee('clicks')
            ->assertSee('12')
            ->assertSee('This is tied to real search visibility and conversion potential.')
            ->assertSee('Keep the phone action visible.')
            ->assertSee('24')
            ->assertSee('approval required')
            ->assertSee('Yes')
            ->assertSee('/appointments');

        $insight->setRawAttributes(array_replace($insight->getAttributes(), [
            'data_used' => json_encode('Search Console only', JSON_THROW_ON_ERROR),
        ]));

        $this->actingAs($user)
            ->view('ai-insights.partials.card', ['insight' => $insight])
            ->assertSee('Search Console only');
    }
}
