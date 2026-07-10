<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrowthOpportunity extends Model
{
    protected $fillable = [
        'website_id',
        'source_type',
        'source_value',
        'source_hash',
        'clicks',
        'impressions',
        'ctr',
        'position',
        'opportunity_type',
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
        return ['ctr' => 'float', 'position' => 'float', 'date_start' => 'date', 'date_end' => 'date'];
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }
}
