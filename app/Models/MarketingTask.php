<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingTask extends Model
{
    protected $fillable = [
        'website_id',
        'ai_insight_id',
        'growth_opportunity_id',
        'title',
        'description',
        'expected_result',
        'priority',
        'source_type',
        'source_value',
        'related_page_url',
        'status',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function aiInsight(): BelongsTo
    {
        return $this->belongsTo(AiInsight::class);
    }
}
