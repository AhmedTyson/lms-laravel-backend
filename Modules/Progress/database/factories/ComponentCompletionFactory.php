<?php

namespace Modules\Progress\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Enrollment\Models\Enrollment;
use Modules\Progress\Models\ComponentCompletion;

class ComponentCompletionFactory extends Factory
{
    protected $model = ComponentCompletion::class;

    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory(),
            'component_type' => 'lesson',
            'component_id' => 1,
            'completed_at' => now(),
            'is_override' => false,
            'overridden_by' => null,
        ];
    }
}
