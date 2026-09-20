<?php

namespace Modules\Auth\Services;

use App\Enums\ApprovalStatus;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Spatie\Permission\Models\Role;

class RegistrationService
{
    public function create(array $attributes, string $role): User
    {
        $user = User::create($attributes);

        $user->forceFill([
            'manager_id' => null, // Student & unapproved instructor have manager_id = null (RULE-002)
            'approval_status' => $role === 'instructor' ? ApprovalStatus::Pending : null, // Instructors start as 'pending'; students have no approval lifecycle (SCOPE-004)
        ])->save();

        $this->assignRoleIfExists($user, $role);

        event(new Registered($user));

        return $user;
    }

    private function assignRoleIfExists(User $user, string $roleName): bool
    {
        $role = Role::where('name', $roleName)->where('guard_name', 'api')->first();

        if (! $role) {
            return false;
        }

        $user->assignRole($role);

        return true;
    }
}
