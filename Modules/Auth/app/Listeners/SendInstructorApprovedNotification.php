<?php

namespace Modules\Auth\Listeners;

use Modules\Auth\app\Events\InstructorApproved;

class SendInstructorApprovedNotification
{
    public function __construct() {}

    public function handle(InstructorApproved $event): void {}
}
