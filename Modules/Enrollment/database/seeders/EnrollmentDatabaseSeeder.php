<?php

namespace Modules\Enrollment\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Courses\Models\Course;
use Modules\Enrollment\Models\Enrollment;

class EnrollmentDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $course = Course::where('status', 'published')->first()
            ?? Course::factory()->create(['status' => 'published', 'published_at' => now()]);

        $student = User::where('email', 'student@example.com')->first()
            ?? User::factory()->create();

        Enrollment::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
    }
}
