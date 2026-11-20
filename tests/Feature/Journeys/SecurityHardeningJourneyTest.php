<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Spatie\Permission\Models\Role;

test('security headers and restrictive CORS are applied on API responses', function () {
    $response = $this->withHeader('Origin', 'https://evil.example')
        ->getJson('/api/health');

    $response->assertStatus(200)
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

    expect($response->headers->get('Access-Control-Allow-Origin'))->not->toBe('*');
});

test('database seeder bootstraps root admin with admin role under team 0', function () {
    $this->seed(DatabaseSeeder::class);

    $admin = User::where('email', 'admin@example.com')->firstOrFail();

    expect(Role::where('name', 'admin')->where('guard_name', 'api')->exists())->toBeTrue();

    setPermissionsTeamId(0);
    expect($admin->hasRole('admin', 'api'))->toBeTrue();
});
