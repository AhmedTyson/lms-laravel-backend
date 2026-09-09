<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Spec §5.16: one row per attempt+question; exactly one answer column set
// per row depending on question type (enforced at service layer).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->json('selected_option_ids')->nullable();
            $table->boolean('answer_boolean')->nullable();
            $table->string('answer_text')->nullable();
            $table->decimal('answer_number', 10, 4)->nullable();
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
            $table->index('attempt_id');
            $table->unique(['attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_answers');
    }
};
