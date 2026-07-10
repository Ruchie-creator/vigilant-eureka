<?php

namespace App\Console\Commands;

use App\Models\WeeklyReport;
use App\Services\WeeklyReportGenerator;
use Illuminate\Console\Command;

class GenerateWeeklyReport extends Command
{
    protected $signature = 'reports:weekly';

    protected $description = 'Generate a simple weekly marketing report.';

    public function handle(WeeklyReportGenerator $generator): int
    {
        $report = $generator->generate(now()->startOfWeek(), now()->endOfWeek());
        $this->info('Generated report: '.$report->title);

        return self::SUCCESS;
    }
}
