<?php

namespace Modules\Courses\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Courses\Models\Course;
use Tests\TestCase;

class CourseListTest extends TestCase
{
    use RefreshDatabase;

    private function seedCourses(): void
    {
        $instructor = User::factory()->create();
        Course::create([
            'instructor_id' => $instructor->id,
            'title' => 'Laravel Basics',
            'category' => 'Backend',
            'description' => 'Intro course',
            'status' => 'published',
        ]);
        Course::create([
            'instructor_id' => $instructor->id,
            'title' => 'Vue Basics',
            'category' => 'Frontend',
            'description' => 'Nothing like Laravel here',
            'status' => 'draft',
        ]);
    }

    public function test_get_all_courses_returns_paginated_envelope(): void
    {
        $this->seedCourses();

        $this->getJson('/api/courses?page=1&per_page=10')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['title' => 'Laravel Basics'])
            ->assertJsonFragment(['title' => 'Vue Basics']);
    }

    public function test_filter_search_and_status_combine(): void
    {
        $this->seedCourses();

        // Grouped OR must not leak the draft (description match) across status.
        $this->getJson('/api/courses?page=1&per_page=10&filter[search]=Laravel&filter[status]=published')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laravel Basics');
    }

    public function test_sort_descending_by_title(): void
    {
        $this->seedCourses();

        $this->getJson('/api/courses?page=1&per_page=10&sort=-title')
            ->assertStatus(200)
            ->assertJsonPath('data.0.title', 'Vue Basics');
    }

    public function test_get_all_courses_requires_pagination_params(): void
    {
        $this->getJson('/api/courses')->assertStatus(422);
    }
}
