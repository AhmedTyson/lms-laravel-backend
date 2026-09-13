<?php

namespace Modules\AccessManagement\Services;

use Modules\AccessManagement\Models\PermissionGrant;
use Modules\AccessManagement\Models\UserPermission;

// ADR-012 seam: mirrors a single ledger row into the read model. Pure and
// transaction-agnostic — the Phase 5 grant/revoke service MUST call this
// inside the same DB transaction as the PermissionGrant write, so a read-model
// failure rolls back the whole grant/revoke atomically.
class PermissionReadModelSync
{
    public function syncFromGrant(PermissionGrant $grant): void
    {
        if ($grant->isGrant()) {
            UserPermission::updateOrCreate(
                [
                    'user_id' => $grant->grantee_id,
                    'permission_name' => $grant->permission_name,
                    'group_id' => $grant->group_id,
                ],
                ['granted_via_grant_id' => $grant->id]
            );

            return;
        }

        UserPermission::where('user_id', $grant->grantee_id)
            ->where('permission_name', $grant->permission_name)
            ->where('group_id', $grant->group_id)
            ->delete();
    }
}
