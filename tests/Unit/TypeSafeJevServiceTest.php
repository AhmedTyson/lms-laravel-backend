<?php

namespace Tests\Unit;

use App\Services\TypeSafeJevService;
use Tests\TestCase;

class TypeSafeJevServiceTest extends TestCase
{
    public function test_jev_service_executes_system_one_decisions(): void
    {
        if (! config('services.typesafe.api_key')) {
            $this->markTestSkipped('TYPESAFE_API_KEY not configured.');
        }

        $service = new TypeSafeJevService;

        $noulScore = $service->noul(
            'The course content covers advanced Laravel dependency injection and architecture design.',
            'Is this course content relevant to software development?'
        );

        $this->assertGreaterThan(0.8, $noulScore);

        $choiceResult = $service->choice(
            'I need help resetting my password because my email link expired.',
            'Route this user support ticket to the best handling queue',
            [
                'auth_support' => 'Authentication, password reset, login issues',
                'billing_support' => 'Payments, invoices, subscriptions',
                'tech_support' => 'Bugs, crashes, site downtime',
            ]
        );

        $this->assertEquals('auth_support', $choiceResult['choice']);
        $this->assertGreaterThan(0.8, $choiceResult['confidence']);
    }
}
