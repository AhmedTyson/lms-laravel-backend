<?php

namespace Modules\Auth\Listeners;

use Modules\Auth\Events\InstructorApproved;

class SendInstructorApprovedNotification
{
    public function handle(InstructorApproved $event): void
    {
        // Phase 11 — notification dispatch goes here.
    }
}
