<?php

namespace Modules\Progress\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Enrollment\Models\Enrollment;
use Modules\Progress\Models\ProgressRecord;

class ProgressRecordFactory extends Factory
{
    protected $model = ProgressRecord::class;

    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory(),
            'percent_complete' => '0.00',
            'completed_at' => null,
        ];
    }
}
