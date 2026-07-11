<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GscSync extends Model
{
    protected $fillable = [
        'website_id',
        'search_console_site_id',
        'property_url',
        'date_start',
        'date_end',
        'search_type',
        'country_filter',
        'device_filter',
        'synced_at',
        'total_clicks',
        'total_impressions',
        'average_ctr',
        'average_position',
        'rows_daily',
        'rows_queries',
        'rows_pages',
        'rows_devices',
        'rows_countries',
        'status',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'date_start' => 'date',
            'date_end' => 'date',
            'synced_at' => 'datetime',
            'average_ctr' => 'float',
            'average_position' => 'float',
        ];
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function searchConsoleSite(): BelongsTo
    {
        return $this->belongsTo(SearchConsoleSite::class);
    }
}
