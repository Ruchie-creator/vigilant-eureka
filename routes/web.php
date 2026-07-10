<?php

use App\Http\Controllers\AiInsightController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MarketingTaskController;
use App\Http\Controllers\SeoAuditController;
use App\Http\Controllers\SettingsController;
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

    Route::get('/seo-audits', [SeoAuditController::class, 'index'])->name('seo-audits.index');

    Route::get('/ai-insights', [AiInsightController::class, 'index'])->name('ai-insights.index');
    Route::post('/websites/{website}/ai-insights', [AiInsightController::class, 'store'])->name('websites.ai-insights.store');
    Route::patch('/ai-insights/{aiInsight}', [AiInsightController::class, 'update'])->name('ai-insights.update');
    Route::post('/ai-insights/{aiInsight}/tasks', [MarketingTaskController::class, 'storeFromInsight'])->name('ai-insights.tasks.store');

    Route::resource('marketing-tasks', MarketingTaskController::class)->except(['show']);

    Route::get('/weekly-reports', [WeeklyReportController::class, 'index'])->name('weekly-reports.index');
    Route::post('/weekly-reports/generate', [WeeklyReportController::class, 'store'])->name('weekly-reports.store');
    Route::get('/weekly-reports/{weeklyReport}', [WeeklyReportController::class, 'show'])->name('weekly-reports.show');

    Route::get('/settings', SettingsController::class)->name('settings');
});
