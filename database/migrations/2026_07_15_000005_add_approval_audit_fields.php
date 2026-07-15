<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_actions', function (Blueprint $table): void {
            $table->enum('status', ['pending', 'reviewed', 'approved', 'completed', 'ignored', 'revision_requested'])->default('pending')->change();
            $table->foreignId('original_action_id')->nullable()->after('created_task_id')->constrained('agent_actions')->nullOnDelete();
            $this->auditColumns($table);
        });

        Schema::table('marketing_tasks', function (Blueprint $table): void {
            $table->enum('approval_status', ['pending', 'approved', 'ignored', 'revision_requested'])->nullable()->after('status');
            $this->auditColumns($table);
        });

        Schema::table('weekly_marketing_plans', function (Blueprint $table): void {
            $table->enum('status', ['draft', 'approved', 'in_progress', 'completed', 'revision_requested', 'ignored'])->default('draft')->change();
            $this->auditColumns($table);
        });

        Schema::table('agent_handoffs', function (Blueprint $table): void {
            $this->auditColumns($table);
        });

        Schema::table('agent_runs', function (Blueprint $table): void {
            $table->enum('status', ['pending', 'running', 'completed', 'failed', 'cancelled'])->default('pending')->change();
        });

        DB::table('marketing_tasks')->whereIn('source_type', ['agent_action', 'weekly_marketing_plan'])->whereNull('approval_status')->update(['approval_status' => 'pending']);
    }

    public function down(): void
    {
        DB::table('agent_actions')->where('status', 'revision_requested')->update(['status' => 'reviewed']);
        DB::table('weekly_marketing_plans')->whereIn('status', ['revision_requested', 'ignored'])->update(['status' => 'draft']);
        DB::table('agent_runs')->where('status', 'cancelled')->update(['status' => 'failed']);

        Schema::table('agent_handoffs', fn (Blueprint $table) => $this->dropAuditColumns($table));
        Schema::table('weekly_marketing_plans', function (Blueprint $table): void {
            $this->dropAuditColumns($table);
            $table->enum('status', ['draft', 'approved', 'in_progress', 'completed'])->default('draft')->change();
        });
        Schema::table('marketing_tasks', function (Blueprint $table): void {
            $this->dropAuditColumns($table);
            $table->dropColumn('approval_status');
        });
        Schema::table('agent_actions', function (Blueprint $table): void {
            $this->dropAuditColumns($table);
            $table->dropConstrainedForeignId('original_action_id');
            $table->enum('status', ['pending', 'reviewed', 'approved', 'completed', 'ignored'])->default('pending')->change();
        });
        Schema::table('agent_runs', fn (Blueprint $table) => $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending')->change());
    }

    private function auditColumns(Blueprint $table): void
    {
        $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamp('reviewed_at')->nullable();
        $table->text('review_notes')->nullable();
        $table->timestamp('revision_requested_at')->nullable();
        $table->text('revision_reason')->nullable();
    }

    private function dropAuditColumns(Blueprint $table): void
    {
        $table->dropConstrainedForeignId('reviewed_by');
        $table->dropColumn(['reviewed_at', 'review_notes', 'revision_requested_at', 'revision_reason']);
    }
};
