<?php

namespace Modules\Quizzes\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Courses\Models\Course;
use Modules\Quizzes\Models\Quiz;

class QuizFactory extends Factory
{
    protected $model = Quiz::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => $this->faker->unique()->sentence(3),
            'opens_at' => now()->subDay(),
            'closes_at' => now()->addWeek(),
            'max_attempts' => 3,
            'passing_threshold' => '70.00',
        ];
    }
}
