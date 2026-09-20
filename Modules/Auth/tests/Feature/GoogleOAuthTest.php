<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleOAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_without_config_returns_503(): void
    {
        config()->set('services.google.client_id', null);

        $this->getJson('/api/auth/google/redirect')
            ->assertStatus(503)
            ->assertJsonPath('error_code', 'OAUTH_NOT_CONFIGURED');
    }

    public function test_callback_without_config_returns_503(): void
    {
        config()->set('services.google.client_id', null);

        $this->postJson('/api/auth/google/callback', ['code' => 'any-code'])
            ->assertStatus(503)
            ->assertJsonPath('error_code', 'OAUTH_NOT_CONFIGURED');
    }

    public function test_callback_requires_code(): void
    {
        $this->postJson('/api/auth/google/callback', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_callback_rejects_bad_role(): void
    {
        $this->postJson('/api/auth/google/callback', ['code' => 'any-code', 'role' => 'admin'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('role');
    }
}
