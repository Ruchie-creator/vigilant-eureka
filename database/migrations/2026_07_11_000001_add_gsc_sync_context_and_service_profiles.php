<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('websites', function (Blueprint $table): void {
            if (! Schema::hasColumn('websites', 'primary_services')) {
                $table->json('primary_services')->nullable()->after('target_location');
            }
            if (! Schema::hasColumn('websites', 'target_locations')) {
                $table->json('target_locations')->nullable()->after('primary_services');
            }
            if (! Schema::hasColumn('websites', 'practitioner_names')) {
                $table->json('practitioner_names')->nullable()->after('target_locations');
            }
            if (! Schema::hasColumn('websites', 'brand_terms')) {
                $table->json('brand_terms')->nullable()->after('practitioner_names');
            }
            if (! Schema::hasColumn('websites', 'priority_pages')) {
                $table->json('priority_pages')->nullable()->after('brand_terms');
            }
        });

        Schema::create('gsc_syncs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('search_console_site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('property_url', 512);
            $table->date('date_start');
            $table->date('date_end');
            $table->string('search_type', 40)->default('web');
            $table->string('country_filter', 12)->nullable();
            $table->string('device_filter', 40)->nullable();
            $table->timestamp('synced_at');
            $table->unsignedInteger('total_clicks')->default(0);
            $table->unsignedInteger('total_impressions')->default(0);
            $table->decimal('average_ctr', 8, 4)->default(0);
            $table->decimal('average_position', 8, 2)->default(0);
            $table->timestamps();
            $table->index(['website_id', 'date_start', 'date_end']);
        });

        Schema::table('growth_opportunities', function (Blueprint $table): void {
            if (! Schema::hasColumn('growth_opportunities', 'opportunity_category')) {
                $table->string('opportunity_category', 80)->default('acquisition_growth')->after('opportunity_type');
            }
        });

        if (Schema::hasColumn('growth_opportunities', 'intent')) {
            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE growth_opportunities MODIFY intent ENUM('service_intent','local_service_intent','condition_intent','branded_practitioner','review_reputation','informational','competitor','irrelevant','unknown','patient_intent','local_service','branded') DEFAULT 'unknown'");
            }
        }
    }

    public function down(): void
    {
        Schema::table('growth_opportunities', function (Blueprint $table): void {
            if (Schema::hasColumn('growth_opportunities', 'opportunity_category')) {
                $table->dropColumn('opportunity_category');
            }
        });

        Schema::dropIfExists('gsc_syncs');

        Schema::table('websites', function (Blueprint $table): void {
            foreach (['priority_pages', 'brand_terms', 'practitioner_names', 'target_locations', 'primary_services'] as $column) {
                if (Schema::hasColumn('websites', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
