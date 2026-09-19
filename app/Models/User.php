<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

#[Fillable(['name', 'email', 'phone_number', 'password', 'manager_id', 'manager_depth', 'approval_status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    /**
     * Mutator to normalize phone numbers to E.164 (+2010...) before DB insert.
     */
    protected function setPhoneNumberAttribute(?string $value): void
    {
        if (empty($value)) {
            $this->attributes['phone_number'] = null;

            return;
        }

        $clean = preg_replace('/[^\d+]/', '', $value);

        // Convert local Egyptian mobile (010..., 011..., 012..., 015...) to +201...
        if (preg_match('/^01[0125]\d{8}$/', $clean)) {
            $clean = '+20'.substr($clean, 1);
        }

        $this->attributes['phone_number'] = $clean;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
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
