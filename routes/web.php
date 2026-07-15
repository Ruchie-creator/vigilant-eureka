<?php

use App\Http\Controllers\AiInsightController;
use App\Http\Controllers\AgentActionController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\AgentRunController;
use App\Http\Controllers\AgentMemoryController;
use App\Http\Controllers\AgentHandoffController;
use App\Http\Controllers\AgentScheduleController;
use App\Http\Controllers\AgentOperationsController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ConversionEventController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoogleSearchConsoleController;
use App\Http\Controllers\GrowthOpportunityController;
use App\Http\Controllers\MarketingTaskController;
use App\Http\Controllers\SeoAuditController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\WebsiteSearchConsoleController;
use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\WeeklyReportController;
use App\Http\Controllers\WeeklyMarketingPlanController;
use Illuminate\Support\Facades\Route;

Route::get('/track/conversions/{trackingKey}/tracker.js', [ConversionEventController::class, 'script'])
    ->middleware('throttle:120,1')
    ->name('conversion-tracking.script');
Route::post('/track/conversions/{trackingKey}', [ConversionEventController::class, 'store'])
    ->middleware('throttle:120,1')
    ->name('conversion-events.store');

Route::middleware('guest')->group(function () {
    Route::get('/', fn () => redirect()->route('login'));
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    Route::patch('/approvals/{type}/{id}', [ApprovalController::class, 'update'])->name('approvals.update');
    Route::get('/agent-operations', [AgentOperationsController::class, 'index'])->name('agent-operations.index');
    Route::get('/agent-operations/runs/{agentRun}', [AgentOperationsController::class, 'show'])->name('agent-operations.runs.show');
    Route::post('/agent-operations/runs/{agentRun}/retry', [AgentOperationsController::class, 'retry'])->name('agent-operations.runs.retry');
    Route::patch('/agent-operations/runs/{agentRun}/cancel', [AgentOperationsController::class, 'cancel'])->name('agent-operations.runs.cancel');

    Route::get('/agents', [AgentController::class, 'index'])->name('agents.index');
    Route::get('/agents/{agent}', [AgentController::class, 'show'])->name('agents.show');
    Route::post('/agents/{agent}/run', [AgentRunController::class, 'store'])->name('agents.run');
    Route::get('/websites/{website}/agents', [AgentController::class, 'website'])->name('websites.agents.index');
    Route::post('/websites/{website}/agents/run-full-team', [AgentRunController::class, 'fullTeam'])->name('websites.agents.run-full-team');
    Route::patch('/agent-actions/{agentAction}', [AgentActionController::class, 'update'])->name('agent-actions.update');
    Route::post('/agent-actions/{agentAction}/tasks', [AgentActionController::class, 'storeTask'])->name('agent-actions.tasks.store');
    Route::get('/agents/{agent}/memories', [AgentMemoryController::class, 'index'])->name('agents.memories.index');
    Route::post('/agents/{agent}/memories', [AgentMemoryController::class, 'store'])->name('agents.memories.store');
    Route::delete('/agent-memories/{agentMemory}', [AgentMemoryController::class, 'destroy'])->name('agent-memories.destroy');
    Route::patch('/agent-memories/{agentMemory}/expire', [AgentMemoryController::class, 'expire'])->name('agent-memories.expire');
    Route::get('/agent-memories', [AgentMemoryController::class, 'all'])->name('agent-memories.index');
    Route::get('/agent-handoffs', [AgentHandoffController::class, 'index'])->name('agent-handoffs.index');
    Route::patch('/agent-handoffs/{agentHandoff}', [AgentHandoffController::class, 'update'])->name('agent-handoffs.update');
    Route::get('/agent-schedules', [AgentScheduleController::class, 'index'])->name('agent-schedules.index');
    Route::patch('/agent-schedules/{agentSchedule}', [AgentScheduleController::class, 'update'])->name('agent-schedules.update');
    Route::patch('/agent-schedules/{agentSchedule}/toggle', [AgentScheduleController::class, 'toggle'])->name('agent-schedules.toggle');
    Route::post('/agent-schedules/{agentSchedule}/run', [AgentScheduleController::class, 'run'])->name('agent-schedules.run');
    Route::post('/websites/{website}/agent-schedules/defaults', [AgentScheduleController::class, 'defaults'])->name('websites.agent-schedules.defaults');
    Route::get('/websites/{website}/weekly-marketing-plans', [WeeklyMarketingPlanController::class, 'index'])->name('websites.weekly-marketing-plans.index');
    Route::patch('/weekly-marketing-plans/{weeklyMarketingPlan}', [WeeklyMarketingPlanController::class, 'update'])->name('weekly-marketing-plans.update');
    Route::post('/weekly-marketing-plans/{weeklyMarketingPlan}/priorities/{priority}/task', [WeeklyMarketingPlanController::class, 'task'])->name('weekly-marketing-plans.priorities.task');

    Route::resource('websites', WebsiteController::class);
    Route::get('/websites/{website}/gsc-queries', [WebsiteController::class, 'gscQueries'])->name('websites.gsc-queries.index');
    Route::get('/websites/{website}/gsc-pages', [WebsiteController::class, 'gscPages'])->name('websites.gsc-pages.index');
    Route::get('/websites/{website}/gsc-countries', [WebsiteController::class, 'gscCountries'])->name('websites.gsc-countries.index');
    Route::get('/websites/{website}/gsc-devices', [WebsiteController::class, 'gscDevices'])->name('websites.gsc-devices.index');
    Route::get('/websites/{website}/growth-opportunities', [WebsiteController::class, 'growthOpportunities'])->name('websites.growth-opportunities.index');
    Route::post('/websites/{website}/audit', [SeoAuditController::class, 'store'])->name('websites.audit');
    Route::post('/websites/{website}/search-console/site', [WebsiteSearchConsoleController::class, 'assign'])->name('websites.search-console.assign');
    Route::post('/websites/{website}/search-console/sync', [WebsiteSearchConsoleController::class, 'sync'])->name('websites.search-console.sync');
    Route::post('/websites/{website}/search-console/reset', [WebsiteSearchConsoleController::class, 'reset'])->name('websites.search-console.reset');
    Route::post('/websites/{website}/search-console/disconnect', [WebsiteSearchConsoleController::class, 'disconnect'])->name('websites.search-console.disconnect');

    Route::get('/google/search-console/connect', [GoogleSearchConsoleController::class, 'connect'])->name('google.search-console.connect');
    Route::get('/google/search-console/callback', [GoogleSearchConsoleController::class, 'callback'])->name('google.search-console.callback');
    Route::post('/google/search-console/disconnect', [GoogleSearchConsoleController::class, 'disconnect'])->name('google.search-console.disconnect');
    Route::get('/google/search-console/sites', [GoogleSearchConsoleController::class, 'sites'])->name('google.search-console.sites');

    Route::get('/seo-audits', [SeoAuditController::class, 'index'])->name('seo-audits.index');

    Route::get('/ai-insights', [AiInsightController::class, 'index'])->name('ai-insights.index');
    Route::get('/websites/{website}/ai-insights', [AiInsightController::class, 'websiteIndex'])->name('websites.ai-insights.index');
    Route::post('/websites/{website}/ai-insights', [AiInsightController::class, 'store'])->name('websites.ai-insights.store');
    Route::patch('/ai-insights/{aiInsight}', [AiInsightController::class, 'update'])->name('ai-insights.update');
    Route::post('/ai-insights/{aiInsight}/tasks', [MarketingTaskController::class, 'storeFromInsight'])->name('ai-insights.tasks.store');
    Route::post('/growth-opportunities/{growthOpportunity}/tasks', [GrowthOpportunityController::class, 'storeTask'])->name('growth-opportunities.tasks.store');
    Route::patch('/growth-opportunities/{growthOpportunity}', [GrowthOpportunityController::class, 'update'])->name('growth-opportunities.update');

    Route::patch('/marketing-tasks/{marketingTask}/status', [MarketingTaskController::class, 'updateStatus'])->name('marketing-tasks.status.update');
    Route::resource('marketing-tasks', MarketingTaskController::class)->except(['show']);

    Route::get('/weekly-reports', [WeeklyReportController::class, 'index'])->name('weekly-reports.index');
    Route::post('/weekly-reports/generate', [WeeklyReportController::class, 'store'])->name('weekly-reports.store');
    Route::get('/weekly-reports/{weeklyReport}', [WeeklyReportController::class, 'show'])->name('weekly-reports.show');

    Route::get('/settings', SettingsController::class)->name('settings');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
});
