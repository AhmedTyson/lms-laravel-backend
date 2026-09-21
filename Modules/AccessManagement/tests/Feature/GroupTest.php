<?php

namespace Modules\AccessManagement\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AccessManagement\Models\Group;
use Modules\AccessManagement\Models\GroupMember;
use Tests\TestCase;

class GroupTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return auth('api')->login($user);
    }

    public function test_member_without_grant_gets_403_on_guarded_action(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $group = Group::create(['owner_id' => $owner->id, 'name' => 'Engineering']);

        // Membership alone confers nothing (RULE-009): member is not owner's subordinate chain target here,
        // and holds no permission, so granting through them fails the ceiling.
        $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($member))
            ->postJson('/api/grants', [
                'grantee_id' => $owner->id,
                'permission_name' => 'courses.publish',
                'group_id' => $group->id,
            ])
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'GRANT_FORBIDDEN');
    }

    public function test_owner_removal_passes_ownership_to_manager(): void
    {
        $manager = User::factory()->create();
        $owner = User::factory()->create(['manager_id' => $manager->id]);
        $group = Group::create(['owner_id' => $owner->id, 'name' => 'Engineering']);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $owner->id]);

        $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($manager))
            ->deleteJson("/api/groups/{$group->id}/members/{$owner->id}")
            ->assertStatus(200);

        $this->assertDatabaseHas('groups', ['id' => $group->id, 'owner_id' => $manager->id]);
        $this->assertDatabaseMissing('group_members', ['group_id' => $group->id, 'user_id' => $owner->id]);
    }

    public function test_owner_removal_without_manager_falls_back_to_admin(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $owner = User::factory()->create();
        $group = Group::create(['owner_id' => $owner->id, 'name' => 'Engineering']);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $owner->id]);

        $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($admin))
            ->deleteJson("/api/groups/{$group->id}/members/{$owner->id}")
            ->assertStatus(200);

        $this->assertDatabaseHas('groups', ['id' => $group->id, 'owner_id' => $admin->id]);
    }

    public function test_group_crud_round_trip(): void
    {
        $owner = User::factory()->create();
        $headers = ['Authorization' => 'Bearer '.$this->tokenFor($owner)];

        $id = $this->withHeaders($headers)
            ->postJson('/api/groups', ['name' => 'Physics'])
            ->assertStatus(201)
            ->json('data.id');

        $this->withHeaders($headers)->getJson("/api/groups/{$id}")->assertStatus(200);
        $this->withHeaders($headers)->deleteJson("/api/groups/{$id}")->assertStatus(200);
        $this->assertDatabaseMissing('groups', ['id' => $id]);
    }
}
