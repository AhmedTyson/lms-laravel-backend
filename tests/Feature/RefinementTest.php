<?php

use App\Models\User;
use Illuminate\Database\QueryException;
use Modules\AccessManagement\Models\PermissionGrant;
use Modules\AccessManagement\Models\UserPermission;
use Modules\AccessManagement\Services\ManagerDepth;
use Modules\AccessManagement\Services\PermissionReadModelSync;
use Modules\Assignments\Models\Assignment;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Lesson;
use Modules\Enrollment\Models\Enrollment;
use Modules\Progress\Models\ComponentCompletion;
use Modules\Quizzes\Models\Quiz;
use Spatie\Activitylog\Models\Activity;

// Pre-Phase 7 refinement gate (ADR-011–015). Service-level atomicity for
// grants and depth maintenance wires up in Phase 5; these tests pin the seams
// (sync helper, depth helper, observer, prune command) in isolation.

test('Edit 1: user owning courses or grants cannot be hard-deleted', function () {
    $instructor = User::factory()->create();
    Course::factory()->create(['instructor_id' => $instructor->id]);

    try {
        $instructor->delete();
        $this->fail('Hard-deleting a course owner must violate RESTRICT.');
    } catch (QueryException) {
        expect(true)->toBeTrue();
    }
});

test('Edit 1: user owning grant history cannot be hard-deleted', function () {
    $granter = User::factory()->create();
    $grantee = User::factory()->create();
    PermissionGrant::create([
        'granter_id' => $granter->id,
        'grantee_id' => $grantee->id,
        'permission_name' => 'courses.publish',
        'action' => 'granted',
    ]);

    try {
        $granter->delete();
        $this->fail('Hard-deleting a granter must violate RESTRICT.');
    } catch (QueryException) {
        expect(true)->toBeTrue();
    }

    try {
        $grantee->delete();
        $this->fail('Hard-deleting a grantee must violate RESTRICT.');
    } catch (QueryException) {
        expect(true)->toBeTrue();
    }
});

test('Edit 2: read model and ledger never disagree across grant revoke grant', function () {
    $sync = new PermissionReadModelSync;
    $granter = User::factory()->create();
    $grantee = User::factory()->create();

    $grant = PermissionGrant::create([
        'granter_id' => $granter->id, 'grantee_id' => $grantee->id,
        'permission_name' => 'courses.publish', 'action' => 'granted',
    ]);
    $sync->syncFromGrant($grant);

    expect(UserPermission::where('user_id', $grantee->id)->count())->toBe(1);

    $revoke = PermissionGrant::create([
        'granter_id' => $granter->id, 'grantee_id' => $grantee->id,
        'permission_name' => 'courses.publish', 'action' => 'revoked',
    ]);
    $sync->syncFromGrant($revoke);

    expect(UserPermission::where('user_id', $grantee->id)->count())->toBe(0);
    expect(PermissionGrant::where('grantee_id', $grantee->id)->count())->toBe(2);

    $regrant = PermissionGrant::create([
        'granter_id' => $granter->id, 'grantee_id' => $grantee->id,
        'permission_name' => 'courses.publish', 'action' => 'granted',
    ]);
    $sync->syncFromGrant($regrant);

    $row = UserPermission::where('user_id', $grantee->id)->sole();
    expect($row->granted_via_grant_id)->toBe($regrant->id);
    expect(PermissionGrant::where('grantee_id', $grantee->id)->count())->toBe(3);
});

test('Edit 3: subtree recompute sets depths from the root', function () {
    $admin = User::factory()->create();
    $manager = User::factory()->create(['manager_id' => $admin->id]);
    $instructor = User::factory()->create(['manager_id' => $manager->id]);

    ManagerDepth::recomputeSubtree($admin);

    expect($admin->fresh()->manager_depth)->toBe(0);
    expect($manager->fresh()->manager_depth)->toBe(1);
    expect($instructor->fresh()->manager_depth)->toBe(2);
});

test('Edit 3: reassignment recomputes the moved subtree only', function () {
    $admin = User::factory()->create();
    $oldManager = User::factory()->create(['manager_id' => $admin->id]);
    $instructor = User::factory()->create(['manager_id' => $oldManager->id]);
    ManagerDepth::recomputeSubtree($admin);

    $instructor->forceFill(['manager_id' => $admin->id])->save();
    ManagerDepth::recomputeSubtree($instructor);

    expect($instructor->fresh()->manager_depth)->toBe(1);
    expect($oldManager->fresh()->manager_depth)->toBe(1);
});

test('Edit 4: prune deletes rows older than the retention window', function () {
    Activity::create(['description' => 'recent', 'created_at' => now()->subDays(10)]);
    Activity::create(['description' => 'stale', 'created_at' => now()->subDays(91)]);

    $this->artisan('activitylog:prune')->assertSuccessful();

    expect(Activity::where('description', 'recent')->exists())->toBeTrue();
    expect(Activity::where('description', 'stale')->exists())->toBeFalse();
});

test('Edit 4: retention window is configurable', function () {
    config(['audit.retention_days' => 7]);
    Activity::create(['description' => 'week-old', 'created_at' => now()->subDays(10)]);
    Activity::create(['description' => 'fresh', 'created_at' => now()->subDays(2)]);

    $this->artisan('activitylog:prune')->assertSuccessful();

    expect(Activity::where('description', 'week-old')->exists())->toBeFalse();
    expect(Activity::where('description', 'fresh')->exists())->toBeTrue();
});

test('Edit 5: deleting a lesson purges its completions', function () {
    $lesson = Lesson::factory()->create();
    $enrollment = Enrollment::factory()->create(['course_id' => $lesson->course_id]);
    ComponentCompletion::create([
        'enrollment_id' => $enrollment->id,
        'component_type' => 'lesson', 'component_id' => $lesson->id,
    ]);

    $lesson->delete();

    expect(ComponentCompletion::count())->toBe(0);
    expect($enrollment->fresh())->not->toBeNull();
});

test('Edit 5: deleting an assignment or quiz purges its completions', function () {
    $course = Course::factory()->create();
    $assignment = Assignment::create([
        'course_id' => $course->id, 'title' => 'Essay',
        'due_date' => now()->addWeek(), 'max_score' => 100, 'passing_threshold' => 60,
    ]);
    $quiz = Quiz::create([
        'course_id' => $course->id, 'title' => 'Midterm',
        'opens_at' => now()->subDay(), 'closes_at' => now()->addDay(), 'passing_threshold' => 60,
    ]);
    $enrollment = Enrollment::factory()->create(['course_id' => $course->id]);
    ComponentCompletion::create([
        'enrollment_id' => $enrollment->id,
        'component_type' => 'assignment', 'component_id' => $assignment->id,
    ]);
    ComponentCompletion::create([
        'enrollment_id' => $enrollment->id,
        'component_type' => 'quiz', 'component_id' => $quiz->id,
    ]);

    $assignment->delete();
    expect(ComponentCompletion::count())->toBe(1);

    $quiz->delete();
    expect(ComponentCompletion::count())->toBe(0);
});
