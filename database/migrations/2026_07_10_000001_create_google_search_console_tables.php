<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('google');
            $table->string('email')->nullable();
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('scopes')->nullable();
            $table->timestamps();
        });

        Schema::create('search_console_sites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('google_account_id')->constrained()->cascadeOnDelete();
            $table->string('site_url', 512);
            $table->string('permission_level')->nullable();
            $table->timestamps();
            $table->unique(['google_account_id', 'site_url'], 'gsc_sites_account_url_unique');
        });

        Schema::table('websites', function (Blueprint $table): void {
            $table->foreignId('search_console_site_id')->nullable()->after('status')->constrained('search_console_sites')->nullOnDelete();
            $table->timestamp('gsc_last_synced_at')->nullable()->after('search_console_site_id');
        });

        Schema::create('gsc_daily_metrics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('search_console_site_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('impressions')->default(0);
            $table->decimal('ctr', 8, 4)->default(0);
            $table->decimal('position', 8, 2)->default(0);
            $table->timestamps();
            $table->unique(['website_id', 'date'], 'gsc_daily_website_date_unique');
        });

        Schema::create('gsc_queries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('search_console_site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('query', 512);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('impressions')->default(0);
            $table->decimal('ctr', 8, 4)->default(0);
            $table->decimal('position', 8, 2)->default(0);
            $table->date('date_start');
            $table->date('date_end');
            $table->timestamps();
            $table->unique(['website_id', 'query', 'date_start', 'date_end'], 'gsc_queries_unique');
        });

        Schema::create('gsc_pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('search_console_site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('page_url', 512);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('impressions')->default(0);
            $table->decimal('ctr', 8, 4)->default(0);
            $table->decimal('position', 8, 2)->default(0);
            $table->date('date_start');
            $table->date('date_end');
            $table->timestamps();
            $table->unique(['website_id', 'page_url', 'date_start', 'date_end'], 'gsc_pages_unique');
        });

        Schema::create('gsc_devices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('search_console_site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('device', 40);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('impressions')->default(0);
            $table->decimal('ctr', 8, 4)->default(0);
            $table->decimal('position', 8, 2)->default(0);
            $table->date('date_start');
            $table->date('date_end');
            $table->timestamps();
            $table->unique(['website_id', 'device', 'date_start', 'date_end'], 'gsc_devices_unique');
        });

        Schema::create('growth_opportunities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->enum('source_type', ['query', 'page', 'device', 'date']);
            $table->string('source_value', 512);
            $table->char('source_hash', 64);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('impressions')->default(0);
            $table->decimal('ctr', 8, 4)->default(0);
            $table->decimal('position', 8, 2)->default(0);
            $table->string('opportunity_type');
            $table->text('problem');
            $table->text('recommendation');
            $table->text('expected_result')->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->enum('status', ['open', 'reviewed', 'in_progress', 'completed', 'ignored'])->default('open');
            $table->date('date_start');
            $table->date('date_end');
            $table->timestamps();
            $table->unique(['website_id', 'source_type', 'source_hash', 'opportunity_type', 'date_start', 'date_end', 'status'], 'growth_opportunities_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('growth_opportunities');
        Schema::dropIfExists('gsc_devices');
        Schema::dropIfExists('gsc_pages');
        Schema::dropIfExists('gsc_queries');
        Schema::dropIfExists('gsc_daily_metrics');
        Schema::table('websites', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('search_console_site_id');
            $table->dropColumn('gsc_last_synced_at');
        });
        Schema::dropIfExists('search_console_sites');
        Schema::dropIfExists('google_accounts');
    }
};
