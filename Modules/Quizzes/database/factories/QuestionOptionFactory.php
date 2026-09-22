<?php

namespace Modules\Quizzes\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Quizzes\Models\Question;
use Modules\Quizzes\Models\QuestionOption;

class QuestionOptionFactory extends Factory
{
    protected $model = QuestionOption::class;

    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'label' => $this->faker->word(),
            'is_correct' => false,
            'order' => 1,
        ];
    }
}
