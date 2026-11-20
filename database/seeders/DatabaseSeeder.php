<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Modules\AccessManagement\Database\Seeders\AccessManagementDatabaseSeeder;
use Modules\Assignments\Database\Seeders\AssignmentsDatabaseSeeder;
use Modules\Auth\Database\Seeders\AuthDatabaseSeeder;
use Modules\Courses\Database\Seeders\CoursesDatabaseSeeder;
use Modules\Enrollment\Database\Seeders\EnrollmentDatabaseSeeder;
use Modules\Notifications\Database\Seeders\NotificationsDatabaseSeeder;
use Modules\Progress\Database\Seeders\ProgressDatabaseSeeder;
use Modules\Quizzes\Database\Seeders\QuizzesDatabaseSeeder;
use Modules\Reporting\Database\Seeders\ReportingDatabaseSeeder;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed order contract (ADR-009 in spec §6.3): the root Admin is ALWAYS
     * created first — it is the root of the authority tree (manager_id NULL).
     * Credentials come from .env, never hardcoded. Idempotent: safe to re-run.
     */
    public function run(): void
    {
        $isDev = app()->environment(['local', 'testing']);
        $adminEmail = env('ADMIN_SEED_EMAIL', $isDev ? 'admin@example.com' : null);
        $adminPassword = env('ADMIN_SEED_PASSWORD', $isDev ? 'ChangeMe123!' : null);

        if (! $adminEmail || ! $adminPassword) {
            throw new RuntimeException('ADMIN_SEED_EMAIL and ADMIN_SEED_PASSWORD must be set outside local/testing.');
        }

        // manager_id / approval_status columns land in Phase 9; the extra
        // attributes below activate automatically once migrated.
        $extra = ['email_verified_at' => now()];
        if (Schema::hasColumn('users', 'manager_id')) {
            $extra['manager_id'] = null;
        }

        $admin = User::unguarded(fn () => User::firstOrCreate(
            ['email' => $adminEmail],
            array_merge([
                'name' => 'Root Admin',
                'password' => Hash::make($adminPassword),
            ], $extra)
        ));

        if (Schema::hasTable('roles') && Schema::hasTable('permissions')) {
            $previousTeamId = getPermissionsTeamId();
            setPermissionsTeamId(0);

            try {
                foreach (['admin', 'instructor', 'student'] as $roleName) {
                    Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'api']);
                }

                $permissions = [
                    Permission::firstOrCreate(['name' => 'approve-instructors', 'guard_name' => 'api']),
                    Permission::firstOrCreate(['name' => 'users.manage_roles', 'guard_name' => 'api']),
                ];

                $admin->assignRole('admin');
                $admin->givePermissionTo($permissions);
            } finally {
                setPermissionsTeamId($previousTeamId);
            }
        }

        // Module seeders run AFTER the admin, in FK dependency order.
        $this->call([
            AuthDatabaseSeeder::class,
            AccessManagementDatabaseSeeder::class,
            CoursesDatabaseSeeder::class,
            EnrollmentDatabaseSeeder::class,
            AssignmentsDatabaseSeeder::class,
            QuizzesDatabaseSeeder::class,
            ProgressDatabaseSeeder::class,
            NotificationsDatabaseSeeder::class,
            ReportingDatabaseSeeder::class,
        ]);
    }
}
