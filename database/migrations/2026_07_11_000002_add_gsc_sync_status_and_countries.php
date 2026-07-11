<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gsc_syncs', function (Blueprint $table): void {
            if (! Schema::hasColumn('gsc_syncs', 'rows_daily')) {
                $table->unsignedInteger('rows_daily')->default(0)->after('average_position');
            }
            if (! Schema::hasColumn('gsc_syncs', 'rows_queries')) {
                $table->unsignedInteger('rows_queries')->default(0)->after('rows_daily');
            }
            if (! Schema::hasColumn('gsc_syncs', 'rows_pages')) {
                $table->unsignedInteger('rows_pages')->default(0)->after('rows_queries');
            }
            if (! Schema::hasColumn('gsc_syncs', 'rows_devices')) {
                $table->unsignedInteger('rows_devices')->default(0)->after('rows_pages');
            }
            if (! Schema::hasColumn('gsc_syncs', 'rows_countries')) {
                $table->unsignedInteger('rows_countries')->default(0)->after('rows_devices');
            }
            if (! Schema::hasColumn('gsc_syncs', 'status')) {
                $table->string('status', 40)->default('success')->after('rows_countries');
            }
            if (! Schema::hasColumn('gsc_syncs', 'error_message')) {
                $table->text('error_message')->nullable()->after('status');
            }
        });

        Schema::create('gsc_countries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('search_console_site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('country', 12);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('impressions')->default(0);
            $table->decimal('ctr', 8, 4)->default(0);
            $table->decimal('position', 8, 2)->default(0);
            $table->date('date_start');
            $table->date('date_end');
            $table->timestamps();
            $table->unique(['website_id', 'country', 'date_start', 'date_end'], 'gsc_countries_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gsc_countries');

        Schema::table('gsc_syncs', function (Blueprint $table): void {
            foreach (['error_message', 'status', 'rows_countries', 'rows_devices', 'rows_pages', 'rows_queries', 'rows_daily'] as $column) {
                if (Schema::hasColumn('gsc_syncs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
