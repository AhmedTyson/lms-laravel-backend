<?php

namespace Modules\Courses\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Courses\Models\Course;
use Tests\TestCase;

class CourseListTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_all_courses_returns_paginated_envelope(): void
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
            'status' => 'draft',
        ]);

        $this->getJson('/api/courses?page=1&per_page=10')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.title', 'Laravel Basics');
    }

    public function test_get_all_courses_filters_by_search_and_status(): void
    {
        $instructor = User::factory()->create();
        Course::create([
            'instructor_id' => $instructor->id,
            'title' => 'Laravel Basics',
            'category' => 'Backend',
            'status' => 'published',
        ]);
        Course::create([
            'instructor_id' => $instructor->id,
            'title' => 'Vue Basics',
            'category' => 'Frontend',
            'status' => 'draft',
        ]);

        $this->getJson('/api/courses?page=1&per_page=10&search=Laravel&status=published')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laravel Basics');
    }

    public function test_search_does_not_leak_across_status_filter(): void
    {
        $instructor = User::factory()->create();
        Course::create([
            'instructor_id' => $instructor->id,
            'title' => 'Vue Basics',
            'category' => 'Frontend',
            'description' => 'Nothing like Laravel here',
            'status' => 'draft',
        ]);
        Course::create([
            'instructor_id' => $instructor->id,
            'title' => 'Laravel Basics',
            'category' => 'Backend',
            'status' => 'published',
        ]);

        // UnGrouped OR would match the draft via description, ignoring status.
        $this->getJson('/api/courses?page=1&per_page=10&search=Laravel&status=published')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laravel Basics');
    }

    public function test_get_all_courses_requires_pagination_params(): void
    {
        $this->getJson('/api/courses')->assertStatus(422);
    }
}
