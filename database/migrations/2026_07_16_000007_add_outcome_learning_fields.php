<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_memories', function (Blueprint $table): void {
            $table->boolean('enabled')->default(true)->after('confidence');
            $table->json('learning_metadata')->nullable()->after('source_id');
            $table->text('review_notes')->nullable()->after('learning_metadata');
            $table->timestamp('last_used_at')->nullable()->after('review_notes');
            $table->index(['website_id', 'memory_type', 'enabled'], 'agent_memories_learning_lookup');
        });

        Schema::table('agent_actions', function (Blueprint $table): void {
            $table->decimal('confidence_score', 5, 4)->nullable()->after('priority');
            $table->decimal('learning_score', 7, 3)->default(0)->after('confidence_score');
            $table->string('evidence_strength', 20)->nullable()->after('learning_score');
            $table->unsignedInteger('similar_success_count')->default(0)->after('evidence_strength');
            $table->unsignedInteger('similar_failure_count')->default(0)->after('similar_success_count');
            $table->text('learning_summary')->nullable()->after('similar_failure_count');
        });
    }

    public function down(): void
    {
        Schema::table('agent_actions', function (Blueprint $table): void {
            $table->dropColumn(['confidence_score', 'learning_score', 'evidence_strength', 'similar_success_count', 'similar_failure_count', 'learning_summary']);
        });
        Schema::table('agent_memories', function (Blueprint $table): void {
            $table->dropIndex('agent_memories_learning_lookup');
            $table->dropColumn(['enabled', 'learning_metadata', 'review_notes', 'last_used_at']);
        });
    }
};
