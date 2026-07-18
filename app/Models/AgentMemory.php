<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentMemory extends Model
{
    protected $fillable = ['agent_id', 'website_id', 'memory_type', 'memory_key', 'memory_value', 'confidence', 'enabled', 'source_type', 'source_id', 'learning_metadata', 'review_notes', 'last_used_at', 'valid_from', 'expires_at'];

    protected function casts(): array
    {
        return ['confidence' => 'float', 'enabled' => 'boolean', 'learning_metadata' => 'array', 'last_used_at' => 'datetime', 'valid_from' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function agent(): BelongsTo { return $this->belongsTo(Agent::class); }
    public function website(): BelongsTo { return $this->belongsTo(Website::class); }
    public function outcome(): BelongsTo { return $this->belongsTo(ActionOutcome::class, 'source_id'); }
    public function getIsExpiredAttribute(): bool { return $this->expires_at?->isPast() ?? false; }
    public function getIsActiveAttribute(): bool { return $this->enabled && ! $this->is_expired; }
}
