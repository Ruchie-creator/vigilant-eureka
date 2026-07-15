<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_memories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('website_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('memory_type', 60);
            $table->string('memory_key', 190);
            $table->longText('memory_value');
            $table->decimal('confidence', 5, 4)->nullable();
            $table->string('source_type', 80)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->unique(['agent_id', 'website_id', 'memory_type', 'memory_key'], 'agent_memories_scope_unique');
            $table->index(['agent_id', 'website_id', 'expires_at']);
        });

        Schema::create('agent_handoffs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_run_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('from_agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('to_agent_id')->constrained('agents')->cascadeOnDelete();
            $table->text('reason');
            $table->json('context')->nullable();
            $table->text('expected_output')->nullable();
            $table->enum('status', ['pending', 'accepted', 'completed', 'ignored', 'failed'])->default('pending');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['website_id', 'to_agent_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_handoffs');
        Schema::dropIfExists('agent_memories');
    }
};
