<?php

use App\Http\Controllers\AiInsightController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoogleSearchConsoleController;
use App\Http\Controllers\GrowthOpportunityController;
use App\Http\Controllers\MarketingTaskController;
use App\Http\Controllers\SeoAuditController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\WebsiteSearchConsoleController;
use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\WeeklyReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', fn () => redirect()->route('login'));
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('websites', WebsiteController::class);
    Route::post('/websites/{website}/audit', [SeoAuditController::class, 'store'])->name('websites.audit');
    Route::post('/websites/{website}/search-console/site', [WebsiteSearchConsoleController::class, 'assign'])->name('websites.search-console.assign');
    Route::post('/websites/{website}/search-console/sync', [WebsiteSearchConsoleController::class, 'sync'])->name('websites.search-console.sync');
    Route::post('/websites/{website}/search-console/disconnect', [WebsiteSearchConsoleController::class, 'disconnect'])->name('websites.search-console.disconnect');

    Route::get('/google/search-console/connect', [GoogleSearchConsoleController::class, 'connect'])->name('google.search-console.connect');
    Route::get('/google/search-console/callback', [GoogleSearchConsoleController::class, 'callback'])->name('google.search-console.callback');
    Route::post('/google/search-console/disconnect', [GoogleSearchConsoleController::class, 'disconnect'])->name('google.search-console.disconnect');
    Route::get('/google/search-console/sites', [GoogleSearchConsoleController::class, 'sites'])->name('google.search-console.sites');

    Route::get('/seo-audits', [SeoAuditController::class, 'index'])->name('seo-audits.index');

    Route::get('/ai-insights', [AiInsightController::class, 'index'])->name('ai-insights.index');
    Route::post('/websites/{website}/ai-insights', [AiInsightController::class, 'store'])->name('websites.ai-insights.store');
    Route::patch('/ai-insights/{aiInsight}', [AiInsightController::class, 'update'])->name('ai-insights.update');
    Route::post('/ai-insights/{aiInsight}/tasks', [MarketingTaskController::class, 'storeFromInsight'])->name('ai-insights.tasks.store');
    Route::post('/growth-opportunities/{growthOpportunity}/tasks', [GrowthOpportunityController::class, 'storeTask'])->name('growth-opportunities.tasks.store');
    Route::patch('/growth-opportunities/{growthOpportunity}', [GrowthOpportunityController::class, 'update'])->name('growth-opportunities.update');

    Route::resource('marketing-tasks', MarketingTaskController::class)->except(['show']);

    Route::get('/weekly-reports', [WeeklyReportController::class, 'index'])->name('weekly-reports.index');
    Route::post('/weekly-reports/generate', [WeeklyReportController::class, 'store'])->name('weekly-reports.store');
    Route::get('/weekly-reports/{weeklyReport}', [WeeklyReportController::class, 'show'])->name('weekly-reports.show');

    Route::get('/settings', SettingsController::class)->name('settings');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
});
