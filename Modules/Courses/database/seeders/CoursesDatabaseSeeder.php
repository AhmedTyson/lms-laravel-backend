<?php

namespace Modules\Courses\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Lesson;

class CoursesDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $published = Course::factory()->create([
            'title' => 'Laravel Basics',
            'category' => 'Backend',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Lesson::factory()->count(3)->sequence(
            ['course_id' => $published->id, 'order' => 1, 'title' => 'Routing'],
            ['course_id' => $published->id, 'order' => 2, 'title' => 'Eloquent'],
            ['course_id' => $published->id, 'order' => 3, 'title' => 'Queues'],
        )->create();

        Course::factory()->create(['title' => 'Vue Basics', 'status' => 'draft']);
    }
}
