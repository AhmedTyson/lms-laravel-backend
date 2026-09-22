<?php

namespace Modules\AccessManagement\Services;

use App\Models\User;

// Single home for manager-chain walks. Both the delegation guard (RULE-003)
// and the reassignment guard share depth cap + visited set here, so the
// traversal rule lives once and reads as a question, not a loop.
class ManagerChain
{
    private const MAX_DEPTH = 32;

    public static function contains(User $start, int $targetId): bool
    {
        $seen = [$start->id => true];
        $current = $start;

        for ($depth = 0; $depth < self::MAX_DEPTH && $current?->manager_id !== null; $depth++) {
            if ($current->manager_id === $targetId) {
                return true;
            }

            $current = User::find($current->manager_id);

            if ($current === null || isset($seen[$current->id])) {
                break;
            }

            $seen[$current->id] = true;
        }

        return false;
    }
}
