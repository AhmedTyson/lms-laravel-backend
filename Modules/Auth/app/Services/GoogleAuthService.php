<?php

namespace Modules\Auth\Services;

use App\Enums\ApprovalStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Modules\Auth\Exceptions\OAuthNotConfiguredException;
use RuntimeException;

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

        $email = $googleUser->getEmail();

        if (! $email) {
            throw new RuntimeException('Google account exposes no email address.');
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = User::create([
                'name' => $googleUser->getName() ?: 'Google User',
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
            ]);

            // Google-verified email replaces our verification step; instructors still pend (SCOPE-004).
            $user->forceFill([
                'email_verified_at' => now(),
                'approval_status' => $role === 'instructor' ? ApprovalStatus::Pending : null,
            ])->save();
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
