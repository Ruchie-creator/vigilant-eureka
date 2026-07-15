<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('websites', function (Blueprint $table): void {
            $table->string('tracking_key', 48)->nullable()->unique()->after('status');
        });

        DB::table('websites')
            ->whereNull('tracking_key')
            ->orderBy('id')
            ->eachById(function (object $website): void {
                DB::table('websites')
                    ->where('id', $website->id)
                    ->update(['tracking_key' => Str::random(48)]);
            });

        Schema::create('conversion_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('growth_opportunity_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('event_uuid');
            $table->string('event_type', 40);
            $table->string('action_label', 120)->nullable();
            $table->string('page_url', 2048);
            $table->string('target_url', 2048)->nullable();
            $table->string('device_type', 20)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->unique(['website_id', 'event_uuid'], 'conversion_events_website_event_unique');
            $table->index(['website_id', 'occurred_at']);
            $table->index(['growth_opportunity_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversion_events');

        Schema::table('websites', function (Blueprint $table): void {
            $table->dropUnique(['tracking_key']);
            $table->dropColumn('tracking_key');
        });
    }
};
