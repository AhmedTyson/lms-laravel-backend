<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthSessionTest extends TestCase
{
    use RefreshDatabase;

    private function token(): string
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        return $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'Password123!',
        ])->json('access_token');
    }

    public function test_me_returns_authenticated_profile(): void
    {
        $this->withHeaders(['Authorization' => 'Bearer '.$this->token()])
            ->getJson('/api/auth/me')
            ->assertStatus(200)
            ->assertJsonPath('data.email', 'user@example.com');
    }

    public function test_refresh_rotates_token(): void
    {
        $this->withHeaders(['Authorization' => 'Bearer '.$this->token()])
            ->postJson('/api/auth/refresh')
            ->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in']);
    }

    public function test_logout_invalidates_token(): void
    {
        $headers = ['Authorization' => 'Bearer '.$this->token()];

        $this->withHeaders($headers)->postJson('/api/auth/logout')->assertStatus(200);

        $this->withHeaders($headers)->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_me_rejects_guest(): void
    {
        $this->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_jwt_in_query_string_is_rejected(): void
    {
        $jwt = $this->token();

        $this->getJson('/api/auth/me?token='.urlencode($jwt))
            ->assertStatus(401);
    }

    public function test_expired_jwt_can_refresh_within_refresh_ttl_and_cannot_reuse_old_token(): void
    {
        $user = User::factory()->create();

        $expiredJwt = app('tymon.jwt.provider.jwt')->encode([
            'iss' => 'http://localhost',
            'iat' => now()->subMinutes(120)->timestamp,
            'exp' => now()->subMinutes(60)->timestamp,
            'nbf' => now()->subMinutes(120)->timestamp,
            'sub' => $user->id,
            'jti' => 'expired-refreshable-jti',
            'ver' => 1,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$expiredJwt)
            ->getJson('/api/auth/me')
            ->assertStatus(401);

        $refresh = $this->withHeader('Authorization', 'Bearer '.$expiredJwt)
            ->postJson('/api/auth/refresh')
            ->assertStatus(200)
            ->assertJsonStructure(['access_token']);

        // Reusing the refreshed token must fail 401
        $this->withHeader('Authorization', 'Bearer '.$expiredJwt)
            ->postJson('/api/auth/refresh')
            ->assertStatus(401);

        $this->withHeader('Authorization', 'Bearer '.$refresh->json('access_token'))
            ->getJson('/api/auth/me')
            ->assertStatus(200);
    }

    public function test_password_reset_revokes_existing_jwt_and_forgot_password_prevents_enumeration(): void
    {
        Notification::fake();

        $jwt = $this->token();
        $user = User::where('email', 'user@example.com')->firstOrFail();

        $realForgot = $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertStatus(200);
        $fakeForgot = $this->postJson('/api/auth/forgot-password', ['email' => 'missing@example.com'])->assertStatus(200);
        $this->assertSame($realForgot->json('message'), $fakeForgot->json('message'));

        $resetToken = Password::createToken($user);

        $this->postJson('/api/auth/reset-password', [
            'token' => $resetToken,
            'email' => $user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertStatus(200);

        // Token is single-use
        $this->postJson('/api/auth/reset-password', [
            'token' => $resetToken,
            'email' => $user->email,
            'password' => 'AnotherPassword123!',
            'password_confirmation' => 'AnotherPassword123!',
        ])->assertStatus(400);

        // Previously issued JWT is now revoked via token_version increment
        $this->withHeader('Authorization', 'Bearer '.$jwt)
            ->getJson('/api/auth/me')
            ->assertStatus(401);
    }
}
