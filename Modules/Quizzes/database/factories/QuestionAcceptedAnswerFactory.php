<?php

namespace Modules\Quizzes\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Quizzes\Models\Question;
use Modules\Quizzes\Models\QuestionAcceptedAnswer;

class QuestionAcceptedAnswerFactory extends Factory
{
    protected $model = QuestionAcceptedAnswer::class;

    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'answer_text' => $this->faker->word(),
        ];
    }
}
