<?php

namespace Modules\Auth\Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class AuthRegistrationTest extends TestCase
{
    /**
     * A basic test example.
     */
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
}
