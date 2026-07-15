<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentAction;
use App\Models\AgentRun;
use App\Models\MarketingTask;
use App\Models\User;
use App\Models\Website;
use App\Services\Agents\AgentTeamService;
use App\Services\ConversionGoalProfileService;
use Database\Seeders\AgentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApprovalAndOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AgentSeeder::class);
    }

    public function test_guest_cannot_access_approval_or_operations_pages(): void
    {
        $run = $this->agentRun();
        $this->get(route('approvals.index'))->assertRedirect(route('login'));
        $this->get(route('agent-operations.index'))->assertRedirect(route('login'));
        $this->get(route('agent-operations.runs.show', $run))->assertRedirect(route('login'));
        $this->post(route('agent-operations.runs.retry', $run))->assertRedirect(route('login'));
    }

    public function test_pending_agent_action_appears_in_approval_inbox(): void
    {
        $action = $this->action();
        $this->actingAs($this->user())->get(route('approvals.index'))->assertOk()->assertSee($action->title)->assertSee($action->website->name);
    }

    public function test_approval_updates_reviewer_metadata(): void
    {
        $action = $this->action();
        $user = $this->user();
        $this->actingAs($user)->patch(route('approvals.update', ['action', $action->id]), ['operation' => 'approve', 'review_notes' => 'Evidence checked.'])->assertRedirect();
        $action->refresh();
        $this->assertSame('approved', $action->status);
        $this->assertSame($user->id, $action->reviewed_by);
        $this->assertNotNull($action->reviewed_at);
        $this->assertSame('Evidence checked.', $action->review_notes);
    }

    public function test_approve_and_create_task_is_duplicate_safe(): void
    {
        $action = $this->action();
        $user = $this->user();
        $url = route('approvals.update', ['action', $action->id]);
        $this->actingAs($user)->patch($url, ['operation' => 'approve_task'])->assertRedirect();
        $this->actingAs($user)->patch($url, ['operation' => 'approve_task'])->assertRedirect();
        $this->assertSame(1, MarketingTask::where('website_id', $action->website_id)->count());
        $this->assertSame('approved', $action->fresh()->createdTask->approval_status);
    }

    public function test_request_revision_creates_one_linked_pending_action(): void
    {
        $action = $this->action();
        $url = route('approvals.update', ['action', $action->id]);
        $payload = ['operation' => 'request_revision', 'revision_reason' => 'Use the mobile evidence and name the affected CTA.'];
        $this->actingAs($this->user())->patch($url, $payload)->assertRedirect();
        $this->actingAs($this->user())->patch($url, $payload)->assertRedirect();
        $this->assertSame('revision_requested', $action->fresh()->status);
        $this->assertSame(1, AgentAction::where('original_action_id', $action->id)->count());
        $revised = AgentAction::where('original_action_id', $action->id)->firstOrFail();
        $this->assertSame('pending', $revised->status);
        $this->assertSame('revision', $revised->run->run_type);
        $this->assertStringContainsString('mobile evidence', $revised->run->input_summary);
    }

    public function test_ignored_item_retains_audit_record(): void
    {
        $action = $this->action();
        $user = $this->user();
        $this->actingAs($user)->patch(route('approvals.update', ['action', $action->id]), ['operation' => 'ignore', 'review_notes' => 'Not aligned this week.'])->assertRedirect();
        $action->refresh();
        $this->assertSame('ignored', $action->status);
        $this->assertSame($user->id, $action->reviewed_by);
        $this->assertSame('Not aligned this week.', $action->review_notes);
        $this->assertDatabaseHas('agent_actions', ['id' => $action->id]);
    }

    public function test_failed_run_appears_in_operations_dashboard(): void
    {
        $run = $this->failedRun();
        $this->actingAs($this->user())->get(route('agent-operations.index'))->assertOk()->assertSee($run->agent->name)->assertSee('Failed');
    }

    public function test_retry_increments_count_and_completes_failed_run(): void
    {
        $run = $this->failedRun();
        $this->actingAs($this->user())->post(route('agent-operations.runs.retry', $run))->assertRedirect();
        $run->refresh();
        $this->assertSame(1, $run->retry_count);
        $this->assertSame('completed', $run->status);
        $this->assertSame('retry', $run->trigger_type);
    }

    public function test_retry_does_not_duplicate_actions_or_tasks(): void
    {
        $run = $this->agentRun();
        $action = $run->actions->firstOrFail();
        $this->actingAs($this->user())->post(route('agent-actions.tasks.store', $action))->assertRedirect();
        $run->update(['status' => 'failed', 'error_message' => 'Temporary internal failure.']);
        $this->actingAs($this->user())->post(route('agent-operations.runs.retry', $run))->assertRedirect();
        $this->assertSame(1, $run->actions()->count());
        $this->assertSame(1, MarketingTask::where('website_id', $run->website_id)->count());
    }

    public function test_run_detail_never_exposes_stored_credentials(): void
    {
        $run = $this->failedRun();
        $run->update(['input_summary' => 'Authorization: Bearer live-secret-token', 'output_summary' => 'OPENAI_API_KEY=secret-key-value', 'error_message' => 'password=stored-password']);
        $response = $this->actingAs($this->user())->get(route('agent-operations.runs.show', $run))->assertOk();
        $response->assertDontSee('live-secret-token')->assertDontSee('secret-key-value')->assertDontSee('stored-password')->assertSee('[redacted]');
    }

    public function test_workspace_command_center_shows_pending_approval_count(): void
    {
        $action = $this->action();
        $this->actingAs($this->user())->get(route('websites.agents.index', $action->website))->assertOk()->assertSee('Pending approvals')->assertSee('Workspace approvals');
    }

    private function action(): AgentAction
    {
        return $this->agentRun()->actions->firstOrFail();
    }

    private function agentRun(): AgentRun
    {
        return app(AgentTeamService::class)->runAgent(Agent::where('slug', 'conversion')->firstOrFail(), $this->website());
    }

    private function failedRun(): AgentRun
    {
        $website = $this->website();
        return AgentRun::create(['agent_id' => Agent::where('slug', 'conversion')->value('id'), 'website_id' => $website->id, 'run_type' => 'manual', 'trigger_type' => 'manual', 'correlation_id' => '11111111-1111-4111-8111-111111111111', 'status' => 'failed', 'error_message' => 'Temporary failure.', 'started_at' => now(), 'completed_at' => now()]);
    }

    private function user(): User
    {
        return User::firstOrCreate(['email' => 'approver@example.com'], ['name' => 'Approval Reviewer', 'password' => Hash::make('password')]);
    }

    private function website(): Website
    {
        $profile = app(ConversionGoalProfileService::class)->profiles()['appointment_booking'];
        return Website::create(['name' => 'Approval workspace '.Website::count(), 'url' => 'https://approval-'.Website::count().'.example.com', 'type' => 'professional_services', 'language' => 'en', 'status' => 'active', 'primary_conversion_goal' => 'appointment_booking', 'secondary_conversion_goals' => $profile['secondary_conversion_goals'], 'target_audience' => $profile['default_target_audience'], 'business_model' => $profile['default_business_model'], 'conversion_labels' => $profile['conversion_labels']]);
    }
}
