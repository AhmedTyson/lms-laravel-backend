<?php

namespace Modules\Assignments\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Assignments\Models\Assignment;
use Modules\Assignments\Models\Submission;
use Modules\Courses\Models\Course;

class AssignmentsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $course = Course::first() ?? Course::factory()->create();

        $assignment = Assignment::factory()->create(['course_id' => $course->id]);

        Submission::factory()->create(['assignment_id' => $assignment->id]);
    }
}
