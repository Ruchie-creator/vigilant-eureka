<?php

namespace App\Listeners;

use App\Events\SearchConsoleSyncCompleted;
use App\Models\Agent;
use App\Models\AgentSchedule;
use App\Models\AgentRun;
use App\Models\GscSync;
use App\Models\Website;
use App\Services\Agents\AgentHandoffService;
use App\Services\Agents\AgentScheduleService;
use App\Services\Agents\AgentTeamService;
use App\Services\Agents\MeaningfulChangeDetector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class TriggerPostSyncAgentAnalysis
{
    public function __construct(private readonly AgentTeamService $team, private readonly AgentHandoffService $handoffs, private readonly MeaningfulChangeDetector $changes, private readonly AgentScheduleService $schedules) {}

    public function handle(SearchConsoleSyncCompleted $event): void
    {
        $schedule = AgentSchedule::with(['website', 'agent'])->where('website_id', $event->websiteId)->where('schedule_type', 'after_gsc_sync')->where('enabled', true)->first();
        if (! $schedule) return;
        $analyticsId = Agent::where('slug', 'analytics-reporting')->value('id');
        if (AgentRun::where('website_id', $event->websiteId)->where('agent_id', $analyticsId)->where('trigger_type', 'gsc_sync')->where('metadata->sync_id', $event->syncId)->exists()) return;
        $lock = Cache::lock('agent-trigger:workspace:'.$event->websiteId.':gsc-sync', 600);
        if (! $lock->get()) return;

        try {
            $this->schedules->markRunning($schedule);
            $website = Website::findOrFail($event->websiteId);
            $sync = GscSync::findOrFail($event->syncId);
            $analytics = Agent::where('slug', 'analytics-reporting')->where('status', 'active')->firstOrFail();
            $correlationId = (string) Str::uuid();
            $analyticsRun = $this->team->runAgent($analytics, $website, 'event_triggered', ['trigger_type' => 'gsc_sync', 'correlation_id' => $correlationId, 'schedule_id' => $schedule->id, 'trigger_reason' => 'Search Console sync completed', 'trigger_evidence' => ['property' => $event->property, 'date_start' => $event->dateStart, 'date_end' => $event->dateEnd, 'clicks' => $event->clicks, 'impressions' => $event->impressions, 'ctr' => $event->ctr, 'average_position' => $event->averagePosition, 'previous_period_comparison' => $event->previousPeriodComparison], 'sync_id' => $sync->id, 'suppress_structured_handoffs' => true]);
            if ($analyticsRun->status === 'failed') { $this->schedules->markFailed($schedule); return; }

            foreach ($this->changes->detect($website, $sync) as $trigger) {
                $agent = Agent::where('slug', $trigger['agent'])->where('status', 'active')->first();
                if (! $agent) continue;
                $context = [...$trigger['context'], 'sync_id' => $sync->id, 'correlation_id' => $correlationId, 'affected_audience' => $website->target_audience];
                $handoff = $this->handoffs->createHandoff($website, $analytics, $agent, $trigger['reason'], $context, $trigger['expected_output'], $analyticsRun);
                if ($handoff->status === 'pending') $handoff = $this->handoffs->acceptHandoff($handoff);
                $run = $this->team->runAgent($agent, $website, 'event_triggered', ['trigger_type' => 'gsc_sync', 'correlation_id' => $correlationId, 'schedule_id' => $schedule->id, 'trigger_reason' => $trigger['reason'], 'sync_id' => $sync->id]);
                if ($run->status === 'failed' && $handoff->fresh()->status === 'accepted') $this->handoffs->failHandoff($handoff);
            }
            $this->schedules->markCompleted($schedule);
        } catch (Throwable $exception) {
            $this->schedules->markFailed($schedule);
            Log::error('Post-sync agent analysis failed.', ['workspace' => $event->websiteId, 'sync_id' => $event->syncId, 'status' => 'failed', 'error_summary' => Str::limit($this->safeError($exception->getMessage()), 500)]);
        } finally {
            $lock->release();
        }
    }

    private function safeError(string $message): string
    {
        return preg_replace('/(Bearer\s+)[^\s]+|(api[_-]?key|token|authorization|password|secret)\s*[:=]\s*[^\s,;]+/i', '[redacted]', $message) ?: 'Post-sync analysis failed.';
    }
}
