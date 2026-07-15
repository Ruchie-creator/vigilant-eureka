<?php

namespace App\Services\Agents;

use App\Models\Agent;
use App\Models\AgentSchedule;
use App\Models\Website;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AgentScheduleService
{
    public const TYPES = ['daily_analytics', 'weekly_full_team', 'weekly_marketing_plan', 'custom_agent', 'after_gsc_sync'];
    public const FREQUENCIES = ['daily', 'weekly', 'monthly', 'manual', 'event_triggered'];

    public function createDefaultSchedules(Website $website): Collection
    {
        $analytics = Agent::where('slug', 'analytics-reporting')->firstOrFail();
        $director = Agent::where('slug', 'marketing-director')->firstOrFail();
        $defaults = [
            ['agent_id' => $analytics->id, 'schedule_type' => 'daily_analytics', 'frequency' => 'daily', 'run_at' => '06:00', 'enabled' => true],
            ['agent_id' => null, 'schedule_type' => 'weekly_full_team', 'frequency' => 'weekly', 'run_at' => '07:00', 'enabled' => true, 'settings' => ['weekday' => 1]],
            ['agent_id' => $director->id, 'schedule_type' => 'weekly_marketing_plan', 'frequency' => 'weekly', 'run_at' => '08:00', 'enabled' => true, 'settings' => ['weekday' => 1]],
            ['agent_id' => $analytics->id, 'schedule_type' => 'after_gsc_sync', 'frequency' => 'event_triggered', 'enabled' => true],
        ];

        if ($this->supportsLifecycle($website) && ($retention = Agent::where('slug', 'retention-lifecycle')->first())) {
            $defaults[] = ['agent_id' => $retention->id, 'schedule_type' => 'custom_agent', 'frequency' => 'weekly', 'run_at' => '09:00', 'enabled' => false, 'settings' => ['weekday' => 2, 'placeholder' => true]];
        }

        return DB::transaction(function () use ($website, $defaults): Collection {
            $created = new Collection();
            foreach ($defaults as $data) {
                $existing = AgentSchedule::where('website_id', $website->id)->where('agent_id', $data['agent_id'])->where('schedule_type', $data['schedule_type'])->where('frequency', $data['frequency'])->first();
                $schedule = $existing ?: AgentSchedule::create(['website_id' => $website->id, 'timezone' => $website->timezone ?: config('app.timezone'), ...$data]);
                if (! $existing && $schedule->enabled) $schedule->update(['next_run_at' => $this->calculateNextRun($schedule)]);
                $created->push($schedule->fresh(['website', 'agent']));
            }
            return $created;
        });
    }

    public function calculateNextRun(AgentSchedule $schedule, ?CarbonInterface $from = null): ?CarbonImmutable
    {
        if (! $schedule->enabled || in_array($schedule->frequency, ['manual', 'event_triggered'], true)) return null;
        $timezone = $schedule->timezone ?: $schedule->website?->timezone ?: config('app.timezone');
        $now = CarbonImmutable::instance(($from ?: now())->toDateTime())->setTimezone($timezone);
        [$hour, $minute] = array_map('intval', explode(':', (string) ($schedule->run_at ?: '06:00')));
        $candidate = $now->setTime($hour, $minute);

        $candidate = match ($schedule->frequency) {
            'daily' => $candidate->lessThanOrEqualTo($now) ? $candidate->addDay() : $candidate,
            'weekly' => $this->nextWeekday($candidate, $now, (int) data_get($schedule->settings, 'weekday', 1)),
            'monthly' => $this->nextMonthDay($candidate, $now, (int) data_get($schedule->settings, 'month_day', 1)),
            default => throw new InvalidArgumentException('Unsupported schedule frequency.'),
        };

        return $candidate->setTimezone(config('app.timezone'));
    }

    public function dueSchedules(?CarbonInterface $at = null): Collection
    {
        return AgentSchedule::with(['website', 'agent'])->where('enabled', true)->whereNotIn('frequency', ['manual', 'event_triggered'])->whereNotNull('next_run_at')->where('next_run_at', '<=', $at ?: now())->orderBy('next_run_at')->get();
    }

    public function markRunning(AgentSchedule $schedule): AgentSchedule { $schedule->update(['last_run_at' => $schedule->last_status === 'running' ? $schedule->last_run_at : now(), 'last_status' => 'running']); return $schedule->fresh(); }
    public function markCompleted(AgentSchedule $schedule): AgentSchedule { $schedule->update(['last_status' => 'completed', 'next_run_at' => $this->calculateNextRun($schedule, now()->addSecond())]); return $schedule->fresh(); }
    public function markFailed(AgentSchedule $schedule): AgentSchedule { $schedule->update(['last_status' => 'failed', 'next_run_at' => $this->calculateNextRun($schedule, now()->addSecond())]); return $schedule->fresh(); }
    public function enable(AgentSchedule $schedule): AgentSchedule
    {
        if ($schedule->agent?->slug === 'retention-lifecycle' && data_get($schedule->settings, 'placeholder') && ! $schedule->website->conversionEvents()->whereIn('event_type', ['trial_started', 'onboarding_completed', 'subscription_started'])->exists()) {
            throw new InvalidArgumentException('Retention scheduling needs supported lifecycle conversion data first.');
        }
        $schedule->update(['enabled' => true]);
        $schedule->update(['next_run_at' => $this->calculateNextRun($schedule)]);
        return $schedule->fresh();
    }
    public function disable(AgentSchedule $schedule): AgentSchedule { $schedule->update(['enabled' => false, 'next_run_at' => null]); return $schedule->fresh(); }

    public function updateSchedule(AgentSchedule $schedule, array $data): AgentSchedule
    {
        $schedule->update(collect($data)->only(['frequency', 'timezone', 'run_at', 'settings'])->all());
        $schedule->update(['next_run_at' => $this->calculateNextRun($schedule)]);
        return $schedule->fresh();
    }

    public function preventDuplicateSchedule(Website $website, ?Agent $agent, string $type, string $frequency, ?int $exceptId = null): bool
    {
        return ! AgentSchedule::where('website_id', $website->id)->where('agent_id', $agent?->id)->where('schedule_type', $type)->where('frequency', $frequency)->where('enabled', true)->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))->exists();
    }

    private function nextWeekday(CarbonImmutable $candidate, CarbonImmutable $now, int $weekday): CarbonImmutable
    {
        $daysUntil = ($weekday - $candidate->dayOfWeek + 7) % 7;
        $candidate = $candidate->addDays($daysUntil);
        return $candidate->lessThanOrEqualTo($now) ? $candidate->addWeek() : $candidate;
    }

    private function nextMonthDay(CarbonImmutable $candidate, CarbonImmutable $now, int $day): CarbonImmutable
    {
        $candidate = $candidate->day(min(max($day, 1), $candidate->daysInMonth));
        if ($candidate->lessThanOrEqualTo($now)) {
            $candidate = $candidate->addMonth();
            $candidate = $candidate->day(min(max($day, 1), $candidate->daysInMonth));
        }
        return $candidate;
    }

    private function supportsLifecycle(Website $website): bool
    {
        return in_array($website->primary_conversion_goal, ['saas_signup', 'saas_signup_and_subscription', 'trial_activation', 'paid_subscription', 'loyalty_retention'], true);
    }
}
