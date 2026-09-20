<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;
use Modules\Auth\Http\Controllers\InstructorApprovalController;

Route::prefix('auth')->group(function () {
    // Unauthenticated Guest Routes
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:auth-register');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth-login');
    Route::post('verify-email', [AuthController::class, 'verifyEmail'])->middleware('throttle:auth-verify');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:auth-password');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:auth-password');

    // Google OAuth (Socialite)
    Route::get('google/redirect', [AuthController::class, 'googleRedirect'])->middleware('throttle:auth-oauth');
    Route::post('google/callback', [AuthController::class, 'googleCallback'])->middleware('throttle:auth-oauth');

    // JWT Authenticated Routes (auth:api)
    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->middleware('throttle:api');
        Route::post('refresh', [AuthController::class, 'refresh'])->middleware('throttle:api');
        Route::get('me', [AuthController::class, 'me'])->middleware('throttle:api');

        // Admin Instructor Approval (SCOPE-004, RULE-010)
        Route::post('instructors/{id}/approve', [InstructorApprovalController::class, 'approve'])->middleware('throttle:api');
    });
});
