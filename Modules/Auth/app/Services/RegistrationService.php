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

        // Students carry no approval lifecycle (SCOPE-004); unapproved instructors park at null manager (RULE-002).
        $user->forceFill([
            'manager_id' => null,
            'approval_status' => $this->initialApprovalStatus($role),
        ])->save();

        $this->assignRoleIfExists($user, $role);

        event(new Registered($user));

        return $user;
    }

    private function initialApprovalStatus(string $role): ?ApprovalStatus
    {
        return $role === 'instructor' ? ApprovalStatus::Pending : null;
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
