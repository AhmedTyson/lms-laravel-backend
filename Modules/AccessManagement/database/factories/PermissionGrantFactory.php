<?php

namespace Modules\AccessManagement\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AccessManagement\Models\Group;
use Modules\AccessManagement\Models\PermissionGrant;

class PermissionGrantFactory extends Factory
{
    protected $model = PermissionGrant::class;

    public function definition(): array
    {
        return [
            'granter_id' => User::factory(),
            'grantee_id' => User::factory(),
            'group_id' => Group::factory(),
            'permission_name' => 'courses.publish',
            'action' => 'granted',
            'granted_at' => now(),
            'revoked_at' => null,
        ];
    }
}
