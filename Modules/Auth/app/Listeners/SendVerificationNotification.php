<?php

namespace Modules\Auth\Listeners;

use Illuminate\Auth\Events\Registered;

class SendVerificationNotification
{
    public function handle(Registered $event): void
    {
        // Phase 11 — verification mail dispatch goes here.
    }
}
