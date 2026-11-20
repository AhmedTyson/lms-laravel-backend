<?php

namespace Modules\AccessManagement\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\AccessManagement\Enums\LedgerAction;
use Modules\AccessManagement\Exceptions\NotSubordinateException;
use Modules\AccessManagement\Exceptions\PermissionCeilingException;
use Modules\AccessManagement\Models\Group;
use Modules\AccessManagement\Models\PermissionGrant;
use Modules\AccessManagement\Models\UserPermission;

// One writer for the append-only ledger: endpoint chooses grant/revoke,
// guards + transaction + sync run once. Ledger vocabulary is granted/revoked.
class DelegationService
{
    public function __construct(private PermissionReadModelSync $sync) {}

    public function record(array $data, User $granter, LedgerAction $action): PermissionGrant
    {
        $ledgerAction = $action->ledgerValue();

        return DB::transaction(function () use ($data, $granter, $ledgerAction) {
            $grantee = User::findOrFail($data['grantee_id']);
            $groupId = (int) $data['group_id'];

            $this->ensureSubordinate($granter, $grantee);
            $this->ensureGroupBoundary($granter, $groupId);
            $this->ensureCeiling($granter, $data['permission_name'], $groupId);

            $grant = PermissionGrant::create([
                'granter_id' => $granter->id,
                'grantee_id' => $grantee->id,
                'group_id' => $groupId,
                'permission_name' => $data['permission_name'],
                'action' => $ledgerAction,
                'granted_at' => $ledgerAction === 'granted' ? now() : null,
                'revoked_at' => $ledgerAction === 'revoked' ? now() : null,
            ]);

            $this->sync->syncFromGrant($grant);

            activity('access-management')
                ->causedBy($granter)
                ->withProperties([
                    'grant_id' => $grant->id,
                    'grantee_id' => $grantee->id,
                    'group_id' => $groupId,
                    'permission_name' => $data['permission_name'],
                    'action' => $ledgerAction,
                ])
                ->log("permission.{$ledgerAction}");

            return $grant;
        });
    }

    // RULE-003: walks grantee chain upward, granter must appear. Depth-capped, cycle-safe.
    private function ensureSubordinate(User $granter, User $grantee): void
    {
        if (! ManagerChain::contains($grantee, $granter->id)) {
            throw new NotSubordinateException("User {$grantee->id} is not subordinate to {$granter->id}.");
        }
    }

    // Granter must own the target group or belong to it while holding users.manage_roles.
    private function ensureGroupBoundary(User $granter, int $groupId): void
    {
        $group = Group::findOrFail($groupId);

        if ((int) $group->owner_id === (int) $granter->id) {
            return;
        }

        $isMember = $group->members()->where('user_id', $granter->id)->exists();
        $canManageRoles = UserPermission::where('user_id', $granter->id)
            ->where('permission_name', 'users.manage_roles')
            ->where(fn ($query) => $query->where('group_id', $groupId)->orWhereNull('group_id'))
            ->exists()
            || $granter->can('users.manage_roles', $group);

        if (! $isMember || ! $canManageRoles) {
            throw new PermissionCeilingException("User {$granter->id} lacks delegation authority in group {$groupId}.");
        }
    }

    // RULE-004: granter must hold permission globally or in the same group.
    private function ensureCeiling(User $granter, string $permission, int $groupId): void
    {
        $holds = UserPermission::where('user_id', $granter->id)
            ->where('permission_name', $permission)
            ->where(fn ($permissionQuery) => $permissionQuery->where('group_id', $groupId)->orWhereNull('group_id'))
            ->exists();

        if (! $holds) {
            throw new PermissionCeilingException("User {$granter->id} does not hold {$permission}.");
        }
    }
}
