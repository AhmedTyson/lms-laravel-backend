<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Spec §5.18 + ADR-010: polymorphic target (lesson|assignment|quiz) resolved
// via Eloquent morphTo — no DB-level cross-table FK, application-enforced.
// Completion = best attempt ≥ % threshold (RULE-018) OR instructor override (RULE-019).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('component_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->string('component_type', 30);
            $table->unsignedBigInteger('component_id');
            $table->timestamp('completed_at')->nullable();
            $table->boolean('is_override')->default(false);
            $table->foreignId('overridden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['enrollment_id', 'component_type', 'component_id']);
            $table->index('enrollment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('component_completions');
    }
};
