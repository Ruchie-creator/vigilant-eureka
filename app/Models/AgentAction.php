<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentAction extends Model
{
    protected $fillable = ['agent_run_id', 'website_id', 'action_type', 'title', 'description', 'priority', 'status', 'related_page_url', 'related_query', 'expected_result', 'created_task_id', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
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
}
