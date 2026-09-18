<?php

namespace Modules\Auth\Listeners;

use Modules\Auth\app\Events\InstructorApproved;

class SendInstructorApprovedNotification
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(InstructorApproved $event): void {}
}
