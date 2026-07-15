<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GrowthOpportunity extends Model
{
    protected $fillable = [
        'website_id',
        'source_type',
        'source_value',
        'source_hash',
        'score',
        'intent',
        'related_page_url',
        'conversion_action',
        'ai_summary',
        'clicks',
        'impressions',
        'ctr',
        'position',
        'opportunity_type',
        'opportunity_category',
        'problem',
        'recommendation',
        'expected_result',
        'priority',
        'status',
        'date_start',
        'date_end',
    ];

    protected function casts(): array
    {
        return ['ctr' => 'float', 'position' => 'float', 'score' => 'integer', 'date_start' => 'date', 'date_end' => 'date'];
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function conversionEvents(): HasMany
    {
        return $this->hasMany(ConversionEvent::class);
    }
}
