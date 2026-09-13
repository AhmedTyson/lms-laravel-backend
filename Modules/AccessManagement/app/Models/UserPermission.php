<?php

namespace Modules\AccessManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// ADR-012 read model: CURRENT effective grants only. permission_grants stays
// the audit ledger. Rows here are maintained atomically by the grant/revoke
// service (Phase 5 wiring) via PermissionReadModelSync — never written directly.
class UserPermission extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'permission_name', 'group_id', 'granted_via_grant_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function grant(): BelongsTo
    {
        return $this->belongsTo(PermissionGrant::class, 'granted_via_grant_id');
    }
}
