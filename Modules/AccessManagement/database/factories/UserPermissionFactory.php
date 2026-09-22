<?php

namespace Modules\AccessManagement\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AccessManagement\Models\Group;
use Modules\AccessManagement\Models\PermissionGrant;
use Modules\AccessManagement\Models\UserPermission;

class UserPermissionFactory extends Factory
{
    protected $model = UserPermission::class;

    public function definition(): array
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        $grant = PermissionGrant::factory()->create([
            'grantee_id' => $user->id,
            'group_id' => $group->id,
        ]);

        return [
            'user_id' => $user->id,
            'permission_name' => $grant->permission_name,
            'group_id' => $group->id,
            'granted_via_grant_id' => $grant->id,
        ];
    }
}
