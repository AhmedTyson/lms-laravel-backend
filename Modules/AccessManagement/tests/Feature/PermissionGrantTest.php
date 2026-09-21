<?php

namespace Modules\AccessManagement\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AccessManagement\Models\Group;
use Modules\AccessManagement\Models\PermissionGrant;
use Modules\AccessManagement\Services\PermissionReadModelSync;
use Tests\TestCase;

class PermissionGrantTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $sub;

    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->sub = User::factory()->create(['manager_id' => $this->admin->id]);
        $this->group = Group::create(['owner_id' => $this->admin->id, 'name' => 'Engineering']);

        // System-issued ceiling so the admin can grant: ledger row + read-model sync.
        $seed = PermissionGrant::create([
            'granter_id' => $this->admin->id,
            'grantee_id' => $this->admin->id,
            'group_id' => $this->group->id,
            'permission_name' => 'courses.publish',
            'action' => 'granted',
            'granted_at' => now(),
        ]);
        app(PermissionReadModelSync::class)->syncFromGrant($seed);
    }

    private function asAdmin(): static
    {
        return $this->withHeader('Authorization', 'Bearer '.auth('api')->login($this->admin));
    }

    private function payload(User $grantee, string $permission = 'courses.publish'): array
    {
        return [
            'grantee_id' => $grantee->id,
            'permission_name' => $permission,
            'group_id' => $this->group->id,
        ];
    }

    public function test_grant_to_subordinate_returns_201_and_appends_ledger(): void
    {
        $this->asAdmin()
            ->postJson('/api/grants', $this->payload($this->sub))
            ->assertStatus(201)
            ->assertJsonPath('message', 'Grant recorded.');

        $this->assertDatabaseCount('permission_grants', 2);
        $this->assertDatabaseHas('permission_grants', [
            'grantee_id' => $this->sub->id, 'action' => 'granted',
        ]);
    }

    public function test_grant_to_non_subordinate_returns_403(): void
    {
        $outsider = User::factory()->create();

        $this->asAdmin()
            ->postJson('/api/grants', $this->payload($outsider))
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'GRANT_FORBIDDEN');
    }

    public function test_grant_unheld_permission_returns_403(): void
    {
        $this->asAdmin()
            ->postJson('/api/grants', $this->payload($this->sub, 'vault.nuke'))
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'GRANT_FORBIDDEN');
    }

    public function test_revoke_appends_row_and_clears_read_model(): void
    {
        $this->asAdmin()->postJson('/api/grants', $this->payload($this->sub))->assertStatus(201);

        $this->asAdmin()
            ->postJson('/api/revokes', $this->payload($this->sub))
            ->assertStatus(201)
            ->assertJsonPath('message', 'Revoke recorded.');

        // Append-only: seed + grant + revoke rows, originals untouched.
        $this->assertDatabaseCount('permission_grants', 3);
        $this->assertDatabaseHas('permission_grants', [
            'grantee_id' => $this->sub->id, 'action' => 'granted',
        ]);
        $this->assertDatabaseHas('permission_grants', [
            'grantee_id' => $this->sub->id, 'action' => 'revoked',
        ]);
        $this->assertDatabaseMissing('user_permissions', [
            'user_id' => $this->sub->id, 'permission_name' => 'courses.publish',
        ]);
    }

    public function test_revoke_by_non_subordinate_returns_403(): void
    {
        $outsider = User::factory()->create();

        $this->asAdmin()
            ->postJson('/api/revokes', $this->payload($outsider))
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'REVOKE_FORBIDDEN');
    }
}
