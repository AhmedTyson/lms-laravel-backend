<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Spec §5.14 + RULE-017: short-answer questions accept multiple variants.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_accepted_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('answer_text');
            $table->timestamps();
            $table->index('question_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_accepted_answers');
    }
};
