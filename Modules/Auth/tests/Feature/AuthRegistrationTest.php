<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_egyptian_and_international_phone_validation(): void
    {
        $user = new User;

        // Test local Egyptian mobile normalization
        $user->phone_number = '01012345678';
        $this->assertEquals('+201012345678', $user->phone_number);

        $user->phone_number = '01198765432';
        $this->assertEquals('+201198765432', $user->phone_number);

        // Test international E.164 preservation
        $user->phone_number = '+966501234567';
        $this->assertEquals('+966501234567', $user->phone_number);
    }

    public function test_student_registration_creates_account(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Student John',
            'email' => 'student@example.com',
            'phone_number' => '01012345678',
            'role' => 'student',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.approval_status', null)
            ->assertJsonPath('data.phone_number', '+201012345678');

        $this->assertDatabaseHas('users', [
            'email' => 'student@example.com',
            'manager_id' => null,
            'approval_status' => null,
        ]);
    }

    public function test_instructor_registration_sets_pending_approval(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Instructor Jane',
            'email' => 'instructor@example.com',
            'role' => 'instructor',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.approval_status', 'pending');

        $this->assertDatabaseHas('users', [
            'email' => 'instructor@example.com',
            'manager_id' => null,
            'approval_status' => 'pending',
        ]);
    }

    public function test_registration_assigns_seeded_roles_under_global_team_and_blocks_privilege_injection(): void
    {
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'instructor', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);

        $this->postJson('/api/auth/register', [
            'name' => 'Admin Attacker',
            'email' => 'evil@example.com',
            'role' => 'admin',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertStatus(422);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Seeded Student',
            'email' => 'seeded@example.com',
            'phone_number' => '01012345678',
            'role' => 'student',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'manager_id' => 1,
            'approval_status' => 'approved',
            'email_verified_at' => now()->toIso8601String(),
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'seeded@example.com')->firstOrFail();
        $this->assertNull($user->manager_id);
        $this->assertNull($user->approval_status);
        $this->assertNull($user->email_verified_at);

        setPermissionsTeamId(0);
        $this->assertTrue($user->hasRole('student', 'api'));

        // Normalized duplicate phone rejected
        $this->postJson('/api/auth/register', [
            'name' => 'Duplicate Phone',
            'email' => 'dup-phone@example.com',
            'phone_number' => '+201012345678',
            'role' => 'student',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertStatus(422);

        // Case-insensitive duplicate email rejected on SQLite via pre-validation normalization
        $this->postJson('/api/auth/register', [
            'name' => 'Duplicate Email Case',
            'email' => 'SEEDED@EXAMPLE.COM',
            'role' => 'student',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertStatus(422);
    }

    public function test_verify_email_prevents_user_id_enumeration_and_rejects_expired_links(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'verify@example.com']);

        $badExisting = $this->postJson('/api/auth/verify-email', [
            'id' => $user->id,
            'hash' => str_repeat('a', 40),
            'expires' => time() + 600,
            'signature' => 'invalid',
        ]);
        $badMissing = $this->postJson('/api/auth/verify-email', [
            'id' => 999999,
            'hash' => str_repeat('a', 40),
            'expires' => time() + 600,
            'signature' => 'invalid',
        ]);

        $badExisting->assertStatus(400)->assertJsonPath('error_code', 'INVALID_SIGNATURE');
        $badMissing->assertStatus(400)->assertJsonPath('error_code', 'INVALID_SIGNATURE');

        $expired = time() - 60;
        $hash = sha1($user->getEmailForVerification());
        $expiredSig = hash_hmac('sha256', $user->id.$hash.$expired, config('app.key'));

        $this->postJson('/api/auth/verify-email', [
            'id' => $user->id,
            'hash' => $hash,
            'expires' => $expired,
            'signature' => $expiredSig,
        ])->assertStatus(400)->assertJsonPath('error_code', 'INVALID_SIGNATURE');
    }
}
