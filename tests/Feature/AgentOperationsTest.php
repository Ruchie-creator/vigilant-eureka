<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentAction;
use App\Models\AgentRun;
use App\Models\MarketingTask;
use App\Models\User;
use App\Models\Website;
use App\Services\Agents\AgentTeamService;
use App\Services\Agents\ConversionAgentService;
use App\Services\ConversionGoalProfileService;
use Database\Seeders\AgentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class AgentOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AgentSeeder::class);
    }

    public function test_guests_cannot_access_agent_routes(): void
    {
        $website = $this->website();
        $agent = Agent::firstOrFail();
        $run = AgentRun::create(['agent_id' => $agent->id, 'website_id' => $website->id, 'run_type' => 'manual', 'trigger_type' => 'manual', 'status' => 'completed']);
        $action = AgentAction::create(['agent_run_id' => $run->id, 'website_id' => $website->id, 'action_type' => 'recommendation', 'title' => 'Test action', 'description' => 'Review this action.', 'priority' => 'medium', 'status' => 'pending']);

        $requests = [
            ['get', route('agents.index'), []],
            ['get', route('agents.show', $agent), []],
            ['post', route('agents.run', $agent), ['website_id' => $website->id]],
            ['get', route('websites.agents.index', $website), []],
            ['post', route('websites.agents.run-full-team', $website), []],
            ['patch', route('agent-actions.update', $action), ['status' => 'approved']],
            ['post', route('agent-actions.tasks.store', $action), []],
        ];

        foreach ($requests as [$method, $url, $data]) {
            $this->{$method}($url, $data ?? [])->assertRedirect(route('login'));
        }
    }

    public function test_authenticated_user_can_access_agents_and_workspace_command_center(): void
    {
        $website = $this->website();
        $this->actingAs($this->user())->get(route('agents.index'))->assertOk()->assertSee('AI Marketing Team');
        $this->actingAs($this->user())->get(route('websites.agents.index', $website))->assertOk()->assertSee($website->name);
    }

    public function test_full_team_creates_expected_runs_and_director_runs_last(): void
    {
        $runs = app(AgentTeamService::class)->runFullTeam($this->website());
        $director = $runs->first(fn (AgentRun $run) => $run->agent->slug === 'marketing-director');
        $specialists = $runs->reject(fn (AgentRun $run) => $run->is($director));

        $this->assertCount(5, $runs);
        $this->assertSame('completed', $director->status);
        $this->assertCount(4, $specialists);
        $this->assertTrue($specialists->every(fn (AgentRun $run) => $run->parent_run_id === $director->id));
        $this->assertTrue($specialists->every(fn (AgentRun $run) => $run->correlation_id === $director->correlation_id));
        $this->assertTrue($specialists->every(fn (AgentRun $run) => $run->completed_at->lte($director->started_at)));
        $this->assertTrue($runs->every(fn (AgentRun $run) => $run->trigger_type === 'manual'));
        $this->assertTrue($runs->every(fn (AgentRun $run) => $run->retry_count === 0));
        $this->assertNotNull($director->duration_ms);
        $this->assertNotNull($director->input_hash);
        $this->assertNotNull($director->output_hash);
    }

    public function test_retention_agent_runs_only_for_lifecycle_goals(): void
    {
        $appointmentRuns = app(AgentTeamService::class)->runFullTeam($this->website('appointment_booking'));
        $saasRuns = app(AgentTeamService::class)->runFullTeam($this->website('saas_signup_and_subscription'));

        $this->assertFalse($appointmentRuns->contains(fn (AgentRun $run) => $run->agent->slug === 'retention-lifecycle'));
        $this->assertTrue($saasRuns->contains(fn (AgentRun $run) => $run->agent->slug === 'retention-lifecycle'));
        $this->assertCount(6, $saasRuns);
    }

    public function test_agent_actions_are_pending_and_can_be_approved(): void
    {
        $run = app(AgentTeamService::class)->runFullTeam($this->website())->first(fn (AgentRun $run) => $run->agent->slug === 'conversion');
        $action = $run->actions->firstOrFail();
        $this->assertSame('pending', $action->status);

        $this->actingAs($this->user())->patch(route('agent-actions.update', $action), ['status' => 'approved'])->assertRedirect();
        $this->assertSame('approved', $action->fresh()->status);
    }

    public function test_task_creation_from_action_is_duplicate_safe(): void
    {
        $run = app(AgentTeamService::class)->runFullTeam($this->website())->first(fn (AgentRun $run) => $run->agent->slug === 'conversion');
        $action = $run->actions->firstOrFail();
        $user = $this->user();

        $this->actingAs($user)->post(route('agent-actions.tasks.store', $action))->assertRedirect();
        $this->actingAs($user)->post(route('agent-actions.tasks.store', $action))->assertRedirect();

        $this->assertSame(1, MarketingTask::where('website_id', $action->website_id)->count());
        $this->assertNotNull($action->fresh()->created_task_id);
    }

    public function test_failed_specialist_is_recorded_and_parent_run_fails_without_director_action(): void
    {
        $failingService = new class(app(ConversionGoalProfileService::class)) extends ConversionAgentService
        {
            protected function action(Website $website, array $goal): array
            {
                throw new RuntimeException('Authorization: Bearer should-never-be-saved');
            }
        };
        $this->app->instance(ConversionAgentService::class, $failingService);

        $runs = app(AgentTeamService::class)->runFullTeam($this->website());
        $conversion = $runs->first(fn (AgentRun $run) => $run->agent->slug === 'conversion');
        $director = $runs->first(fn (AgentRun $run) => $run->agent->slug === 'marketing-director');

        $this->assertSame('failed', $conversion->status);
        $this->assertStringNotContainsString('should-never-be-saved', $conversion->error_message);
        $this->assertSame('failed', $director->status);
        $this->assertCount(0, $director->actions);
    }

    public function test_overlapping_full_team_run_is_rejected(): void
    {
        $website = $this->website();
        $lock = Cache::lock('agent-team:workspace:'.$website->id, 300);
        $this->assertTrue($lock->get());

        try {
            app(AgentTeamService::class)->runFullTeam($website);
            $this->fail('Expected the overlapping run to be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('A full-team run is already active for this workspace.', $exception->getMessage());
        } finally {
            $lock->release();
        }
    }

    private function user(): User
    {
        return User::firstOrCreate(['email' => 'tester@example.com'], ['name' => 'Test User', 'password' => Hash::make('password')]);
    }

    private function website(string $goal = 'appointment_booking'): Website
    {
        $profile = app(ConversionGoalProfileService::class)->profiles()[$goal];

        return Website::create([
            'name' => $goal.' workspace '.Website::count(),
            'url' => 'https://workspace-'.Website::count().'.example.com',
            'type' => $goal === 'appointment_booking' ? 'professional_services' : 'saas',
            'language' => 'en',
            'status' => 'active',
            'primary_conversion_goal' => $goal,
            'secondary_conversion_goals' => $profile['secondary_conversion_goals'],
            'target_audience' => $profile['default_target_audience'],
            'business_model' => $profile['default_business_model'],
            'conversion_labels' => $profile['conversion_labels'],
        ]);
    }
}
