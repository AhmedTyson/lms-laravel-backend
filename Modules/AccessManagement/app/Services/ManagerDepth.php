<?php

namespace Modules\AccessManagement\Services;

use App\Models\User;

// ADR-013 seam: maintains the manager_depth cache. manager_id traversal stays
// authoritative for cycle prevention (ADR-007); depth only accelerates reads.
// The Phase 5 reassignment service MUST call recomputeSubtree() for the
// reassigned node inside the same transaction, AFTER the cycle check passes.
// No max-depth cap adopted — depth is advisory-only.
class ManagerDepth
{
    public static function depthFor(?int $managerId): int
    {
        $depth = 0;
        $current = $managerId ? User::find($managerId) : null;

        while ($current) {
            $depth++;
            $current = $current->manager_id ? User::find($current->manager_id) : null;
        }

        return $depth;
    }

    public static function recomputeSubtree(User $root): void
    {
        $root->forceFill(['manager_depth' => self::depthFor($root->manager_id)])->save();

        $queue = [[$root->id, $root->manager_depth]];
        while ($queue !== []) {
            [$parentId, $parentDepth] = array_shift($queue);

            foreach (User::where('manager_id', $parentId)->get() as $child) {
                $child->forceFill(['manager_depth' => $parentDepth + 1])->save();
                $queue[] = [$child->id, $parentDepth + 1];
            }
        }
    }
}
