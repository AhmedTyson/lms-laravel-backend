<?php

namespace Modules\Auth\Database\Seeders;

use App\Enums\ApprovalStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class AuthDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Demo Instructor',
            'email' => 'instructor@example.com',
        ])->forceFill(['approval_status' => ApprovalStatus::Pending])->save();

        User::factory()->create([
            'name' => 'Demo Student',
            'email' => 'student@example.com',
        ]);
    }
}
