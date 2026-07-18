<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AgentAction extends Model
{
    protected $fillable = ['agent_run_id', 'website_id', 'action_type', 'title', 'description', 'priority', 'confidence_score', 'learning_score', 'evidence_strength', 'similar_success_count', 'similar_failure_count', 'learning_summary', 'status', 'related_page_url', 'related_query', 'expected_result', 'created_task_id', 'original_action_id', 'metadata', 'reviewed_by', 'reviewed_at', 'review_notes', 'revision_requested_at', 'revision_reason'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'confidence_score' => 'float', 'learning_score' => 'float', 'reviewed_at' => 'datetime', 'revision_requested_at' => 'datetime'];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(AgentRun::class, 'agent_run_id');
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function createdTask(): BelongsTo
    {
        return $this->belongsTo(MarketingTask::class, 'created_task_id');
    }

    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function originalAction(): BelongsTo { return $this->belongsTo(self::class, 'original_action_id'); }
    public function outcome(): HasOne { return $this->hasOne(ActionOutcome::class); }
}
