<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_insights', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('audit_id')->nullable()->constrained('seo_audits')->nullOnDelete();
            $table->string('title');
            $table->text('summary');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->enum('category', ['seo', 'content', 'technical', 'conversion', 'local_seo'])->default('seo');
            $table->text('recommendation');
            $table->text('expected_result')->nullable();
            $table->enum('status', ['new', 'reviewed', 'implemented', 'ignored'])->default('new');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_insights');
    }
};
