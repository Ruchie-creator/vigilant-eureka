<?php

namespace Tests\Feature;

use App\Models\ActionOutcome;
use App\Models\Agent;
use App\Models\AgentAction;
use App\Models\AgentMemory;
use App\Models\AgentRun;
use App\Models\MarketingTask;
use App\Models\User;
use App\Models\Website;
use App\Services\Agents\AgentLearningService;
use App\Services\Agents\AgentTeamService;
use Database\Seeders\AgentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AgentLearningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AgentSeeder::class);
    }

    public function test_improved_outcome_creates_successful_action_memory(): void
    {
        $memory = app(AgentLearningService::class)->learnFromOutcome($this->outcome('improved', 'high'));
        $this->assertSame('successful_action', $memory->memory_type);
        $this->assertSame('improved', $memory->learning_metadata['measured_result']);
        $this->assertSame(.9, $memory->confidence);
    }

    public function test_declined_outcome_creates_unsuccessful_action_memory(): void
    {
        $memory = app(AgentLearningService::class)->learnFromOutcome($this->outcome('declined', 'medium'));
        $this->assertSame('unsuccessful_action', $memory->memory_type);
        $this->assertStringContainsString('Declined result', $memory->memory_value);
    }

    public function test_duplicate_evaluations_do_not_duplicate_learning(): void
    {
        $outcome = $this->outcome('improved');
        $service = app(AgentLearningService::class);
        $service->learnFromOutcome($outcome);
        $service->learnFromOutcome($outcome);
        $this->assertSame(1, AgentMemory::where('source_type', 'action_outcome')->where('source_id', $outcome->id)->count());
    }

    public function test_expired_or_disabled_learning_is_excluded_from_prompts(): void
    {
        $outcome = $this->outcome('improved');
        $memory = app(AgentLearningService::class)->learnFromOutcome($outcome);
        $memory->update(['enabled' => false]);
        $expired = $memory->replicate()->fill(['memory_key' => 'expired-learning', 'enabled' => true, 'expires_at' => now()->subDay()]);
        $expired->save();
        $context = app(AgentLearningService::class)->buildLearningContext($outcome->agentAction->run->agent, $outcome->website);
        $this->assertEmpty($context['successful_patterns']);
    }

    public function test_successes_increase_and_repeated_failures_reduce_recommendation_confidence(): void
    {
        $outcome = $this->outcome('improved');
        $agent = $outcome->agentAction->run->agent;
        $website = $outcome->website;
        $service = app(AgentLearningService::class);
        $service->learnFromOutcome($outcome);
        $action = ['type' => 'conversion_improvement', 'audience' => 'workspace conversion goal', 'data_sources' => ['conversion events']];
        $successful = $service->scoreRecommendation($agent, $website, $action);
        $this->assertGreaterThan(.7, $successful['confidence_score']);

        AgentMemory::where('website_id', $website->id)->delete();
        foreach (range(1, 3) as $index) AgentMemory::create(['agent_id' => $agent->id, 'website_id' => $website->id, 'memory_type' => 'unsuccessful_action', 'memory_key' => 'failed-'.$index, 'memory_value' => 'A similar action declined.', 'confidence' => .8, 'enabled' => true, 'source_type' => 'action_outcome', 'source_id' => 100 + $index, 'learning_metadata' => ['action_type' => 'conversion_improvement', 'affected_source' => 'workspace conversion goal']]);
        $failed = $service->scoreRecommendation($agent, $website, $action);
        $this->assertLessThan($successful['confidence_score'], $failed['confidence_score']);
        $this->assertSame(3, $failed['similar_failure_count']);
        $this->assertCount(1, $service->identifyRepeatedFailures($agent, $website));
    }

    public function test_marketing_director_receives_outcome_learning_context(): void
    {
        $outcome = $this->outcome('improved');
        app(AgentLearningService::class)->learnFromOutcome($outcome);
        $director = Agent::where('slug', 'marketing-director')->firstOrFail();
        $run = app(AgentTeamService::class)->runAgent($director, $outcome->website);
        $this->assertStringContainsString('Outcome learning context', $run->input_summary);
        $this->assertStringContainsString('positive measured result', $run->input_summary);
    }

    public function test_learning_remains_isolated_by_workspace(): void
    {
        $outcome = $this->outcome('improved');
        app(AgentLearningService::class)->learnFromOutcome($outcome);
        $other = $this->website('Other workspace');
        $context = app(AgentLearningService::class)->buildLearningContext($outcome->agentAction->run->agent, $other);
        $this->assertEmpty($context['successful_patterns']);
    }

    public function test_guest_users_cannot_access_learning_routes(): void
    {
        $memory = app(AgentLearningService::class)->learnFromOutcome($this->outcome('improved'));
        $this->get(route('agent-learning.index'))->assertRedirect(route('login'));
        $this->patch(route('agent-learning.update', $memory), ['memory_type' => 'inconclusive_action', 'confidence' => .3, 'enabled' => 1])->assertRedirect(route('login'));
    }

    public function test_manual_confidence_correction_preserves_original_outcome(): void
    {
        $outcome = $this->outcome('improved', 'high');
        $memory = app(AgentLearningService::class)->learnFromOutcome($outcome);
        $this->actingAs($this->user())->patch(route('agent-learning.update', $memory), ['memory_type' => 'inconclusive_action', 'confidence' => .25, 'enabled' => 0, 'review_notes' => 'External promotion affected this period.'])->assertRedirect();
        $memory->refresh();
        $this->assertSame('inconclusive_action', $memory->memory_type);
        $this->assertSame(.25, $memory->confidence);
        $this->assertFalse($memory->enabled);
        $this->assertSame('improved', $outcome->fresh()->status);
        $this->assertDatabaseHas('action_outcomes', ['id' => $outcome->id]);
    }

    private function outcome(string $status, string $confidence = 'medium'): ActionOutcome
    {
        $website = $this->website('Learning workspace '.Website::count());
        $agent = Agent::where('slug', 'conversion')->firstOrFail();
        $run = AgentRun::create(['agent_id' => $agent->id, 'website_id' => $website->id, 'run_type' => 'manual', 'trigger_type' => 'manual', 'correlation_id' => (string) Str::uuid(), 'status' => 'completed', 'started_at' => now(), 'completed_at' => now()]);
        $task = MarketingTask::create(['website_id' => $website->id, 'title' => 'Improve CTA', 'priority' => 'high', 'source_type' => 'agent_action', 'source_value' => 'booking', 'status' => 'completed']);
        $action = AgentAction::create(['agent_run_id' => $run->id, 'website_id' => $website->id, 'action_type' => 'conversion_improvement', 'title' => 'Improve CTA', 'description' => 'Make the CTA clearer.', 'priority' => 'high', 'status' => 'approved', 'expected_result' => 'More appointment actions.', 'created_task_id' => $task->id]);
        return ActionOutcome::create(['website_id' => $website->id, 'agent_action_id' => $action->id, 'marketing_task_id' => $task->id, 'evaluation_type' => 'conversion_goal', 'status' => $status, 'baseline_metrics' => ['clicks' => 10], 'evaluation_metrics' => ['clicks' => 15], 'metric_changes' => ['clicks' => ['absolute' => 5, 'percentage' => 50]], 'outcome_summary' => 'The available data suggests a positive measured result.', 'confidence' => $confidence, 'evaluated_at' => now()]);
    }

    private function website(string $name): Website
    {
        return Website::create(['name' => $name, 'url' => 'https://learning-'.Str::lower(Str::random(8)).'.example.com/', 'type' => 'professional_services', 'language' => 'en', 'status' => 'active', 'primary_conversion_goal' => 'appointment_booking']);
    }

    private function user(): User
    {
        return User::firstOrCreate(['email' => 'learning@example.com'], ['name' => 'Learning Reviewer', 'password' => Hash::make('password')]);
    }
}
