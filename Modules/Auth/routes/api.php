<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;
use Modules\Auth\Http\Controllers\InstructorApprovalController;
use Modules\Auth\Http\Controllers\OAuthController;
use Modules\Auth\Http\Controllers\PasswordController;
use Modules\Auth\Http\Controllers\SessionController;

Route::prefix('auth')->group(function () {
    // Unauthenticated Guest Routes
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:auth-register');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth-login');
    Route::post('verify-email', [AuthController::class, 'verifyEmail'])->middleware('throttle:auth-verify');
    Route::post('forgot-password', [PasswordController::class, 'forgotPassword'])->middleware('throttle:auth-password');
    Route::post('reset-password', [PasswordController::class, 'resetPassword'])->middleware('throttle:auth-password');

    // Google OAuth (Socialite)
    Route::get('google/redirect', [OAuthController::class, 'googleRedirect'])->middleware('throttle:auth-oauth');
    Route::post('google/callback', [OAuthController::class, 'googleCallback'])->middleware('throttle:auth-oauth');

    // JWT Authenticated Routes (auth:api)
    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [SessionController::class, 'logout'])->middleware('throttle:api');
        Route::post('refresh', [SessionController::class, 'refresh'])->middleware('throttle:api');
        Route::get('me', [SessionController::class, 'me'])->middleware('throttle:api');

        // Admin Instructor Approval (SCOPE-004, RULE-010)
        Route::post('instructors/{id}/approve', [InstructorApprovalController::class, 'approve'])->middleware('throttle:api');
    });
});
