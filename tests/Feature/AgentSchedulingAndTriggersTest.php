<?php

namespace Tests\Feature;

use App\Events\SearchConsoleSyncCompleted;
use App\Models\Agent;
use App\Models\AgentRun;
use App\Models\AgentSchedule;
use App\Models\ConversionEvent;
use App\Models\GscPage;
use App\Models\GscQuery;
use App\Models\GscSync;
use App\Models\MarketingTask;
use App\Models\User;
use App\Models\Website;
use App\Models\WeeklyMarketingPlan;
use App\Services\Agents\AgentScheduleRunner;
use App\Services\Agents\AgentScheduleService;
use App\Services\Agents\AgentTeamService;
use App\Services\Agents\WeeklyMarketingPlanService;
use App\Services\ConversionGoalProfileService;
use Database\Seeders\AgentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AgentSchedulingAndTriggersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AgentSeeder::class);
    }

    public function test_default_schedules_are_goal_aware_and_duplicate_safe(): void
    {
        $service = app(AgentScheduleService::class);
        $appointment = $this->website();
        $saas = $this->website('saas_signup_and_subscription');
        $service->createDefaultSchedules($appointment);
        $service->createDefaultSchedules($appointment);
        $service->createDefaultSchedules($saas);

        $this->assertSame(4, $appointment->agentSchedules()->count());
        $this->assertSame(5, $saas->agentSchedules()->count());
        $this->assertTrue($appointment->agentSchedules()->where('schedule_type', 'after_gsc_sync')->firstOrFail()->enabled);
        $this->assertFalse($saas->agentSchedules()->where('schedule_type', 'custom_agent')->firstOrFail()->enabled);
    }

    public function test_due_schedule_runs_correct_agent_and_disabled_schedule_does_not_run(): void
    {
        $website = $this->website();
        $schedule = app(AgentScheduleService::class)->createDefaultSchedules($website)->firstWhere('schedule_type', 'daily_analytics');
        $schedule->update(['next_run_at' => now()->subMinute()]);
        $this->artisan('agents:run-scheduled')->assertSuccessful();
        $this->assertDatabaseHas('agent_runs', ['website_id' => $website->id, 'agent_id' => $schedule->agent_id, 'run_type' => 'scheduled', 'trigger_type' => 'scheduled']);
        $count = AgentRun::count();
        app(AgentScheduleService::class)->disable($schedule);
        $schedule->update(['next_run_at' => now()->subMinute()]);
        $this->artisan('agents:run-scheduled')->assertSuccessful();
        $this->assertSame($count, AgentRun::count());
    }

    public function test_overlapping_scheduled_run_is_prevented(): void
    {
        $schedule = app(AgentScheduleService::class)->createDefaultSchedules($this->website())->firstWhere('schedule_type', 'daily_analytics');
        $lock = Cache::lock('agent-schedule:workspace:'.$schedule->website_id.':'.$schedule->schedule_type, 600);
        $this->assertTrue($lock->get());
        try { app(AgentScheduleRunner::class)->run($schedule); $this->fail('Expected overlap rejection.'); }
        catch (\RuntimeException $exception) { $this->assertSame('This workspace schedule is already running.', $exception->getMessage()); }
        finally { $lock->release(); }
    }

    public function test_successful_sync_event_runs_analytics_but_insignificant_change_runs_no_specialist(): void
    {
        $website = $this->website();
        app(AgentScheduleService::class)->createDefaultSchedules($website);
        [$previous, $current] = $this->syncPair($website, ['clicks' => 20, 'impressions' => 200, 'ctr' => 3, 'position' => 8], ['clicks' => 21, 'impressions' => 205, 'ctr' => 3.1, 'position' => 8.1]);
        $this->dispatchSync($current);
        $this->dispatchSync($current);

        $runs = AgentRun::where('website_id', $website->id)->where('trigger_type', 'gsc_sync')->get();
        $this->assertCount(1, $runs);
        $this->assertSame('analytics-reporting', $runs->first()->agent->slug);
        $this->assertSame('completed', $website->agentSchedules()->where('schedule_type', 'after_gsc_sync')->first()->last_status);
    }

    public function test_ctr_decline_triggers_acquisition_agent(): void
    {
        $website = $this->website(); app(AgentScheduleService::class)->createDefaultSchedules($website);
        [$previous, $current] = $this->syncPair($website);
        $this->page($website, $previous, 5, 100, 4);
        $this->page($website, $current, 5, 100, 1);
        $this->dispatchSync($current);
        $this->assertTriggered($website, 'acquisition-growth');
    }

    public function test_new_service_query_without_page_triggers_content_agent(): void
    {
        $website = $this->website(); app(AgentScheduleService::class)->createDefaultSchedules($website);
        [, $current] = $this->syncPair($website);
        GscQuery::create(['website_id' => $website->id, 'query' => 'osteopathie lyon', 'clicks' => 3, 'impressions' => 80, 'ctr' => 4, 'position' => 18, 'date_start' => $current->date_start, 'date_end' => $current->date_end]);
        $this->dispatchSync($current);
        $this->assertTriggered($website, 'content-strategy');
    }

    public function test_traffic_without_conversion_tracking_triggers_conversion_agent(): void
    {
        $website = $this->website(); app(AgentScheduleService::class)->createDefaultSchedules($website);
        [$previous, $current] = $this->syncPair($website);
        $this->page($website, $previous, 5, 100, 3);
        $this->page($website, $current, 10, 130, 3);
        $this->dispatchSync($current);
        $this->assertTriggered($website, 'conversion');
    }

    public function test_appointment_workspace_never_triggers_retention_agent(): void
    {
        $website = $this->website(); app(AgentScheduleService::class)->createDefaultSchedules($website);
        ConversionEvent::create(['website_id' => $website->id, 'event_uuid' => (string) Str::uuid(), 'event_type' => 'trial_started', 'page_url' => $website->url, 'occurred_at' => now()]);
        [, $current] = $this->syncPair($website);
        $this->dispatchSync($current);
        $this->assertDatabaseMissing('agent_runs', ['website_id' => $website->id, 'agent_id' => Agent::where('slug', 'retention-lifecycle')->value('id'), 'trigger_type' => 'gsc_sync']);
    }

    public function test_weekly_full_team_schedule_creates_one_plan(): void
    {
        $website = $this->website();
        $schedule = app(AgentScheduleService::class)->createDefaultSchedules($website)->firstWhere('schedule_type', 'weekly_full_team');
        app(AgentScheduleRunner::class)->run($schedule);
        app(AgentScheduleRunner::class)->run($schedule);
        $this->assertSame(1, WeeklyMarketingPlan::where('website_id', $website->id)->count());
    }

    public function test_open_task_prevents_duplicate_weekly_priority(): void
    {
        $website = $this->website();
        $run = app(AgentTeamService::class)->runAgent(Agent::where('slug', 'conversion')->firstOrFail(), $website);
        $action = $run->actions->firstOrFail();
        $taskTitle = data_get($action->metadata, 'suggested_task', $action->title);
        MarketingTask::create(['website_id' => $website->id, 'title' => $taskTitle, 'priority' => 'medium', 'status' => 'pending', 'source_type' => 'agent_action']);
        $plan = app(WeeklyMarketingPlanService::class)->generate($website);
        $this->assertNotContains($taskTitle, collect($plan->top_priorities)->pluck('recommended_task')->all());
    }

    public function test_guest_users_cannot_access_schedule_or_plan_routes(): void
    {
        $website = $this->website();
        $schedule = app(AgentScheduleService::class)->createDefaultSchedules($website)->first();
        $plan = app(WeeklyMarketingPlanService::class)->generate($website);
        $this->get(route('agent-schedules.index'))->assertRedirect(route('login'));
        $this->patch(route('agent-schedules.toggle', $schedule))->assertRedirect(route('login'));
        $this->post(route('agent-schedules.run', $schedule))->assertRedirect(route('login'));
        $this->get(route('websites.weekly-marketing-plans.index', $website))->assertRedirect(route('login'));
        $this->patch(route('weekly-marketing-plans.update', $plan), ['status' => 'approved'])->assertRedirect(route('login'));
    }

    public function test_authenticated_schedule_and_plan_pages_render(): void
    {
        $website = $this->website();
        app(AgentScheduleService::class)->createDefaultSchedules($website);
        app(WeeklyMarketingPlanService::class)->generate($website);
        $user = User::create(['name' => 'Schedule Tester', 'email' => 'schedule@example.com', 'password' => Hash::make('password')]);
        $this->actingAs($user)->get(route('agent-schedules.index'))->assertOk()->assertSee('Agent run schedule');
        $this->actingAs($user)->get(route('websites.weekly-marketing-plans.index', $website))->assertOk()->assertSee('Weekly Marketing Plans');
        $this->actingAs($user)->get(route('websites.agents.index', $website))->assertOk()->assertSee('Next full-team run');
    }

    private function syncPair(Website $website, array $old = [], array $new = []): array
    {
        $old += ['clicks' => 10, 'impressions' => 100, 'ctr' => 3, 'position' => 8];
        $new += ['clicks' => 10, 'impressions' => 100, 'ctr' => 3, 'position' => 8];
        $previous = $this->sync($website, now()->subDays(56), now()->subDays(29), $old, now()->subDay());
        $current = $this->sync($website, now()->subDays(28), now()->subDay(), $new, now());
        return [$previous, $current];
    }

    private function sync(Website $website, $start, $end, array $metrics, $syncedAt): GscSync
    {
        return GscSync::create(['website_id' => $website->id, 'property_url' => 'sc-domain:example.com', 'date_start' => $start->toDateString(), 'date_end' => $end->toDateString(), 'search_type' => 'web', 'total_clicks' => $metrics['clicks'], 'total_impressions' => $metrics['impressions'], 'average_ctr' => $metrics['ctr'], 'average_position' => $metrics['position'], 'status' => 'success', 'synced_at' => $syncedAt]);
    }

    private function page(Website $website, GscSync $sync, int $clicks, int $impressions, float $ctr): GscPage
    {
        return GscPage::create(['website_id' => $website->id, 'page_url' => $website->url.'/osteopathy-service', 'clicks' => $clicks, 'impressions' => $impressions, 'ctr' => $ctr, 'position' => 8, 'date_start' => $sync->date_start, 'date_end' => $sync->date_end]);
    }

    private function dispatchSync(GscSync $sync): void
    {
        SearchConsoleSyncCompleted::dispatch($sync->website_id, $sync->id, $sync->property_url, $sync->date_start->toDateString(), $sync->date_end->toDateString(), $sync->total_clicks, $sync->total_impressions, $sync->average_ctr, $sync->average_position, []);
    }

    private function assertTriggered(Website $website, string $slug): void
    {
        $this->assertDatabaseHas('agent_runs', ['website_id' => $website->id, 'agent_id' => Agent::where('slug', $slug)->value('id'), 'trigger_type' => 'gsc_sync', 'status' => 'completed']);
    }

    private function website(string $goal = 'appointment_booking'): Website
    {
        $profile = app(ConversionGoalProfileService::class)->profiles()[$goal];
        return Website::create(['name' => $goal.' scheduled '.Website::count(), 'url' => 'https://scheduled-'.Website::count().'.example.com', 'type' => $goal === 'appointment_booking' ? 'professional_services' : 'saas', 'language' => 'en', 'timezone' => 'Africa/Nairobi', 'status' => 'active', 'primary_conversion_goal' => $goal, 'secondary_conversion_goals' => $profile['secondary_conversion_goals'], 'target_audience' => $profile['default_target_audience'], 'business_model' => $profile['default_business_model'], 'conversion_labels' => $profile['conversion_labels']]);
    }
}
