<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeoAudit extends Model
{
    protected $fillable = [
        'website_id',
        'http_status',
        'page_title',
        'meta_description',
        'h1',
        'canonical_url',
        'robots_meta',
        'og_title',
        'og_description',
        'sitemap_available',
        'robots_txt_available',
        'is_indexable',
        'missing_fields',
        'recommendations',
        'raw_error',
    ];

    protected function casts(): array
    {
        return [
            'sitemap_available' => 'boolean',
            'robots_txt_available' => 'boolean',
            'is_indexable' => 'boolean',
            'missing_fields' => 'array',
            'recommendations' => 'array',
        ];
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function aiInsights(): HasMany
    {
        return $this->hasMany(AiInsight::class, 'audit_id');
    }
}
