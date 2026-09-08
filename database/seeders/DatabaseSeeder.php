<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

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
        // manager_id / approval_status columns land in Phase 9; the extra
        // attributes below activate automatically once migrated.
        $extra = ['email_verified_at' => now()];
        if (Schema::hasColumn('users', 'manager_id')) {
            $extra['manager_id'] = null;
        }

        User::unguarded(fn () => User::firstOrCreate(
            ['email' => env('ADMIN_SEED_EMAIL', 'admin@example.com')],
            array_merge([
                'name' => 'Root Admin',
                'password' => Hash::make(env('ADMIN_SEED_PASSWORD', 'ChangeMe123!')),
            ], $extra)
        ));

        // Module seeders (Phase 9+) register below, AFTER the admin.
        // $this->call([...]);
    }
}
