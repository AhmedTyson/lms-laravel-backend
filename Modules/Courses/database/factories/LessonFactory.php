<?php

namespace Modules\Courses\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Lesson;

class LessonFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Lesson::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => $this->faker->sentence(4),
            'content_reference' => $this->faker->url(),
            'order' => $this->faker->unique()->numberBetween(1, 9999),
        ];
    }
}
