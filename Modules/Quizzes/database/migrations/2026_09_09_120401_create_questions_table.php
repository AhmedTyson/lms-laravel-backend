<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Spec §5.12: per-question points enable mixed scoring (0.5, 2, 5) in one
// quiz. Type-specific columns stay NULL unless the type needs them.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->text('prompt');
            $table->string('type', 30); // single|multiple|boolean|text|numeric (validated at service layer)
            $table->unsignedInteger('order');
            $table->decimal('points', 6, 2)->default(1.00);
            $table->boolean('correct_boolean')->nullable();
            $table->decimal('correct_number', 10, 4)->nullable();
            $table->decimal('numeric_tolerance', 10, 4)->nullable()->default(0);
            $table->timestamps();
            $table->index('quiz_id');
            $table->index(['quiz_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
