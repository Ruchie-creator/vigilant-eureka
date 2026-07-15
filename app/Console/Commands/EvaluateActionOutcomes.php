<?php

namespace App\Console\Commands;

use App\Services\Agents\ActionOutcomeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class EvaluateActionOutcomes extends Command
{
    protected $signature = 'agents:evaluate-outcomes';
    protected $description = 'Evaluate due marketing action outcomes against equivalent baseline periods.';

    public function handle(ActionOutcomeService $service): int
    {
        try {
            $evaluated = $service->evaluateDueOutcomes();
            $this->info($evaluated->count().' due outcome(s) processed.');
            return self::SUCCESS;
        } catch (Throwable $exception) {
            Log::error('Action outcome evaluation command failed safely.', ['status' => 'failed', 'error_summary' => str($exception->getMessage())->limit(500)->toString()]);
            $this->error('Outcome evaluation failed safely.');
            return self::FAILURE;
        }
    }
}
