<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
