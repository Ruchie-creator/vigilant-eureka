<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentHandoff extends Model
{
    protected $fillable = ['website_id', 'agent_run_id', 'from_agent_id', 'to_agent_id', 'reason', 'context', 'expected_output', 'status', 'accepted_at', 'completed_at'];

    protected function casts(): array
    {
        return ['context' => 'array', 'accepted_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function website(): BelongsTo { return $this->belongsTo(Website::class); }
    public function run(): BelongsTo { return $this->belongsTo(AgentRun::class, 'agent_run_id'); }
    public function fromAgent(): BelongsTo { return $this->belongsTo(Agent::class, 'from_agent_id'); }
    public function toAgent(): BelongsTo { return $this->belongsTo(Agent::class, 'to_agent_id'); }
}
