<?php

namespace Modules\AccessManagement\Policies;

use App\Enums\ApprovalStatus;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Modules\AccessManagement\Models\Group;

class GroupPolicy
{
    public function create(User $user): bool
    {
        if ($user->suspended_at !== null || $user->approval_status === ApprovalStatus::Pending) {
            return false;
        }

        if (Schema::hasTable('roles')) {
            $previousTeamId = getPermissionsTeamId();
            setPermissionsTeamId(0);

            try {
                if ($user->hasRole('student', 'api') && ! $user->hasAnyRole(['instructor', 'admin'], 'api')) {
                    return false;
                }
            } finally {
                setPermissionsTeamId($previousTeamId);
            }
        }

        return true;
    }

    // Member, owner, or grant-holder sees the group.
    public function view(User $user, Group $group): bool
    {
        return (int) $group->owner_id === (int) $user->id
            || $group->members()->where('user_id', $user->id)->exists();
    }

    // Only the owner mutates the group and its membership.
    public function manage(User $user, Group $group): bool
    {
        return (int) $group->owner_id === (int) $user->id;
    }
}
