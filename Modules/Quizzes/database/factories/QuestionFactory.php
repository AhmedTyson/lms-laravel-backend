<?php

namespace Modules\Quizzes\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Quizzes\Models\Question;
use Modules\Quizzes\Models\Quiz;

class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'prompt' => $this->faker->sentence(),
            'type' => 'single_choice',
            'order' => 1,
            'points' => '1.00',
        ];
    }
}
