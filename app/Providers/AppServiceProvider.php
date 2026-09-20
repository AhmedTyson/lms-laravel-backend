<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Modules\Assignments\Models\Assignment;
use Modules\Courses\Models\Lesson;
use Modules\Quizzes\Models\Quiz;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // propaganistas/laravel-phone depends on giggsey/libphonenumber-for-php-lite.
        // The wikimedia/composer-merge-plugin regenerates vendor/composer/* on every
        // dump-autoload, stripping manually injected PSR-4 entries. Registering here
        // is the only durable fix that survives composer commands.
        $loader = require base_path('vendor/autoload.php');
        $loader->addPsr4('Propaganistas\\LaravelPhone\\', base_path('vendor/propaganistas/laravel-phone/src/'));
        $loader->addPsr4('libphonenumber\\', base_path('vendor/giggsey/libphonenumber-for-php-lite/src/'));
    }

    public function boot(): void
    {
        Relation::enforceMorphMap([
            'user' => User::class,
            'lesson' => Lesson::class,
            'assignment' => Assignment::class,
            'quiz' => Quiz::class,
        ]);
    }
}
