<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MarketingTask extends Model
{
    protected $fillable = [
        'website_id',
        'ai_insight_id',
        'growth_opportunity_id',
        'title',
        'description',
        'expected_result',
        'priority',
        'source_type',
        'source_value',
        'related_page_url',
        'status',
        'approval_status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'revision_requested_at',
        'revision_reason',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date', 'reviewed_at' => 'datetime', 'revision_requested_at' => 'datetime',
        ];
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function aiInsight(): BelongsTo
    {
        return $this->belongsTo(AiInsight::class);
    }

    public function growthOpportunity(): BelongsTo
    {
        return $this->belongsTo(GrowthOpportunity::class);
    }

    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function agentAction(): HasOne { return $this->hasOne(AgentAction::class, 'created_task_id'); }
    public function outcome(): HasOne { return $this->hasOne(ActionOutcome::class); }
}
