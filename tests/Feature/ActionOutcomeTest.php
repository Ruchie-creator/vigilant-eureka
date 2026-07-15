<?php

namespace Tests\Feature;

use App\Models\ActionOutcome;
use App\Models\Agent;
use App\Models\AgentAction;
use App\Models\AgentRun;
use App\Models\ConversionEvent;
use App\Models\GscDailyMetric;
use App\Models\MarketingTask;
use App\Models\User;
use App\Models\Website;
use App\Services\Agents\ActionOutcomeService;
use Database\Seeders\AgentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ActionOutcomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AgentSeeder::class);
        Carbon::setTestNow('2026-07-16 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_completed_agent_task_creates_one_outcome_and_duplicate_completion_is_safe(): void
    {
        [$task] = $this->agentTask();
        $url = route('marketing-tasks.status.update', $task);
        $this->actingAs($this->user())->patch($url, ['status' => 'completed'])->assertRedirect();
        $this->actingAs($this->user())->patch($url, ['status' => 'completed'])->assertRedirect();

        $this->assertSame(1, ActionOutcome::count());
        $this->assertSame('waiting', ActionOutcome::firstOrFail()->status);
    }

    public function test_baseline_metrics_are_captured_with_appointment_keys(): void
    {
        [$task, $action, $website] = $this->agentTask();
        GscDailyMetric::create(['website_id' => $website->id, 'date' => '2026-07-15', 'clicks' => 10, 'impressions' => 100, 'ctr' => .1, 'position' => 8]);
        $outcome = app(ActionOutcomeService::class)->createForCompletedTask(tap($task)->update(['status' => 'completed']));

        $this->assertSame(10, $outcome->baseline_metrics['clicks']);
        $this->assertArrayHasKey('booking_clicks', $outcome->baseline_metrics);
        $this->assertArrayHasKey('appointment_action_rate', $outcome->baseline_metrics);
        $this->assertSame($action->id, $outcome->agent_action_id);
    }

    public function test_incomplete_evaluation_period_remains_waiting(): void
    {
        $outcome = $this->outcome();
        app(ActionOutcomeService::class)->evaluate($outcome);
        $this->assertSame('waiting', $outcome->fresh()->status);
        $this->assertNull($outcome->fresh()->evaluated_at);
    }

    public function test_due_outcome_with_increased_ctr_is_improved(): void
    {
        $outcome = $this->dueOutcome(['clicks' => 10, 'impressions' => 100, 'ctr' => .1, 'average_position' => 10, 'appointment_actions' => 4]);
        GscDailyMetric::create(['website_id' => $outcome->website_id, 'date' => '2026-07-01', 'clicks' => 20, 'impressions' => 100, 'ctr' => .2, 'position' => 9]);
        $result = app(ActionOutcomeService::class)->evaluate($outcome);

        $this->assertSame('improved', $result->status);
        $this->assertEquals(100.0, $result->metric_changes['ctr']['percentage']);
        $this->assertStringContainsString('suggests a positive result', $result->outcome_summary);
    }

    public function test_reduced_relevant_conversion_actions_can_be_declined(): void
    {
        $outcome = $this->dueOutcome(['clicks' => 20, 'impressions' => 100, 'ctr' => .2, 'average_position' => 8, 'appointment_actions' => 10, 'appointment_action_rate' => 50]);
        GscDailyMetric::create(['website_id' => $outcome->website_id, 'date' => '2026-07-01', 'clicks' => 20, 'impressions' => 100, 'ctr' => .2, 'position' => 8]);
        foreach (range(1, 2) as $index) ConversionEvent::create(['website_id' => $outcome->website_id, 'event_uuid' => (string) Str::uuid(), 'event_type' => 'booking_click', 'page_url' => $outcome->website->url, 'occurred_at' => '2026-07-01 10:00:00']);

        $this->assertSame('declined', app(ActionOutcomeService::class)->evaluate($outcome)->status);
    }

    public function test_low_data_produces_inconclusive_status(): void
    {
        $outcome = $this->dueOutcome(['clicks' => 1, 'impressions' => 5, 'ctr' => .2, 'average_position' => 8]);
        $result = app(ActionOutcomeService::class)->evaluate($outcome);
        $this->assertSame('inconclusive', $result->status);
        $this->assertSame('low', $result->confidence);
    }

    public function test_saas_fixture_prepares_signup_trial_and_subscription_keys(): void
    {
        [$task, , $website] = $this->agentTask('saas_signup_and_subscription');
        $outcome = app(ActionOutcomeService::class)->createForCompletedTask(tap($task)->update(['status' => 'completed']));
        foreach (['signups', 'trial_starts', 'trial_activation', 'onboarding_completion', 'subscriptions', 'trial_to_paid_conversion'] as $key) $this->assertArrayHasKey($key, $outcome->baseline_metrics);
        $this->assertFalse($outcome->baseline_metrics['product_data_connected']);
    }

    public function test_guest_users_cannot_access_outcome_routes(): void
    {
        $outcome = $this->outcome();
        $this->get(route('action-outcomes.index'))->assertRedirect(route('login'));
        $this->get(route('action-outcomes.show', $outcome))->assertRedirect(route('login'));
        $this->post(route('action-outcomes.evaluate', $outcome))->assertRedirect(route('login'));
    }

    public function test_outcome_rendering_supports_structured_metric_arrays(): void
    {
        $outcome = $this->outcome();
        $outcome->update(['baseline_metrics' => ['search' => ['clicks' => 12, 'devices' => ['mobile', 'desktop']], 'conversion_events_available' => true], 'metric_changes' => ['ctr' => ['absolute' => .04, 'percentage' => 25]]]);
        $this->actingAs($this->user())->get(route('action-outcomes.show', $outcome))->assertOk()->assertSee('devices')->assertSee('mobile')->assertSee('percentage')->assertSee('25');
    }

    private function outcome(): ActionOutcome
    {
        [$task] = $this->agentTask();
        $task->update(['status' => 'completed']);
        return app(ActionOutcomeService::class)->createForCompletedTask($task);
    }

    private function dueOutcome(array $baseline): ActionOutcome
    {
        $outcome = $this->outcome();
        $outcome->update(['baseline_metrics' => $baseline, 'baseline_start' => '2026-06-16', 'baseline_end' => '2026-06-29', 'evaluation_start' => '2026-07-01', 'evaluation_end' => '2026-07-14', 'status' => 'waiting']);
        return $outcome->fresh(['website', 'agentAction']);
    }

    private function agentTask(string $goal = 'appointment_booking'): array
    {
        $website = Website::create(['name' => 'Outcome '.$goal.Website::count(), 'url' => 'https://outcome-'.Website::count().'.example.com/', 'type' => 'professional_services', 'language' => 'en', 'status' => 'active', 'primary_conversion_goal' => $goal]);
        $agent = Agent::where('slug', 'conversion')->firstOrFail();
        $run = AgentRun::create(['agent_id' => $agent->id, 'website_id' => $website->id, 'run_type' => 'manual', 'trigger_type' => 'manual', 'correlation_id' => (string) Str::uuid(), 'status' => 'completed', 'started_at' => now(), 'completed_at' => now()]);
        $task = MarketingTask::create(['website_id' => $website->id, 'title' => 'Improve primary CTA', 'description' => 'Clarify the conversion path.', 'expected_result' => 'More relevant actions.', 'priority' => 'high', 'source_type' => 'agent_action', 'source_value' => 'primary conversion', 'status' => 'in_progress']);
        $action = AgentAction::create(['agent_run_id' => $run->id, 'website_id' => $website->id, 'action_type' => 'conversion_improvement', 'title' => 'Improve primary CTA', 'description' => 'Clarify the conversion path.', 'priority' => 'high', 'status' => 'approved', 'expected_result' => 'More relevant actions.', 'created_task_id' => $task->id]);
        return [$task, $action, $website];
    }

    private function user(): User
    {
        return User::firstOrCreate(['email' => 'outcomes@example.com'], ['name' => 'Outcome Reviewer', 'password' => Hash::make('password')]);
    }
}
