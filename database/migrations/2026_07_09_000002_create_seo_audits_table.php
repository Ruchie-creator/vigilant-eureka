<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('page_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('h1')->nullable();
            $table->string('canonical_url', 2048)->nullable();
            $table->string('robots_meta')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->boolean('sitemap_available')->default(false);
            $table->boolean('robots_txt_available')->default(false);
            $table->boolean('is_indexable')->default(false);
            $table->json('missing_fields')->nullable();
            $table->json('recommendations')->nullable();
            $table->text('raw_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_audits');
    }
};
