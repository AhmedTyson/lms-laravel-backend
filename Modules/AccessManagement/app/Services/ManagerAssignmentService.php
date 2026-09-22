<?php

namespace Modules\AccessManagement\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\AccessManagement\Exceptions\CircularManagerAssignmentException;

class ManagerAssignmentService
{
    /**
     * Assign a new manager to a user, checking for cycles and updating subtree depth.
     */
    public function assign(User $user, User $newManager): void
    {
        if ($user->id === $newManager->id) {
            throw new CircularManagerAssignmentException("User {$user->id} cannot be their own manager.");
        }

        DB::transaction(function () use ($user, $newManager) {
            if (ManagerChain::contains($newManager, $user->id)) {
                throw new CircularManagerAssignmentException("Assigning user {$newManager->id} as manager to user {$user->id} creates a circular chain.");
            }

            $user->manager_id = $newManager->id;
            $user->save();

            ManagerDepth::recomputeSubtree($user);
        });
    }
}
