<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversionEvent extends Model
{
    protected $fillable = [
        'website_id',
        'growth_opportunity_id',
        'event_uuid',
        'event_type',
        'action_label',
        'page_url',
        'target_url',
        'device_type',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function growthOpportunity(): BelongsTo
    {
        return $this->belongsTo(GrowthOpportunity::class);
    }
}
