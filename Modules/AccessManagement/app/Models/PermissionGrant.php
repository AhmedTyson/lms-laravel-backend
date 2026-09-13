<?php

namespace Modules\AccessManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionGrant extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'granter_id', 'grantee_id', 'group_id', 'permission_name',
        'action', 'granted_at', 'revoked_at', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function granter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granter_id');
    }

    public function grantee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'grantee_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function isGrant(): bool
    {
        return $this->action === 'granted';
    }
}
