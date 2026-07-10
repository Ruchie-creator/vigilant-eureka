<?php

namespace App\Http\Controllers;

use App\Models\WeeklyReport;
use App\Services\WeeklyReportGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WeeklyReportController extends Controller
{
    public function index(): View
    {
        return view('weekly-reports.index', [
            'reports' => WeeklyReport::latest('week_end')->paginate(12),
        ]);
    }

    public function store(WeeklyReportGenerator $generator): RedirectResponse
    {
        $report = $generator->generate(now()->startOfWeek(), now()->endOfWeek());

        return redirect()->route('weekly-reports.show', $report)->with('success', 'Weekly report generated.');
    }

    public function show(WeeklyReport $weeklyReport): View
    {
        return view('weekly-reports.show', compact('weeklyReport'));
    }
}
