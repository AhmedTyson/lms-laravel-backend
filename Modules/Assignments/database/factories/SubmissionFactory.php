<?php

namespace Modules\Assignments\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Assignments\Models\Assignment;
use Modules\Assignments\Models\Submission;
use Modules\Enrollment\Models\Enrollment;

class SubmissionFactory extends Factory
{
    protected $model = Submission::class;

    public function definition(): array
    {
        return [
            'assignment_id' => Assignment::factory(),
            'enrollment_id' => Enrollment::factory(),
            'attempt_number' => 1,
            'file_path' => null,
            'content' => $this->faker->paragraph(),
            'submitted_at' => now(),
            'is_late' => false,
            'status' => 'submitted',
        ];
    }
}
