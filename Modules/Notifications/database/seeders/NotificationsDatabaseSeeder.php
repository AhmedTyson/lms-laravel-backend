<?php

namespace Modules\Notifications\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Notifications\Models\Notification;

class NotificationsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create();

        Notification::factory()->create(['user_id' => $user->id]);
    }
}
