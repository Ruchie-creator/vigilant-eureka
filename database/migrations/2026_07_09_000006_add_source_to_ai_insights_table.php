<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_insights', function (Blueprint $table): void {
            $table->enum('source', ['ai', 'rule_based'])->default('rule_based')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('ai_insights', function (Blueprint $table): void {
            $table->dropColumn('source');
        });
    }
};
