<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_runs', function (Blueprint $table): void {
            $table->string('trigger_type', 40)->default('manual')->after('run_type');
            $table->uuid('correlation_id')->nullable()->after('trigger_type')->index();
            $table->foreignId('parent_run_id')->nullable()->after('correlation_id')->constrained('agent_runs')->nullOnDelete();
            $table->unsignedSmallInteger('retry_count')->default(0)->after('parent_run_id');
            $table->unsignedInteger('duration_ms')->nullable()->after('retry_count');
            $table->char('input_hash', 64)->nullable()->after('duration_ms');
            $table->char('output_hash', 64)->nullable()->after('input_hash');
        });
    }

    public function down(): void
    {
        Schema::table('agent_runs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('parent_run_id');
            $table->dropIndex(['correlation_id']);
            $table->dropColumn(['trigger_type', 'correlation_id', 'retry_count', 'duration_ms', 'input_hash', 'output_hash']);
        });
    }
};
