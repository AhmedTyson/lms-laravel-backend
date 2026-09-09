<?php

namespace Modules\Enrollment\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Courses\Models\Course;
use Modules\Enrollment\Models\Enrollment;

class EnrollmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Enrollment::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'student_id' => User::factory(),
            'course_id' => Course::factory(),
            'status' => 'active',
            'enrolled_at' => now(),
        ];
    }
}
