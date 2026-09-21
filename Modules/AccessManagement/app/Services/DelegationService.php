<?php

namespace Modules\AccessManagement\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\AccessManagement\Exceptions\NotSubordinateException;
use Modules\AccessManagement\Exceptions\PermissionCeilingException;
use Modules\AccessManagement\Models\PermissionGrant;
use Modules\AccessManagement\Models\UserPermission;

// One writer for the append-only ledger: endpoint chooses grant/revoke,
// guards + transaction + sync run once. Ledger vocabulary is granted/revoked.
class DelegationService
{
    public function __construct(private PermissionReadModelSync $sync) {}

    public function record(array $data, User $granter, string $action): PermissionGrant
    {
        $ledgerAction = match ($action) {
            'grant' => 'granted',
            'revoke' => 'revoked',
            default => throw new InvalidArgumentException("Unknown ledger action {$action}."),
        };

        return DB::transaction(function () use ($data, $granter, $ledgerAction) {
            $grantee = User::findOrFail($data['grantee_id']);

            $this->ensureSubordinate($granter, $grantee);
            $this->ensureCeiling($granter, $data['permission_name'], $data['group_id']);

            $grant = PermissionGrant::create([
                'granter_id' => $granter->id,
                'grantee_id' => $grantee->id,
                'group_id' => $data['group_id'],
                'permission_name' => $data['permission_name'],
                'action' => $ledgerAction,
                'granted_at' => $ledgerAction === 'granted' ? now() : null,
                'revoked_at' => $ledgerAction === 'revoked' ? now() : null,
            ]);

            $this->sync->syncFromGrant($grant);

            return $grant;
        });
    }

    // RULE-003: walks grantee chain upward, granter must appear.
    private function ensureSubordinate(User $granter, User $grantee): void
    {
        $current = $grantee;

        while ($current !== null && $current->manager_id !== null) {
            if ($current->manager_id === $granter->id) {
                return;
            }

            $current = $current->manager;
        }

        throw new NotSubordinateException("User {$grantee->id} is not subordinate to {$granter->id}.");
    }

    // RULE-004: granter must hold permission globally or in the same group.
    private function ensureCeiling(User $granter, string $permission, int $groupId): void
    {
        $holds = UserPermission::where('user_id', $granter->id)
            ->where('permission_name', $permission)
            ->where(fn ($q) => $q->where('group_id', $groupId)->orWhereNull('group_id'))
            ->exists();

        if (! $holds) {
            throw new PermissionCeilingException("User {$granter->id} does not hold {$permission}.");
        }
    }
}
