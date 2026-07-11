<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GscCountry extends Model
{
    protected $fillable = ['website_id', 'search_console_site_id', 'country', 'clicks', 'impressions', 'ctr', 'position', 'date_start', 'date_end'];

    protected function casts(): array
    {
        return ['ctr' => 'float', 'position' => 'float', 'date_start' => 'date', 'date_end' => 'date'];
    }
}
