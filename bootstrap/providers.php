<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\TelescopeServiceProvider;
use Propaganistas\LaravelPhone\PhoneServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    TelescopeServiceProvider::class,
    PhoneServiceProvider::class,
];
