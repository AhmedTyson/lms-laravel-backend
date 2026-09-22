<?php

namespace Modules\Progress\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Enrollment\Models\Enrollment;
use Modules\Progress\Models\ComponentCompletion;
use Modules\Progress\Models\ProgressRecord;

class ProgressDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $enrollment = Enrollment::first() ?? Enrollment::factory()->create();

        $record = ProgressRecord::factory()->create(['enrollment_id' => $enrollment->id]);

        ComponentCompletion::factory()->create([
            'enrollment_id' => $enrollment->id,
            'component_type' => 'lesson',
            'component_id' => 1,
        ]);

        $record->forceFill(['percent_complete' => '10.00'])->save();
    }
}
