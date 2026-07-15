<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentHandoff;
use App\Models\AgentMemory;
use App\Models\GscPage;
use App\Models\User;
use App\Models\Website;
use App\Services\Agents\AgentHandoffService;
use App\Services\Agents\AgentMemoryService;
use App\Services\Agents\AgentTeamService;
use App\Services\ConversionGoalProfileService;
use Database\Seeders\AgentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AgentMemoryAndHandoffTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AgentSeeder::class);
    }

    public function test_approved_and_ignored_actions_create_agent_memory(): void
    {
        $website = $this->website();
        $agent = Agent::where('slug', 'conversion')->firstOrFail();
        $first = app(AgentTeamService::class)->runAgent($agent, $website)->actions->firstOrFail();
        $second = app(AgentTeamService::class)->runAgent($agent, $website)->actions->firstOrFail();

        $this->actingAs($this->user())->patch(route('agent-actions.update', $first), ['status' => 'approved'])->assertRedirect();
        $this->actingAs($this->user())->patch(route('agent-actions.update', $second), ['status' => 'ignored'])->assertRedirect();

        $this->assertDatabaseHas('agent_memories', ['agent_id' => $agent->id, 'website_id' => $website->id, 'memory_type' => 'approved_action', 'source_id' => $first->id]);
        $this->assertDatabaseHas('agent_memories', ['agent_id' => $agent->id, 'website_id' => $website->id, 'memory_type' => 'ignored_action', 'source_id' => $second->id]);
    }

    public function test_completed_agent_task_creates_completed_work_memory(): void
    {
        $website = $this->website();
        $agent = Agent::where('slug', 'conversion')->firstOrFail();
        $action = app(AgentTeamService::class)->runAgent($agent, $website)->actions->firstOrFail();
        $user = $this->user();
        $this->actingAs($user)->post(route('agent-actions.tasks.store', $action))->assertRedirect();

        $this->actingAs($user)->patch(route('marketing-tasks.status.update', $action->fresh()->createdTask), ['status' => 'completed'])->assertRedirect();

        $this->assertDatabaseHas('agent_memories', ['agent_id' => $agent->id, 'website_id' => $website->id, 'memory_type' => 'completed_task', 'source_id' => $action->fresh()->created_task_id]);
    }

    public function test_approved_director_action_creates_task_manager_handoff(): void
    {
        $website = $this->website();
        $director = Agent::where('slug', 'marketing-director')->firstOrFail();
        $taskManager = Agent::where('slug', 'task-manager')->firstOrFail();
        $action = app(AgentTeamService::class)->runAgent($director, $website)->actions->firstOrFail();

        $this->actingAs($this->user())->patch(route('agent-actions.update', $action), ['status' => 'approved'])->assertRedirect();

        $this->assertDatabaseHas('agent_handoffs', ['website_id' => $website->id, 'from_agent_id' => $director->id, 'to_agent_id' => $taskManager->id, 'status' => 'pending']);
    }

    public function test_duplicate_memory_updates_existing_record(): void
    {
        $website = $this->website();
        $agent = Agent::firstOrFail();
        $service = app(AgentMemoryService::class);

        $first = $service->remember($agent, $website, 'workspace_context', 'preferred-focus', 'First value');
        $second = $service->updateOrRemember($agent, $website, 'workspace_context', 'preferred-focus', 'Updated value');

        $this->assertSame($first->id, $second->id);
        $this->assertSame('Updated value', $first->fresh()->memory_value);
        $this->assertSame(1, AgentMemory::count());
    }

    public function test_expired_memory_is_excluded_from_agent_context(): void
    {
        $website = $this->website();
        $agent = Agent::firstOrFail();
        $service = app(AgentMemoryService::class);
        $service->remember($agent, $website, 'user_instruction', 'expired-instruction', 'Do not use this old instruction.', ['expires_at' => now()->subMinute()]);
        $service->remember($agent, $website, 'user_instruction', 'active-instruction', 'Use the active instruction.');

        $context = $service->buildAgentMemoryContext($agent, $website);

        $this->assertNotContains('Do not use this old instruction.', $context['workspace_preferences']);
        $this->assertContains('Use the active instruction.', $context['workspace_preferences']);
    }

    public function test_analytics_creates_acquisition_handoff_for_high_impressions_and_weak_ctr(): void
    {
        $website = $this->website();
        $this->page($website);
        $analytics = Agent::where('slug', 'analytics-reporting')->firstOrFail();
        $acquisition = Agent::where('slug', 'acquisition-growth')->firstOrFail();

        app(AgentTeamService::class)->runAgent($analytics, $website);

        $this->assertDatabaseHas('agent_handoffs', [
            'website_id' => $website->id,
            'from_agent_id' => $analytics->id,
            'to_agent_id' => $acquisition->id,
            'status' => 'pending',
        ]);
    }

    public function test_duplicate_pending_handoff_is_not_created(): void
    {
        $website = $this->website();
        $from = Agent::where('slug', 'analytics-reporting')->firstOrFail();
        $to = Agent::where('slug', 'acquisition-growth')->firstOrFail();
        $service = app(AgentHandoffService::class);
        $context = ['related_page_url' => 'https://example.com/service', 'related_query' => null];

        $first = $service->createHandoff($website, $from, $to, 'Weak CTR.', $context);
        $second = $service->createHandoff($website, $from, $to, 'Weak CTR.', $context);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, AgentHandoff::count());
    }

    public function test_accepted_handoff_is_included_and_completed_by_receiving_run(): void
    {
        $website = $this->website();
        $from = Agent::where('slug', 'analytics-reporting')->firstOrFail();
        $to = Agent::where('slug', 'acquisition-growth')->firstOrFail();
        $service = app(AgentHandoffService::class);
        $handoff = $service->createHandoff($website, $from, $to, 'Review weak CTR.', ['related_page_url' => 'https://example.com/service'], 'Recommend an acquisition change.');
        $service->acceptHandoff($handoff);

        $run = app(AgentTeamService::class)->runAgent($to, $website);

        $this->assertSame($handoff->id, data_get($run->metadata, 'handoff_context.0.id'));
        $this->assertSame('completed', $handoff->fresh()->status);
        $this->assertNotNull($handoff->fresh()->completed_at);
    }

    public function test_full_team_uses_memory_and_completes_same_run_handoff(): void
    {
        $website = $this->website();
        $this->page($website);
        $acquisition = Agent::where('slug', 'acquisition-growth')->firstOrFail();
        app(AgentMemoryService::class)->remember($acquisition, $website, 'user_instruction', 'team-focus', 'Prioritize the service page CTA.');

        $runs = app(AgentTeamService::class)->runFullTeam($website);
        $run = $runs->first(fn ($run) => $run->agent->is($acquisition));

        $this->assertContains('Prioritize the service page CTA.', data_get($run->metadata, 'memory_context.workspace_preferences'));
        $this->assertNotEmpty(data_get($run->metadata, 'handoff_context'));
        $this->assertDatabaseHas('agent_handoffs', ['website_id' => $website->id, 'to_agent_id' => $acquisition->id, 'status' => 'completed']);
    }

    public function test_agent_memory_and_handoff_routes_require_authentication(): void
    {
        $website = $this->website();
        $agent = Agent::firstOrFail();
        $memory = app(AgentMemoryService::class)->remember($agent, $website, 'workspace_context', 'test', 'Test context');
        $handoff = app(AgentHandoffService::class)->createHandoff($website, $agent, Agent::whereKeyNot($agent->id)->firstOrFail(), 'Test handoff.');

        $this->get(route('agent-memories.index'))->assertRedirect(route('login'));
        $this->get(route('agents.memories.index', $agent))->assertRedirect(route('login'));
        $this->post(route('agents.memories.store', $agent), [])->assertRedirect(route('login'));
        $this->delete(route('agent-memories.destroy', $memory))->assertRedirect(route('login'));
        $this->get(route('agent-handoffs.index'))->assertRedirect(route('login'));
        $this->patch(route('agent-handoffs.update', $handoff), ['status' => 'accepted'])->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_render_memory_and_handoff_pages(): void
    {
        $website = $this->website();
        $agent = Agent::firstOrFail();
        app(AgentMemoryService::class)->remember($agent, $website, 'workspace_context', 'render-test', 'Visible workspace context', ['confidence' => 0.8]);
        app(AgentHandoffService::class)->createHandoff($website, $agent, Agent::whereKeyNot($agent->id)->firstOrFail(), 'Visible handoff.');
        $user = $this->user();

        $this->actingAs($user)->get(route('agents.show', $agent))->assertOk()->assertSee('Agent Memory')->assertSee('Agent Handoffs');
        $this->actingAs($user)->get(route('agents.memories.index', $agent))->assertOk()->assertSee('Visible workspace context');
        $this->actingAs($user)->get(route('agent-handoffs.index'))->assertOk()->assertSee('Visible handoff.');
    }

    private function page(Website $website): GscPage
    {
        return GscPage::create(['website_id' => $website->id, 'page_url' => $website->url.'/service', 'clicks' => 8, 'impressions' => 800, 'ctr' => 1, 'position' => 7.5, 'date_start' => now()->subDays(28)->toDateString(), 'date_end' => now()->toDateString()]);
    }

    private function user(): User
    {
        return User::firstOrCreate(['email' => 'memory-tester@example.com'], ['name' => 'Memory Tester', 'password' => Hash::make('password')]);
    }

    private function website(): Website
    {
        $profile = app(ConversionGoalProfileService::class)->profiles()['appointment_booking'];

        return Website::create(['name' => 'Memory workspace '.Website::count(), 'url' => 'https://memory-'.Website::count().'.example.com', 'type' => 'professional_services', 'language' => 'en', 'status' => 'active', 'primary_conversion_goal' => 'appointment_booking', 'secondary_conversion_goals' => $profile['secondary_conversion_goals'], 'target_audience' => $profile['default_target_audience'], 'business_model' => $profile['default_business_model'], 'conversion_labels' => $profile['conversion_labels']]);
    }
}
