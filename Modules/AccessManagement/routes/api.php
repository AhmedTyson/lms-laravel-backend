<?php

use Illuminate\Support\Facades\Route;
use Modules\AccessManagement\Http\Controllers\GrantController;
use Modules\AccessManagement\Http\Controllers\GroupController;
use Modules\AccessManagement\Http\Controllers\GroupMemberController;

Route::post('grants', [GrantController::class, 'store'])->middleware(['auth:api', 'throttle:access-grants']);
Route::post('revokes', [GrantController::class, 'revoke'])->middleware(['auth:api', 'throttle:access-grants']);

Route::middleware('auth:api')->group(function () {
    Route::apiResource('groups', GroupController::class)->only(['index', 'store', 'show', 'destroy'])->middleware('throttle:api');
    Route::post('groups/{group}/members', [GroupMemberController::class, 'store'])->middleware('throttle:api');
    Route::delete('groups/{group}/members/{user}', [GroupMemberController::class, 'destroy'])->middleware('throttle:api');
});
