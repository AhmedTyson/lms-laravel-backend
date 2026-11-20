<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Support\PhoneNormalizer;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * @property ?string $google_id
 * @property int $token_version
 * @property ?Carbon $suspended_at
 */
#[Fillable(['name', 'email', 'phone_number', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected string $guard_name = 'api';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'approval_status' => ApprovalStatus::class,
            'token_version' => 'integer',
            'suspended_at' => 'datetime',
        ];
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    // Normalises email to lowercase on write so SQLite (case-sensitive) and MySQL behave identically.
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null ? mb_strtolower(trim($value)) : null,
        );
    }

    // Normalises to E.164 on write; invalid input stores null, validation rejects it upstream.
    protected function phoneNumber(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => PhoneNormalizer::toE164($value),
        );
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'ver' => (int) ($this->token_version ?? 1),
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->suspended_at !== null) {
            return false;
        }

        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId(0);

        try {
            return $this->hasRole('admin', 'api');
        } finally {
            setPermissionsTeamId($previousTeamId);
        }
    }
}
