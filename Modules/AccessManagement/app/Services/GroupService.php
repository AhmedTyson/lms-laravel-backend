<?php

namespace Modules\AccessManagement\Services;

use App\Enums\ApprovalStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\AccessManagement\Models\Group;
use Modules\AccessManagement\Models\GroupMember;
use Spatie\Permission\Models\Role;

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

            $successorId = $this->resolveSuccessorId($userId);

            abort_unless($successorId, 422, 'Group has no eligible successor.');

            GroupMember::firstOrCreate(
                ['group_id' => $group->id, 'user_id' => $successorId],
                ['joined_at' => now()]
            );

            $group->forceFill(['owner_id' => $successorId])->save();

            return $group->fresh();
        });
    }

    // Manager first (RULE-012), else any Admin (or oldest non-student eligible user); successor joins if needed.
    private function resolveSuccessorId(int $removedOwnerId): ?int
    {
        $managerId = User::find($removedOwnerId)?->manager?->id;

        if ($managerId !== null) {
            return $managerId;
        }

        if (Schema::hasTable('roles') && Schema::hasTable('model_has_roles')) {
            $adminRoleId = Role::where('name', 'admin')->where('guard_name', 'api')->value('id');
            if ($adminRoleId !== null) {
                $roledAdminId = User::whereKeyNot($removedOwnerId)
                    ->whereNull('suspended_at')
                    ->whereIn('id', DB::table('model_has_roles')
                        ->where('role_id', $adminRoleId)
                        ->where('model_type', (new User)->getMorphClass())
                        ->pluck('model_id'))
                    ->oldest('id')
                    ->value('id');

                if ($roledAdminId !== null) {
                    return $roledAdminId;
                }
            }

            $studentRoleId = Role::where('name', 'student')->where('guard_name', 'api')->value('id');
            if ($studentRoleId !== null) {
                return User::whereKeyNot($removedOwnerId)
                    ->whereNull('suspended_at')
                    ->where(fn ($query) => $query->whereNull('approval_status')->orWhere('approval_status', ApprovalStatus::Approved))
                    ->whereNotIn('id', DB::table('model_has_roles')
                        ->where('role_id', $studentRoleId)
                        ->where('model_type', (new User)->getMorphClass())
                        ->pluck('model_id'))
                    ->oldest('id')
                    ->value('id');
            }
        }

        return User::whereKeyNot($removedOwnerId)->oldest('id')->value('id');
    }

    public function delete(Group $group): void
    {
        DB::transaction(function () use ($group) {
            $group->members()->delete();
            $group->delete();
        });
    }
}
