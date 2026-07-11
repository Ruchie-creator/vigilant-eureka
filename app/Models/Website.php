<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Website extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'type',
        'language',
        'target_location',
        'primary_services',
        'target_locations',
        'practitioner_names',
        'brand_terms',
        'priority_pages',
        'status',
        'search_console_site_id',
        'gsc_last_synced_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'gsc_last_synced_at' => 'datetime',
            'primary_services' => 'array',
            'target_locations' => 'array',
            'practitioner_names' => 'array',
            'brand_terms' => 'array',
            'priority_pages' => 'array',
        ];
    }

    public function seoAudits(): HasMany
    {
        return $this->hasMany(SeoAudit::class);
    }

    public function aiInsights(): HasMany
    {
        return $this->hasMany(AiInsight::class);
    }

    public function marketingTasks(): HasMany
    {
        return $this->hasMany(MarketingTask::class);
    }

    public function searchConsoleSite(): BelongsTo
    {
        return $this->belongsTo(SearchConsoleSite::class);
    }

    public function gscDailyMetrics(): HasMany
    {
        return $this->hasMany(GscDailyMetric::class);
    }

    public function gscQueries(): HasMany
    {
        return $this->hasMany(GscQuery::class);
    }

    public function gscPages(): HasMany
    {
        return $this->hasMany(GscPage::class);
    }

    public function gscDevices(): HasMany
    {
        return $this->hasMany(GscDevice::class);
    }

    public function gscCountries(): HasMany
    {
        return $this->hasMany(GscCountry::class);
    }

    public function gscSyncs(): HasMany
    {
        return $this->hasMany(GscSync::class);
    }

    public function latestGscSync(): HasOne
    {
        return $this->hasOne(GscSync::class)->latestOfMany('synced_at');
    }

    public function growthOpportunities(): HasMany
    {
        return $this->hasMany(GrowthOpportunity::class);
    }

    public function conversionChecks(): HasMany
    {
        return $this->hasMany(ConversionCheck::class);
    }

    public function serviceProfile(): array
    {
        $defaults = $this->defaultServiceProfile();

        return [
            'primary_services' => $this->profileList('primary_services', $defaults['primary_services']),
            'target_locations' => $this->profileList('target_locations', array_values(array_filter([$this->target_location, ...$defaults['target_locations']]))),
            'practitioner_names' => $this->profileList('practitioner_names', $defaults['practitioner_names']),
            'brand_terms' => $this->profileList('brand_terms', $defaults['brand_terms']),
            'priority_pages' => $this->profileList('priority_pages', $defaults['priority_pages']),
        ];
    }

    private function profileList(string $key, array $fallback = []): array
    {
        $values = $this->{$key};
        $values = is_array($values) && $values !== [] ? $values : $fallback;

        return collect($values)
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => trim((string) $value))
            ->unique()
            ->values()
            ->all();
    }

    private function defaultServiceProfile(): array
    {
        $url = strtolower($this->url);

        if ($this->type === 'sexology' || str_contains($url, 'sexology-geneve')) {
            return [
                'primary_services' => ['sexologie', 'sexologue', 'consultation sexologie', 'troubles du desir', 'baisse desir sexuel', 'ejaculation precoce', 'troubles orgasme', 'douleurs rapports sexuels', 'therapie couple', 'sante sexuelle'],
                'target_locations' => ['Geneve', 'Suisse'],
                'practitioner_names' => ['Beatrice Cuzin', 'Beatrice Cuzin', 'Dr Cuzin', 'Docteur Cuzin'],
                'brand_terms' => ['sexology geneve', 'sexology-geneve'],
                'priority_pages' => [
                    'https://sexology-geneve.ch/traitements-sexologie-geneve/',
                    'https://sexology-geneve.ch/ejaculation-precoce-geneve/',
                    'https://sexology-geneve.ch/baisse-desir-sexuel-geneve/',
                    'https://sexology-geneve.ch/troubles-orgasme-geneve/',
                ],
            ];
        }

        if ($this->type === 'osteopathy' || str_contains($url, 'tbweiss-osteo-lyon')) {
            return [
                'primary_services' => ['osteopathe', 'osteopathie', 'osteopathie cranienne', 'therapie cranio-sacrale', 'drainage lymphatique', 'osteopathie du sport', 'osteopathie musicien', 'osteopathie danseur', 'troubles digestifs'],
                'target_locations' => ['Lyon', 'Lyon 2', 'France'],
                'practitioner_names' => ['Thomas Baptiste Weiss', 'Thomas Weiss', 'Baptiste Weiss'],
                'brand_terms' => ['tbweiss', 'tbweiss osteo', 'thomas weiss osteopathe'],
                'priority_pages' => [
                    'https://tbweiss-osteo-lyon.com/therapie-cranio-sacrale/',
                    'https://tbweiss-osteo-lyon.com/drainage-lymphatique/',
                    'https://tbweiss-osteo-lyon.com/osteopathie-cranio-sacree/',
                    'https://tbweiss-osteo-lyon.com/osteopathie-du-sport/',
                    'https://tbweiss-osteo-lyon.com/osteopathie-lyon-2/',
                ],
            ];
        }

        return [
            'primary_services' => [],
            'target_locations' => [],
            'practitioner_names' => [],
            'brand_terms' => [],
            'priority_pages' => [],
        ];
    }
}
