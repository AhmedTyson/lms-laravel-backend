<?php

namespace Modules\Auth\Services;

use App\Enums\ApprovalStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Modules\Auth\Exceptions\OAuthAccountConflictException;
use Modules\Auth\Exceptions\OAuthNotConfiguredException;
use RuntimeException;
use Spatie\Permission\Models\Role;

class GoogleAuthService
{
    public function redirectUrl(): string
    {
        $this->ensureConfigured();

        return Socialite::driver('google')->stateless()->redirect()->getTargetUrl();
    }

    public function handle(string $role = 'student'): User
    {
        $this->ensureConfigured();

        // Socialite reads `code` from the request input (JSON body included).
        $googleUser = Socialite::driver('google')->stateless()->user();

        $rawClaims = is_array($googleUser->user ?? null) ? $googleUser->user : [];
        if (
            (array_key_exists('email_verified', $rawClaims) && $rawClaims['email_verified'] !== true) ||
            (array_key_exists('verified_email', $rawClaims) && $rawClaims['verified_email'] !== true)
        ) {
            throw new RuntimeException('Google account email is not verified.');
        }

        $rawEmail = $googleUser->getEmail();
        $email = is_string($rawEmail) ? mb_strtolower(trim($rawEmail)) : '';

        if ($email === '') {
            throw new RuntimeException('Google account exposes no email address.');
        }

        $googleId = method_exists($googleUser, 'getId') ? (string) $googleUser->getId() : null;

        $user = $googleId ? User::where('google_id', $googleId)->first() : null;

        if (! $user) {
            $existing = User::where('email', $email)->first();

            if ($existing) {
                if (! $existing->hasVerifiedEmail() || $existing->suspended_at !== null) {
                    throw new OAuthAccountConflictException('Existing unverified or suspended account cannot be auto-linked via Google OAuth.');
                }

                if ($googleId && $existing->google_id === null) {
                    $existing->forceFill(['google_id' => $googleId])->save();
                }

                return $existing;
            }

            $user = DB::transaction(function () use ($googleUser, $email, $googleId, $role): User {
                $user = User::create([
                    'name' => $googleUser->getName() ?: 'Google User',
                    'email' => $email,
                    'password' => Hash::make(Str::random(32)),
                ]);

                // Google-verified email replaces our verification step; instructors still pend (SCOPE-004).
                $user->forceFill([
                    'google_id' => $googleId,
                    'manager_id' => null,
                    'email_verified_at' => now(),
                    'approval_status' => $role === 'instructor' ? ApprovalStatus::Pending : null,
                ])->save();

                $roleModel = Role::where('name', $role)->where('guard_name', 'api')->first();
                if ($roleModel) {
                    $previousTeamId = getPermissionsTeamId();
                    setPermissionsTeamId(0);

                    try {
                        $user->assignRole($roleModel);
                    } finally {
                        setPermissionsTeamId($previousTeamId);
                    }
                }

                return $user;
            });
        }

        return $user;
    }

    private function ensureConfigured(): void
    {
        if (! config('services.google.client_id')) {
            throw new OAuthNotConfiguredException('Google OAuth credentials missing.');
        }
    }
}
