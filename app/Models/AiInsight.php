<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiInsight extends Model
{
    protected $fillable = [
        'website_id',
        'audit_id',
        'title',
        'summary',
        'why_it_matters',
        'priority',
        'category',
        'recommendation',
        'expected_result',
        'suggested_task',
        'data_used',
        'status',
        'source',
        'insight_key',
        'data_period',
        'property_url',
        'affected_source_type',
        'affected_source_value',
    ];

    protected function casts(): array
    {
        return [
            'data_used' => 'array',
        ];
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function audit(): BelongsTo
    {
        return $this->belongsTo(SeoAudit::class, 'audit_id');
    }

    public function marketingTasks(): HasMany
    {
        return $this->hasMany(MarketingTask::class);
    }
}
