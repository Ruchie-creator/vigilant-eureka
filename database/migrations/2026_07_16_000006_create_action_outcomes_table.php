<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('action_outcomes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_action_id')->constrained()->cascadeOnDelete();
            $table->foreignId('marketing_task_id')->nullable()->constrained()->nullOnDelete();
            $table->string('evaluation_type', 50);
            $table->enum('status', ['pending_baseline', 'baseline_captured', 'waiting', 'evaluating', 'improved', 'no_change', 'declined', 'inconclusive', 'failed'])->default('pending_baseline');
            $table->date('baseline_start')->nullable();
            $table->date('baseline_end')->nullable();
            $table->date('evaluation_start')->nullable();
            $table->date('evaluation_end')->nullable();
            $table->json('baseline_metrics')->nullable();
            $table->json('evaluation_metrics')->nullable();
            $table->json('metric_changes')->nullable();
            $table->longText('outcome_summary')->nullable();
            $table->string('confidence', 20)->nullable();
            $table->longText('review_notes')->nullable();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();

            $table->unique(['agent_action_id', 'marketing_task_id'], 'action_outcomes_action_task_unique');
            $table->index(['website_id', 'status', 'evaluation_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_outcomes');
    }
};
