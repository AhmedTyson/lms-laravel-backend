<?php

namespace Modules\Assignments\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Assignments\Models\Assignment;
use Modules\Courses\Models\Course;

class AssignmentFactory extends Factory
{
    protected $model = Assignment::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => $this->faker->unique()->sentence(3),
            'description' => $this->faker->paragraph(),
            'due_date' => now()->addWeek(),
            'resubmission_allowed' => false,
            'max_score' => '100.00',
            'passing_threshold' => '60.00',
        ];
    }
}
