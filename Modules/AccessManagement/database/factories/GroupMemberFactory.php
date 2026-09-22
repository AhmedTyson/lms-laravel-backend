<?php

namespace Modules\AccessManagement\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AccessManagement\Models\Group;
use Modules\AccessManagement\Models\GroupMember;

class GroupMemberFactory extends Factory
{
    protected $model = GroupMember::class;

    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'user_id' => User::factory(),
            'joined_at' => now(),
        ];
    }
}
