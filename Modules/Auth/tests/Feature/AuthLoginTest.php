<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in', 'user']);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('error_code', 'INVALID_CREDENTIALS');
    }

    public function test_unverified_login_allowed_until_phase_11_flag(): void
    {
        User::factory()->unverified()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        // Flag off (today): token issued.
        $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'Password123!',
        ])->assertStatus(200)->assertJsonStructure(['access_token']);

        // Flag on (Phase 11 mailer): token withheld.
        config()->set('lms.auth.require_verified', true);

        // Wrong password for unverified user must still return 401 (no pre-auth enumeration oracle).
        $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'WrongPassword123!',
        ])->assertStatus(401)
            ->assertJsonPath('error_code', 'INVALID_CREDENTIALS');

        $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'Password123!',
        ])->assertStatus(403)
            ->assertJsonPath('error_code', 'EMAIL_NOT_VERIFIED');
    }

    public function test_login_rate_limiter_normalizes_email_case_and_handles_non_string_payloads(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'casecheck@example.com',
                'password' => 'WrongPassword!',
            ])->assertStatus(401);
        }

        $this->postJson('/api/auth/login', [
            'email' => 'CaseCheck@EXAMPLE.com',
            'password' => 'WrongPassword!',
        ])->assertStatus(429)
            ->assertJsonPath('error_code', 'TOO_MANY_REQUESTS');

        $this->postJson('/api/auth/login', [
            'email' => ['not-a-string'],
            'password' => ['not-a-string'],
        ])->assertStatus(422);
    }

    public function test_suspended_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'suspended@example.com',
            'password' => Hash::make('Password123!'),
        ]);
        $user->forceFill(['suspended_at' => now()])->save();

        $this->postJson('/api/auth/login', [
            'email' => 'suspended@example.com',
            'password' => 'Password123!',
        ])->assertStatus(403)
            ->assertJsonPath('error_code', 'ACCOUNT_SUSPENDED');
    }
}
