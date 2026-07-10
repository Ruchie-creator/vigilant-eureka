<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Website extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'type',
        'language',
        'target_location',
        'status',
        'search_console_site_id',
        'gsc_last_synced_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'gsc_last_synced_at' => 'datetime',
        ];
    }

    public function seoAudits(): HasMany
    {
        return $this->hasMany(SeoAudit::class);
    }

    public function aiInsights(): HasMany
    {
        return $this->hasMany(AiInsight::class);
    }

    public function marketingTasks(): HasMany
    {
        return $this->hasMany(MarketingTask::class);
    }

    public function searchConsoleSite(): BelongsTo
    {
        return $this->belongsTo(SearchConsoleSite::class);
    }

    public function gscDailyMetrics(): HasMany
    {
        return $this->hasMany(GscDailyMetric::class);
    }

    public function gscQueries(): HasMany
    {
        return $this->hasMany(GscQuery::class);
    }

    public function gscPages(): HasMany
    {
        return $this->hasMany(GscPage::class);
    }

    public function gscDevices(): HasMany
    {
        return $this->hasMany(GscDevice::class);
    }

    public function growthOpportunities(): HasMany
    {
        return $this->hasMany(GrowthOpportunity::class);
    }

    public function conversionChecks(): HasMany
    {
        return $this->hasMany(ConversionCheck::class);
    }
}
