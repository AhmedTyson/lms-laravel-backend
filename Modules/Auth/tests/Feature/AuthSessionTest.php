<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
}
