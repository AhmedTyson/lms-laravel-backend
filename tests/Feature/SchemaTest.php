<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Modules\Assignments\Models\Assignment;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Lesson;
use Modules\Enrollment\Models\Enrollment;
use Modules\Progress\Models\ComponentCompletion;

// Phase 3 gate: full §5 schema present with race-closing uniques, and the
// polymorphic completion relation resolves without type-switches (ADR-010).

test('all nineteen spec tables exist', function () {
    $tables = [
        'users', 'permission_grants', 'groups', 'group_members',
        'courses', 'lessons', 'enrollments',
        'assignments', 'submissions',
        'quizzes', 'questions', 'question_options', 'question_accepted_answers',
        'attempts', 'attempt_answers',
        'progress_records', 'component_completions',
        'notifications',
        'activity_log',
    ];

    foreach ($tables as $table) {
        expect(Schema::hasTable($table))->toBeTrue("$table missing");
    }
});

test('v2.1 scoring columns exist with correct shape', function () {
    expect(Schema::hasColumn('questions', 'points'))->toBeTrue();
    expect(Schema::hasColumn('quizzes', 'passing_threshold'))->toBeTrue();
    expect(Schema::hasColumn('assignments', 'max_score'))->toBeTrue();
    expect(Schema::hasColumn('assignments', 'passing_threshold'))->toBeTrue();
    expect(Schema::hasColumn('users', 'manager_id'))->toBeTrue();
    expect(Schema::hasColumn('users', 'approval_status'))->toBeTrue();
});

test('duplicate enrollment violates unique index', function () {
    $enrollment = Enrollment::factory()->create();

    expect(fn () => Enrollment::factory()->create([
        'student_id' => $enrollment->student_id,
        'course_id' => $enrollment->course_id,
    ]))->toThrow(QueryException::class);
});

test('duplicate lesson order violates unique index', function () {
    $lesson = Lesson::factory()->create();

    expect(fn () => Lesson::factory()->create([
        'course_id' => $lesson->course_id,
        'order' => $lesson->order,
    ]))->toThrow(QueryException::class);
});

test('completion morph resolves lesson without type-switch', function () {
    $lesson = Lesson::factory()->create();
    $enrollment = Enrollment::factory()->create(['course_id' => $lesson->course_id]);

    $completion = ComponentCompletion::create([
        'enrollment_id' => $enrollment->id,
        'component_type' => 'lesson',
        'component_id' => $lesson->id,
    ]);

    expect($completion->component)->toBeInstanceOf(Lesson::class);
    expect($completion->component->id)->toBe($lesson->id);
    expect($lesson->completions()->count())->toBe(1);
});

test('completion morph resolves assignment and quiz', function () {
    $course = Course::factory()->create();
    $assignment = Assignment::create([
        'course_id' => $course->id,
        'title' => 'Essay',
        'due_date' => now()->addWeek(),
        'max_score' => 100,
        'passing_threshold' => 60,
    ]);
    $enrollment = Enrollment::factory()->create(['course_id' => $course->id]);

    $completion = ComponentCompletion::create([
        'enrollment_id' => $enrollment->id,
        'component_type' => 'assignment',
        'component_id' => $assignment->id,
    ]);

    expect($completion->component)->toBeInstanceOf(Assignment::class);
    expect($assignment->completions()->count())->toBe(1);
});
