<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('websites', function (Blueprint $table): void {
            $table->string('timezone', 80)->nullable()->after('language');
        });

        Schema::create('agent_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained()->nullOnDelete();
            $table->string('schedule_type', 60);
            $table->string('frequency', 30);
            $table->string('timezone', 80)->nullable();
            $table->time('run_at')->nullable();
            $table->boolean('enabled')->default(true);
            $table->json('settings')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->string('last_status', 30)->nullable();
            $table->timestamps();
            $table->index(['enabled', 'next_run_at']);
            $table->index(['website_id', 'schedule_type', 'frequency']);
        });

        Schema::create('weekly_marketing_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_run_id')->nullable()->constrained()->nullOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('primary_goal', 80);
            $table->enum('status', ['draft', 'approved', 'in_progress', 'completed'])->default('draft');
            $table->longText('executive_summary')->nullable();
            $table->json('performance_summary')->nullable();
            $table->json('top_priorities')->nullable();
            $table->json('agent_contributions')->nullable();
            $table->json('expected_results')->nullable();
            $table->json('unresolved_actions')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['website_id', 'period_start', 'period_end'], 'weekly_plans_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_marketing_plans');
        Schema::dropIfExists('agent_schedules');
        Schema::table('websites', fn (Blueprint $table) => $table->dropColumn('timezone'));
    }
};
