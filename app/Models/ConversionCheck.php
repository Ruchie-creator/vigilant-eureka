<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversionCheck extends Model
{
    protected $fillable = [
        'website_id',
        'page_url',
        'item',
        'check_hash',
        'status',
        'priority',
        'recommendation',
        'notes',
    ];

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }
}
