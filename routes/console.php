<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ADR-014: nightly audit-trail prune (90-day window via ACTIVITY_LOG_RETENTION_DAYS).
Schedule::command('activitylog:prune')->daily();
