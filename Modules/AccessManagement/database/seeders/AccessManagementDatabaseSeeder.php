<?php

namespace Modules\AccessManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AccessManagement\Models\Group;
use Modules\AccessManagement\Models\GroupMember;

class AccessManagementDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $group = Group::factory()->create(['name' => 'Demo Cohort']);

        GroupMember::factory()->count(3)->create(['group_id' => $group->id]);
    }
}
