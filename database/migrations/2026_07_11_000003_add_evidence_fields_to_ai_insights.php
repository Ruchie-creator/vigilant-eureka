<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_insights', function (Blueprint $table): void {
            if (! Schema::hasColumn('ai_insights', 'insight_key')) {
                $table->string('insight_key', 120)->nullable()->after('source');
            }
            if (! Schema::hasColumn('ai_insights', 'data_period')) {
                $table->string('data_period')->nullable()->after('insight_key');
            }
            if (! Schema::hasColumn('ai_insights', 'property_url')) {
                $table->string('property_url', 512)->nullable()->after('data_period');
            }
            if (! Schema::hasColumn('ai_insights', 'affected_source_type')) {
                $table->string('affected_source_type', 40)->nullable()->after('property_url');
            }
            if (! Schema::hasColumn('ai_insights', 'affected_source_value')) {
                $table->string('affected_source_value', 512)->nullable()->after('affected_source_type');
            }
            if (! Schema::hasColumn('ai_insights', 'why_it_matters')) {
                $table->text('why_it_matters')->nullable()->after('summary');
            }
            if (! Schema::hasColumn('ai_insights', 'suggested_task')) {
                $table->string('suggested_task', 512)->nullable()->after('expected_result');
            }
            if (! Schema::hasColumn('ai_insights', 'data_used')) {
                $table->json('data_used')->nullable()->after('suggested_task');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_insights', function (Blueprint $table): void {
            foreach (['data_used', 'suggested_task', 'why_it_matters', 'affected_source_value', 'affected_source_type', 'property_url', 'data_period', 'insight_key'] as $column) {
                if (Schema::hasColumn('ai_insights', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
