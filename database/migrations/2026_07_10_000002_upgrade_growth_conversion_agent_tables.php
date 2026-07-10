<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('growth_opportunities', function (Blueprint $table): void {
            if (! Schema::hasColumn('growth_opportunities', 'score')) {
                $table->unsignedSmallInteger('score')->default(0)->after('source_hash');
            }
            if (! Schema::hasColumn('growth_opportunities', 'intent')) {
                $table->enum('intent', ['patient_intent', 'local_service', 'informational', 'branded', 'competitor', 'irrelevant', 'unknown'])->default('unknown')->after('score');
            }
            if (! Schema::hasColumn('growth_opportunities', 'related_page_url')) {
                $table->string('related_page_url', 512)->nullable()->after('intent');
            }
            if (! Schema::hasColumn('growth_opportunities', 'conversion_action')) {
                $table->string('conversion_action')->nullable()->after('related_page_url');
            }
            if (! Schema::hasColumn('growth_opportunities', 'ai_summary')) {
                $table->text('ai_summary')->nullable()->after('conversion_action');
            }
        });

        Schema::table('marketing_tasks', function (Blueprint $table): void {
            if (! Schema::hasColumn('marketing_tasks', 'growth_opportunity_id')) {
                $table->foreignId('growth_opportunity_id')->nullable()->after('ai_insight_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('marketing_tasks', 'source_type')) {
                $table->string('source_type')->nullable()->after('priority');
            }
            if (! Schema::hasColumn('marketing_tasks', 'source_value')) {
                $table->string('source_value', 512)->nullable()->after('source_type');
            }
            if (! Schema::hasColumn('marketing_tasks', 'related_page_url')) {
                $table->string('related_page_url', 512)->nullable()->after('source_value');
            }
            if (! Schema::hasColumn('marketing_tasks', 'expected_result')) {
                $table->text('expected_result')->nullable()->after('description');
            }
        });

        Schema::create('conversion_checks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->string('page_url', 512)->nullable();
            $table->string('item');
            $table->char('check_hash', 64);
            $table->enum('status', ['missing', 'partial', 'done', 'not_applicable'])->default('missing');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->text('recommendation');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['website_id', 'check_hash'], 'conversion_checks_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversion_checks');

        Schema::table('marketing_tasks', function (Blueprint $table): void {
            foreach (['growth_opportunity_id', 'source_type', 'source_value', 'related_page_url', 'expected_result'] as $column) {
                if (Schema::hasColumn('marketing_tasks', $column)) {
                    if ($column === 'growth_opportunity_id') {
                        $table->dropConstrainedForeignId($column);
                    } else {
                        $table->dropColumn($column);
                    }
                }
            }
        });

        Schema::table('growth_opportunities', function (Blueprint $table): void {
            foreach (['score', 'intent', 'related_page_url', 'conversion_action', 'ai_summary'] as $column) {
                if (Schema::hasColumn('growth_opportunities', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
