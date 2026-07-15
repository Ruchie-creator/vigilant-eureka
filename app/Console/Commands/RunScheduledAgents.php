<?php

namespace App\Console\Commands;

use App\Services\Agents\AgentScheduleRunner;
use App\Services\Agents\AgentScheduleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class RunScheduledAgents extends Command
{
    protected $signature = 'agents:run-scheduled';
    protected $description = 'Run all due agent schedules without overlapping workspace schedule runs.';

    public function handle(AgentScheduleService $schedules, AgentScheduleRunner $runner): int
    {
        foreach ($schedules->dueSchedules() as $schedule) {
            try {
                $schedules->markRunning($schedule);
                $result = $runner->run($schedule->fresh(['website', 'agent']));
                $failed = is_array($result) ? collect($result)->contains(fn ($run) => $run->status === 'failed') : $result->status === 'failed';
                $failed ? $schedules->markFailed($schedule) : $schedules->markCompleted($schedule);
            } catch (Throwable $exception) {
                $schedules->markFailed($schedule);
                Log::error('Scheduled agent run failed.', ['workspace' => $schedule->website_id, 'schedule_type' => $schedule->schedule_type, 'status' => 'failed', 'error_summary' => Str::limit($this->safeError($exception->getMessage()), 500)]);
                $this->error('Schedule '.$schedule->id.' failed; continuing.');
            }
        }
        return self::SUCCESS;
    }

    private function safeError(string $message): string
    {
        return preg_replace('/(Bearer\s+)[^\s]+|(api[_-]?key|token|authorization|password|secret)\s*[:=]\s*[^\s,;]+/i', '[redacted]', $message) ?: 'Scheduled run failed.';
    }
}
