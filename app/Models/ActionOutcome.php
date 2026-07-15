<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActionOutcome extends Model
{
    protected $fillable = ['website_id', 'agent_action_id', 'marketing_task_id', 'evaluation_type', 'status', 'baseline_start', 'baseline_end', 'evaluation_start', 'evaluation_end', 'baseline_metrics', 'evaluation_metrics', 'metric_changes', 'outcome_summary', 'confidence', 'review_notes', 'evaluated_at'];

    protected function casts(): array
    {
        return ['baseline_start' => 'date', 'baseline_end' => 'date', 'evaluation_start' => 'date', 'evaluation_end' => 'date', 'baseline_metrics' => 'array', 'evaluation_metrics' => 'array', 'metric_changes' => 'array', 'evaluated_at' => 'datetime'];
    }

    public function website(): BelongsTo { return $this->belongsTo(Website::class); }
    public function agentAction(): BelongsTo { return $this->belongsTo(AgentAction::class); }
    public function marketingTask(): BelongsTo { return $this->belongsTo(MarketingTask::class); }
}
