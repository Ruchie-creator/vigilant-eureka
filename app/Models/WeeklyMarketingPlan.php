<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyMarketingPlan extends Model
{
    protected $fillable = ['website_id', 'agent_run_id', 'period_start', 'period_end', 'primary_goal', 'status', 'executive_summary', 'performance_summary', 'top_priorities', 'agent_contributions', 'expected_results', 'unresolved_actions', 'approved_at', 'completed_at'];

    protected function casts(): array
    {
        return ['period_start' => 'date', 'period_end' => 'date', 'performance_summary' => 'array', 'top_priorities' => 'array', 'agent_contributions' => 'array', 'expected_results' => 'array', 'unresolved_actions' => 'array', 'approved_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function website(): BelongsTo { return $this->belongsTo(Website::class); }
    public function agentRun(): BelongsTo { return $this->belongsTo(AgentRun::class); }
}
