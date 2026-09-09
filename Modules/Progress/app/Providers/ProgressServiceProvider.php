<?php

namespace Modules\Progress\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Assignments\Models\Assignment;
use Modules\Courses\Models\Lesson;
use Modules\Quizzes\Models\Quiz;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ProgressServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Progress';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'progress';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Define module schedules.
     *
     * @param  $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }

    public function boot(): void
    {
        parent::boot();

        // ADR-010: short component_type values resolve to module models.
        Relation::enforceMorphMap([
            'lesson' => Lesson::class,
            'assignment' => Assignment::class,
            'quiz' => Quiz::class,
        ]);
    }
}
