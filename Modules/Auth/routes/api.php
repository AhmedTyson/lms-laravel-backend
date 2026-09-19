<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;
use Modules\Auth\Http\Controllers\InstructorApprovalController;

Route::prefix('auth')->group(function () {
    // Unauthenticated Guest Routes
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);

    // Google OAuth (Socialite)
    Route::get('google/redirect', [AuthController::class, 'googleRedirect']);
    Route::post('google/callback', [AuthController::class, 'googleCallback']);

    // JWT Authenticated Routes (auth:api)
    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);

        // Admin Instructor Approval (SCOPE-004, RULE-010)
        Route::post('instructors/{id}/approve', [InstructorApprovalController::class, 'approve']);
    });
});
