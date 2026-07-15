<?php

namespace App\Services\Agents;

use App\Models\ActionOutcome;
use App\Models\AgentAction;
use App\Models\ConversionEvent;
use App\Models\GscDailyMetric;
use App\Models\GscPage;
use App\Models\GscQuery;
use App\Models\MarketingTask;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ActionOutcomeService
{
    public function createForCompletedTask(MarketingTask $task): ?ActionOutcome
    {
        if ($task->status !== 'completed' || $task->source_type !== 'agent_action') return null;

        $action = AgentAction::with(['website', 'run.agent'])->where('created_task_id', $task->id)->first();
        if (! $action) return null;

        $outcome = ActionOutcome::firstOrCreate(
            ['agent_action_id' => $action->id, 'marketing_task_id' => $task->id],
            ['website_id' => $task->website_id, 'evaluation_type' => $this->evaluationType($action), 'status' => 'pending_baseline']
        );

        if ($outcome->status === 'pending_baseline') $this->captureBaseline($outcome);

        return $outcome->fresh();
    }

    public function captureBaseline(ActionOutcome $outcome, ?int $days = null): ActionOutcome
    {
        $outcome->loadMissing(['marketingTask', 'agentAction', 'website']);
        $days ??= 14;
        $implemented = ($outcome->marketingTask?->updated_at ?? $outcome->created_at ?? now())->copy()->startOfDay();
        $baselineEnd = $implemented->copy()->subDay();
        $baselineStart = $baselineEnd->copy()->subDays($days - 1);
        [$evaluationStart, $evaluationEnd] = $this->determineEvaluationPeriod($implemented, $days);

        $outcome->update([
            'baseline_start' => $baselineStart,
            'baseline_end' => $baselineEnd,
            'evaluation_start' => $evaluationStart,
            'evaluation_end' => $evaluationEnd,
            'baseline_metrics' => $this->metrics($outcome, $baselineStart, $baselineEnd),
            'status' => 'waiting',
            'evaluated_at' => null,
        ]);

        return $outcome->fresh();
    }

    public function determineEvaluationPeriod(CarbonInterface $implementedAt, int $days = 14): array
    {
        $start = $implementedAt->copy()->addDay()->startOfDay();
        return [$start, $start->copy()->addDays(max(1, $days) - 1)];
    }

    public function evaluateDueOutcomes(): Collection
    {
        $due = ActionOutcome::whereIn('status', ['waiting', 'baseline_captured', 'failed'])->whereDate('evaluation_end', '<=', today())->get();

        return $due->map(function (ActionOutcome $outcome): ActionOutcome {
            $lock = Cache::lock('action-outcome:workspace:'.$outcome->website_id, 120);
            if (! $lock->get()) return $outcome;
            try {
                return $this->evaluate($outcome);
            } catch (Throwable $exception) {
                $error = $this->safeError($exception->getMessage());
                $outcome->update(['status' => 'failed', 'outcome_summary' => 'Outcome evaluation failed safely. '.$error]);
                Log::error('Action outcome evaluation failed.', ['workspace' => $outcome->website_id, 'action_outcome' => $outcome->id, 'status' => 'failed', 'error_summary' => str($error)->limit(500)->toString()]);
                return $outcome->fresh();
            } finally {
                $lock->release();
            }
        });
    }

    public function evaluate(ActionOutcome $outcome): ActionOutcome
    {
        $outcome->loadMissing(['website', 'agentAction', 'marketingTask']);
        if (! $outcome->evaluation_end || $outcome->evaluation_end->isFuture()) {
            $outcome->update(['status' => 'waiting', 'outcome_summary' => 'The equivalent post-action period is not complete yet.']);
            return $outcome->fresh();
        }

        $outcome->update(['status' => 'evaluating']);
        $evaluation = $this->metrics($outcome, $outcome->evaluation_start, $outcome->evaluation_end);
        $changes = $this->calculateMetricChanges($outcome->baseline_metrics ?? [], $evaluation);
        [$status, $confidence] = $this->determineOutcomeStatus($outcome->baseline_metrics ?? [], $evaluation, $changes);
        $outcome->update([
            'evaluation_metrics' => $evaluation,
            'metric_changes' => $changes,
            'status' => $status,
            'confidence' => $confidence,
            'outcome_summary' => $this->generateOutcomeSummary($status, $confidence),
            'evaluated_at' => now(),
        ]);

        return $outcome->fresh();
    }

    public function calculateMetricChanges(array $baseline, array $evaluation): array
    {
        $changes = [];
        foreach (array_unique([...array_keys($baseline), ...array_keys($evaluation)]) as $key) {
            if (! is_numeric($baseline[$key] ?? null) || ! is_numeric($evaluation[$key] ?? null)) continue;
            $before = (float) $baseline[$key];
            $after = (float) $evaluation[$key];
            $absolute = round($after - $before, 4);
            $changes[$key] = ['absolute' => $absolute, 'percentage' => $before == 0.0 ? null : round(($absolute / abs($before)) * 100, 2)];
        }
        return $changes;
    }

    public function determineOutcomeStatus(array $baseline, array $evaluation, array $changes): array
    {
        $volume = (int) ($baseline['impressions'] ?? 0) + (int) ($evaluation['impressions'] ?? 0);
        $conversionVolume = (int) ($baseline['appointment_actions'] ?? $baseline['signups'] ?? 0) + (int) ($evaluation['appointment_actions'] ?? $evaluation['signups'] ?? 0);
        if ($volume < 20 && $conversionVolume < 3) return ['inconclusive', 'low'];

        $scores = [];
        foreach (['clicks', 'ctr', 'appointment_actions', 'appointment_action_rate', 'signups', 'trial_starts', 'subscriptions'] as $metric) {
            $change = $changes[$metric]['percentage'] ?? null;
            if ($change !== null) $scores[] = $change > 5 ? 1 : ($change < -5 ? -1 : 0);
        }
        $position = $changes['average_position']['percentage'] ?? null;
        if ($position !== null) $scores[] = $position < -5 ? 1 : ($position > 5 ? -1 : 0);
        $score = array_sum($scores);
        $status = $score > 0 ? 'improved' : ($score < 0 ? 'declined' : 'no_change');
        $confidence = ($volume >= 200 || $conversionVolume >= 10) ? 'high' : 'medium';
        return [$status, $confidence];
    }

    public function generateOutcomeSummary(string $status, string $confidence): string
    {
        return match ($status) {
            'improved' => 'Performance improved after this action. The available data suggests a positive result; it does not prove sole causation. Confidence: '.$confidence.'.',
            'declined' => 'Relevant performance declined after this action. Other changes and external factors may also have contributed. Confidence: '.$confidence.'.',
            'no_change' => 'No clear measurable change was detected across equivalent periods. Confidence: '.$confidence.'.',
            default => 'The result is inconclusive because data volume or relevant conversion coverage is low.',
        };
    }

    private function metrics(ActionOutcome $outcome, CarbonInterface $start, CarbonInterface $end): array
    {
        $action = $outcome->agentAction;
        $search = $this->searchMetrics($outcome, $start, $end);
        $goal = $outcome->website->primary_conversion_goal ?: 'custom';
        if (str_contains($goal, 'saas') || in_array($goal, ['trial_activation', 'paid_subscription', 'loyalty_retention'], true)) {
            return $search + ['signups' => 0, 'trial_starts' => 0, 'trial_activation' => 0, 'onboarding_completion' => 0, 'subscriptions' => 0, 'trial_to_paid_conversion' => 0, 'product_data_connected' => false];
        }

        $events = ConversionEvent::where('website_id', $outcome->website_id)->whereBetween('occurred_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()]);
        if ($action->related_page_url) $events->where('page_url', 'like', '%'.trim((string) parse_url($action->related_page_url, PHP_URL_PATH), '/').'%');
        $counts = $events->selectRaw('event_type, COUNT(*) as aggregate')->groupBy('event_type')->pluck('aggregate', 'event_type');
        $booking = (int) ($counts['booking_click'] ?? 0) + (int) ($counts['book_appointment'] ?? 0);
        $external = (int) ($counts['booking_platform_click'] ?? 0);
        $phone = (int) ($counts['phone_click'] ?? 0);
        $email = (int) ($counts['email_click'] ?? 0);
        $forms = (int) ($counts['form_submit'] ?? 0) + (int) ($counts['contact_form_submit'] ?? 0);
        $actions = $booking + $external + $phone + $email + $forms;

        return $search + ['booking_clicks' => $booking, 'external_booking_clicks' => $external, 'phone_clicks' => $phone, 'email_clicks' => $email, 'contact_form_submissions' => $forms, 'appointment_actions' => $actions, 'appointment_action_rate' => ($search['clicks'] ?? 0) > 0 ? round(($actions / $search['clicks']) * 100, 2) : 0, 'conversion_events_available' => $counts->isNotEmpty()];
    }

    private function searchMetrics(ActionOutcome $outcome, CarbonInterface $start, CarbonInterface $end): array
    {
        $action = $outcome->agentAction;
        if ($action->related_page_url) {
            $rows = GscPage::where('website_id', $outcome->website_id)->where('page_url', $action->related_page_url)->whereDate('date_start', $start)->whereDate('date_end', $end)->get();
        } elseif ($action->related_query) {
            $rows = GscQuery::where('website_id', $outcome->website_id)->where('query', $action->related_query)->whereDate('date_start', $start)->whereDate('date_end', $end)->get();
        } else {
            $rows = GscDailyMetric::where('website_id', $outcome->website_id)->whereBetween('date', [$start, $end])->get();
        }
        $clicks = (int) $rows->sum('clicks');
        $impressions = (int) $rows->sum('impressions');
        $positionWeight = max(1, $impressions);
        $position = $rows->sum(fn ($row) => (float) $row->position * max(1, (int) $row->impressions)) / $positionWeight;
        return ['clicks' => $clicks, 'impressions' => $impressions, 'ctr' => $impressions > 0 ? round($clicks / $impressions, 4) : 0, 'average_position' => round($position, 2)];
    }

    private function evaluationType(AgentAction $action): string
    {
        return $action->related_page_url ? 'page' : ($action->related_query ? 'query' : 'conversion_goal');
    }

    private function safeError(string $message): string
    {
        return preg_replace('/(Bearer\s+)[^\s]+|(api[_-]?key|token|authorization|password|secret)\s*[:=]\s*[^\s,;]+/i', '[redacted]', $message) ?: 'Evaluation failed.';
    }
}
