<?php

use Illuminate\Support\Facades\Route;
use Modules\AccessManagement\Http\Controllers\GrantController;

Route::post('grants', [GrantController::class, 'store'])->middleware(['auth:api', 'throttle:access-grants']);
Route::post('revokes', [GrantController::class, 'revoke'])->middleware(['auth:api', 'throttle:access-grants']);
