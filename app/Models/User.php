<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Propaganistas\LaravelPhone\PhoneNumber;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Throwable;
use Tymon\JWTAuth\Contracts\JWTSubject;

#[Fillable(['name', 'email', 'phone_number', 'password', 'manager_id', 'manager_depth', 'approval_status'])]
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
        ];
    }

    // Normalises to E.164 on write; invalid input passes through for validation to reject.
    protected function phoneNumber(): Attribute
    {
        return Attribute::make(
            set: function (?string $value): ?string {
                if (empty($value)) {
                    return null;
                }

                $phone = new PhoneNumber($value, config('lms.phone.default_country', 'EG'));

                try {
                    return $phone->isValid() ? $phone->formatE164() : $value;
                } catch (Throwable) {
                    return $value;
                }
            },
        );
    }

    // Assigns a Spatie role only when it has been seeded for the api guard.
    public function assignRoleIfExists(string $role): void
    {
        if (Role::where('name', $role)->where('guard_name', 'api')->exists()) {
            $this->assignRole(Role::findByName($role, 'api'));
        }
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
