<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Spatie\Permission\Models\Role;
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

    public function test_callback_blocks_unverified_account_takeover_and_assigns_role_on_new_user(): void
    {
        config()->set('services.google.client_id', 'test-client-id');
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'api']);

        $victim = User::factory()->unverified()->create(['email' => 'victim@example.com']);

        $mockCollisionUser = new class
        {
            public array $user = ['email_verified' => true];

            public function getEmail(): string
            {
                return 'victim@example.com';
            }

            public function getName(): string
            {
                return 'Attacker';
            }

            public function getId(): string
            {
                return 'google-sub-victim';
            }
        };

        $provider1 = Mockery::mock();
        $provider1->shouldReceive('stateless')->andReturnSelf();
        $provider1->shouldReceive('user')->andReturn($mockCollisionUser);
        $socialite1 = Mockery::mock();
        $socialite1->shouldReceive('driver')->with('google')->andReturn($provider1);
        Socialite::swap($socialite1);

        $this->postJson('/api/auth/google/callback', ['code' => 'valid-code'])
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'ACCOUNT_LINKING_REQUIRED');

        $mockNewUser = new class
        {
            public array $user = ['email_verified' => true];

            public function getEmail(): string
            {
                return 'newgoogle@example.com';
            }

            public function getName(): string
            {
                return 'New Google Student';
            }

            public function getId(): string
            {
                return 'google-sub-new';
            }
        };

        $provider2 = Mockery::mock();
        $provider2->shouldReceive('stateless')->andReturnSelf();
        $provider2->shouldReceive('user')->andReturn($mockNewUser);
        $socialite2 = Mockery::mock();
        $socialite2->shouldReceive('driver')->with('google')->andReturn($provider2);
        Socialite::swap($socialite2);

        $this->postJson('/api/auth/google/callback', ['code' => 'valid-code-2', 'role' => 'student'])
            ->assertStatus(200)
            ->assertJsonStructure(['access_token']);

        $created = User::where('email', 'newgoogle@example.com')->firstOrFail();
        setPermissionsTeamId(0);
        $this->assertTrue($created->hasRole('student', 'api'));
    }
}
