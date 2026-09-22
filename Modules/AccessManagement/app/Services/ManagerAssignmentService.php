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
            // Cycle-safe upward walk: depth-capped, visited set.
            $seen = [$newManager->id => true];
            $current = $newManager;

            for ($depth = 0; $depth < 32 && $current !== null && $current->manager_id !== null; $depth++) {
                if ($current->manager_id === $user->id) {
                    throw new CircularManagerAssignmentException("Assigning user {$newManager->id} as manager to user {$user->id} creates a circular chain.");
                }

                $current = User::find($current->manager_id);

                if ($current !== null && isset($seen[$current->id])) {
                    break;
                }

                if ($current !== null) {
                    $seen[$current->id] = true;
                }
            }

            $user->manager_id = $newManager->id;
            $user->save();

            ManagerDepth::recomputeSubtree($user);
        });
    }
}
