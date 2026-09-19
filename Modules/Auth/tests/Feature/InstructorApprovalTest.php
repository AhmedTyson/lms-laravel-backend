<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
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
}
