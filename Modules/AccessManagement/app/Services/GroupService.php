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
            $group = new Group(['name' => $data['name']]);
            $group->owner()->associate($owner);
            $group->save();

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
    // else the oldest remaining member. Membership ≠ permission (RULE-009).
    public function removeMember(Group $group, int $userId): Group
    {
        return DB::transaction(function () use ($group, $userId) {
            GroupMember::where('group_id', $group->id)->where('user_id', $userId)->firstOrFail()->delete();

            if ((int) $group->owner_id !== $userId) {
                return $group->fresh();
            }

            $owner = User::find($userId);

            // Manager first (RULE-012), else oldest surviving user; successor joins if needed.
            $successorId = $owner?->manager?->id
                ?? User::whereKeyNot($userId)->oldest('id')->value('id');

            abort_unless($successorId, 422, 'Group has no eligible successor.');

            GroupMember::firstOrCreate(
                ['group_id' => $group->id, 'user_id' => $successorId],
                ['joined_at' => now()]
            );

            $group->forceFill(['owner_id' => $successorId])->save();

            return $group->fresh();
        });
    }

    public function delete(Group $group): void
    {
        DB::transaction(function () use ($group) {
            $group->members()->delete();
            $group->delete();
        });
    }
}
