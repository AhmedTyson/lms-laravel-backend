<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Spec §5.9: instructor-defined ceiling + percentage threshold (v2.1 variable
// scoring). due_date required; future-dated enforced at app layer, not DB.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('due_date');
            $table->boolean('resubmission_allowed')->default(false);
            $table->decimal('max_score', 6, 2);
            $table->decimal('passing_threshold', 5, 2);
            $table->timestamps();
            $table->index('course_id');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
