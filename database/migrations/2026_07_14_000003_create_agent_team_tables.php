<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('role');
            $table->text('goal');
            $table->longText('instructions')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('agent_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('website_id')->nullable()->constrained()->nullOnDelete();
            $table->string('run_type');
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->longText('input_summary')->nullable();
            $table->longText('output_summary')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->index(['website_id', 'status', 'created_at']);
        });

        Schema::create('agent_actions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('agent_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('website_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action_type');
            $table->string('title');
            $table->longText('description');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['pending', 'reviewed', 'approved', 'completed', 'ignored'])->default('pending');
            $table->string('related_page_url', 2048)->nullable();
            $table->string('related_query', 1024)->nullable();
            $table->text('expected_result')->nullable();
            $table->foreignId('created_task_id')->nullable()->constrained('marketing_tasks')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['website_id', 'status', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_actions');
        Schema::dropIfExists('agent_runs');
        Schema::dropIfExists('agents');
    }
};
