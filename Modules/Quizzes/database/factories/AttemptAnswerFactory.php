<?php

namespace Modules\Quizzes\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Quizzes\Models\Attempt;
use Modules\Quizzes\Models\AttemptAnswer;
use Modules\Quizzes\Models\Question;

class AttemptAnswerFactory extends Factory
{
    protected $model = AttemptAnswer::class;

    public function definition(): array
    {
        return [
            'attempt_id' => Attempt::factory(),
            'question_id' => Question::factory(),
            'is_correct' => false,
        ];
    }
}
