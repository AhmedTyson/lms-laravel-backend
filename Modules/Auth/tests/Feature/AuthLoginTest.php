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

        $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'Password123!',
        ])->assertStatus(403)
            ->assertJsonPath('error_code', 'EMAIL_NOT_VERIFIED');
    }
}
