<?php

namespace Modules\Courses\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Courses\Models\Course;

class CourseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Course::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'instructor_id' => User::factory(),
            'title' => $this->faker->unique()->sentence(3),
            'category' => $this->faker->word(),
            'description' => $this->faker->paragraph(),
            'status' => 'draft',
        ];
    }
}
