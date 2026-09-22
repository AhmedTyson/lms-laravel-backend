<?php

namespace Modules\Notifications\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Notifications\Models\Notification;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'GradePublished',
            'data' => ['assignment_id' => 1, 'grade' => '85.50'],
            'read_at' => null,
        ];
    }
}
