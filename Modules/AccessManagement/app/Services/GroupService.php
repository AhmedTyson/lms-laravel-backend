<?php

namespace Modules\AccessManagement\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\AccessManagement\Models\Group;
use Modules\AccessManagement\Models\GroupMember;

class GroupService
{
    public function create(array $data, User $owner): Group
    {
        return DB::transaction(function () use ($data, $owner) {
            $group = Group::create([
                'owner_id' => $owner->id,
                'name' => $data['name'],
            ]);

            GroupMember::create([
                'group_id' => $group->id,
                'user_id' => $owner->id,
                'joined_at' => now(),
            ]);

            return $group;
        });
    }

    public function addMember(Group $group, int $userId): GroupMember
    {
        return GroupMember::firstOrCreate(
            ['group_id' => $group->id, 'user_id' => $userId],
            ['joined_at' => now()]
        );
    }

    // RULE-012: owner removal passes ownership to the owner's manager,
    // else the seeded admin, else the oldest user. Membership ≠ permission (RULE-009).
    public function removeMember(Group $group, int $userId): Group
    {
        return DB::transaction(function () use ($group, $userId) {
            GroupMember::where('group_id', $group->id)->where('user_id', $userId)->firstOrFail()->delete();

            if ((int) $group->owner_id !== $userId) {
                return $group->fresh();
            }

            $owner = User::find($userId);

            $successor = $owner?->manager
                ?? User::where('email', env('ADMIN_SEED_EMAIL', 'admin@example.com'))->first()
                ?? User::oldest('id')->firstOrFail();

            $group->forceFill(['owner_id' => $successor->id])->save();

            return $group->fresh();
        });
    }
}
