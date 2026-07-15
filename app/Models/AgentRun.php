<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AgentRun extends Model
{
    protected $fillable = ['agent_id', 'website_id', 'run_type', 'trigger_type', 'correlation_id', 'parent_run_id', 'retry_count', 'duration_ms', 'input_hash', 'output_hash', 'status', 'input_summary', 'output_summary', 'metadata', 'started_at', 'completed_at', 'error_message'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'started_at' => 'datetime', 'completed_at' => 'datetime', 'retry_count' => 'integer', 'duration_ms' => 'integer'];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(AgentAction::class);
    }

    public function parentRun(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_run_id');
    }

    public function childRuns(): HasMany
    {
        return $this->hasMany(self::class, 'parent_run_id');
    }

    public function handoffs(): HasMany
    {
        return $this->hasMany(AgentHandoff::class);
    }

    public function weeklyMarketingPlan(): HasOne { return $this->hasOne(WeeklyMarketingPlan::class); }
}
