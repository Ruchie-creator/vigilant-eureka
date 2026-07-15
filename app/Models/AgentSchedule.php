<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentSchedule extends Model
{
    protected $fillable = ['website_id', 'agent_id', 'schedule_type', 'frequency', 'timezone', 'run_at', 'enabled', 'settings', 'last_run_at', 'next_run_at', 'last_status'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'settings' => 'array', 'last_run_at' => 'datetime', 'next_run_at' => 'datetime'];
    }

    public function website(): BelongsTo { return $this->belongsTo(Website::class); }
    public function agent(): BelongsTo { return $this->belongsTo(Agent::class); }
}
