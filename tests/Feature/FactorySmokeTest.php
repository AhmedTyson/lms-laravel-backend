<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AccessManagement\Models\Group;
use Modules\AccessManagement\Models\GroupMember;
use Modules\AccessManagement\Models\PermissionGrant;
use Modules\AccessManagement\Models\UserPermission;
use Modules\Assignments\Models\Assignment;
use Modules\Assignments\Models\Submission;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Lesson;
use Modules\Enrollment\Models\Enrollment;
use Modules\Notifications\Models\Notification;
use Modules\Progress\Models\ComponentCompletion;
use Modules\Progress\Models\ProgressRecord;
use Modules\Quizzes\Models\Attempt;
use Modules\Quizzes\Models\AttemptAnswer;
use Modules\Quizzes\Models\Question;
use Modules\Quizzes\Models\QuestionAcceptedAnswer;
use Modules\Quizzes\Models\QuestionOption;
use Modules\Quizzes\Models\Quiz;
use Tests\TestCase;

class FactorySmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_module_factory_resolves_and_inserts(): void
    {
        $this->assertInstanceOf(User::class, User::factory()->create());
        $this->assertInstanceOf(Course::class, Course::factory()->create());
        $this->assertInstanceOf(Lesson::class, Lesson::factory()->create());
        $this->assertInstanceOf(Enrollment::class, Enrollment::factory()->create());
        $this->assertInstanceOf(Assignment::class, Assignment::factory()->create());
        $this->assertInstanceOf(Submission::class, Submission::factory()->create());
        $this->assertInstanceOf(Quiz::class, Quiz::factory()->create());
        $this->assertInstanceOf(Question::class, Question::factory()->create());
        $this->assertInstanceOf(QuestionOption::class, QuestionOption::factory()->create());
        $this->assertInstanceOf(QuestionAcceptedAnswer::class, QuestionAcceptedAnswer::factory()->create());
        $this->assertInstanceOf(Attempt::class, Attempt::factory()->create());
        $this->assertInstanceOf(AttemptAnswer::class, AttemptAnswer::factory()->create());
        $this->assertInstanceOf(ProgressRecord::class, ProgressRecord::factory()->create());
        $this->assertInstanceOf(ComponentCompletion::class, ComponentCompletion::factory()->create());
        $this->assertInstanceOf(Notification::class, Notification::factory()->create());
        $this->assertInstanceOf(Group::class, Group::factory()->create());
        $this->assertInstanceOf(GroupMember::class, GroupMember::factory()->create());
        $this->assertInstanceOf(PermissionGrant::class, PermissionGrant::factory()->create());
        $this->assertInstanceOf(UserPermission::class, UserPermission::factory()->create());
    }
}
