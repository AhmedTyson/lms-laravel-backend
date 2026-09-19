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
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
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
