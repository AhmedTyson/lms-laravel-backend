<?php

namespace Modules\AccessManagement\Policies;

use App\Models\User;
use Modules\AccessManagement\Models\Group;

class GroupPolicy
{
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
