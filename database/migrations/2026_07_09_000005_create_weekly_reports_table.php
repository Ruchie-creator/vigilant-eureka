<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_reports', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->date('week_start');
            $table->date('week_end');
            $table->text('summary')->nullable();
            $table->text('wins')->nullable();
            $table->text('issues')->nullable();
            $table->text('recommendations')->nullable();
            $table->text('next_actions')->nullable();
            $table->enum('status', ['draft', 'ready', 'sent', 'archived'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_reports');
    }
};
