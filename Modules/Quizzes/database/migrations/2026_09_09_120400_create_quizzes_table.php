<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Spec §5.11: threshold is a PERCENTAGE of SUM(questions.points), computed
// dynamically at evaluation time — never cached, so re-authoring questions
// never silently invalidates the threshold.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->timestamp('opens_at');
            $table->timestamp('closes_at');
            $table->unsignedInteger('max_attempts')->default(1);
            $table->decimal('passing_threshold', 5, 2);
            $table->timestamps();
            $table->index('course_id');
            $table->index(['opens_at', 'closes_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
