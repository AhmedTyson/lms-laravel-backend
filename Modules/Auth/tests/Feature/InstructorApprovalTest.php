<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AccessManagement\Models\PermissionGrant;
use Modules\AccessManagement\Services\PermissionReadModelSync;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InstructorApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_approve_instructor(): void
    {
        $admin = User::factory()->create();
        $permission = Permission::create(['name' => 'approve-instructors', 'guard_name' => 'api']);
        setPermissionsTeamId(0);
        $admin->givePermissionTo($permission);

        $instructor = User::factory()->create([
            'approval_status' => 'pending',
            'manager_id' => null,
        ]);

        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/auth/instructors/{$instructor->id}/approve", [
                'manager_id' => $admin->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.approval_status', 'approved')
            ->assertJsonPath('data.manager_id', $admin->id);

        $this->assertDatabaseHas('users', [
            'id' => $instructor->id,
            'approval_status' => 'approved',
            'manager_id' => $admin->id,
        ]);
    }

    public function test_unauthorized_user_cannot_approve_instructor(): void
    {
        $user = User::factory()->create();
        $instructor = User::factory()->create([
            'approval_status' => 'pending',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/auth/instructors/{$instructor->id}/approve");

        $response->assertStatus(403);
    }

    public function test_cannot_approve_student_and_delegated_permission_in_read_model_authorizes_approval(): void
    {
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'api']);
        $rootAdmin = User::factory()->create();
        $delegatedAdmin = User::factory()->create(['manager_id' => $rootAdmin->id]);

        $grant = PermissionGrant::create([
            'granter_id' => $rootAdmin->id,
            'grantee_id' => $delegatedAdmin->id,
            'group_id' => null,
            'permission_name' => 'approve-instructors',
            'action' => 'granted',
            'granted_at' => now(),
        ]);
        app(PermissionReadModelSync::class)->syncFromGrant($grant);

        $student = User::factory()->create(['approval_status' => null]);
        setPermissionsTeamId(0);
        $student->assignRole('student');

        $token = auth('api')->login($delegatedAdmin);

        // Student cannot be approved as an instructor
        $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/auth/instructors/{$student->id}/approve")
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INVALID_INSTRUCTOR_STATE');

        // Pending instructor CAN be approved by delegated admin via unified Gate::before
        $pending = User::factory()->create(['approval_status' => 'pending']);

        $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/auth/instructors/{$pending->id}/approve", [
                'manager_id' => $delegatedAdmin->id,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.approval_status', 'approved');
    }
}
