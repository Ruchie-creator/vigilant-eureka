<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentRun extends Model
{
    protected $fillable = ['agent_id', 'website_id', 'run_type', 'status', 'input_summary', 'output_summary', 'metadata', 'started_at', 'completed_at', 'error_message'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
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
}
