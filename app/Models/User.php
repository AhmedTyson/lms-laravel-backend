<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Support\PhoneNormalizer;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

#[Fillable(['name', 'email', 'phone_number', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'approval_status' => ApprovalStatus::class,
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

    // Normalises to E.164 on write; invalid input stores null, validation rejects it upstream.
    protected function phoneNumber(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => PhoneNormalizer::toE164($value),
        );
    }

    // Assigns a Spatie role only when it has been seeded for the api guard.
    public function assignRoleIfExists(string $roleName): bool
    {
        $role = Role::where('name', $roleName)->where('guard_name', 'api')->first();

        if (! $role) {
            return false;
        }

        $this->assignRole($role);

        return true;
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
