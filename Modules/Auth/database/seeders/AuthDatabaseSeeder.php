<?php

namespace Modules\Auth\Database\Seeders;

use App\Enums\ApprovalStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AuthDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $instructor = User::factory()->create([
            'name' => 'Demo Instructor',
            'email' => 'instructor@example.com',
        ]);
        $instructor->forceFill(['approval_status' => ApprovalStatus::Pending])->save();

        $student = User::factory()->create([
            'name' => 'Demo Student',
            'email' => 'student@example.com',
        ]);

        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId(0);

        try {
            if (Role::where('name', 'instructor')->where('guard_name', 'api')->exists()) {
                $instructor->assignRole('instructor');
            }
            if (Role::where('name', 'student')->where('guard_name', 'api')->exists()) {
                $student->assignRole('student');
            }
        } finally {
            setPermissionsTeamId($previousTeamId);
        }
    }
}
