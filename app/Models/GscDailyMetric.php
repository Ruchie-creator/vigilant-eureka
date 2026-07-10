<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GscDailyMetric extends Model
{
    protected $fillable = ['website_id', 'search_console_site_id', 'date', 'clicks', 'impressions', 'ctr', 'position'];

    protected function casts(): array
    {
        return ['date' => 'date', 'ctr' => 'float', 'position' => 'float'];
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }
}
