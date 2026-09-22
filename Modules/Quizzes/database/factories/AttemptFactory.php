<?php

namespace Modules\Quizzes\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Enrollment\Models\Enrollment;
use Modules\Quizzes\Models\Attempt;
use Modules\Quizzes\Models\Quiz;

class AttemptFactory extends Factory
{
    protected $model = Attempt::class;

    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'enrollment_id' => Enrollment::factory(),
            'attempt_number' => 1,
            'started_at' => now(),
            'submitted_at' => null,
        ];
    }
}
