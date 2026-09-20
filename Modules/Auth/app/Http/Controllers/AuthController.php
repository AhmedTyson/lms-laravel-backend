<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Http\Requests\VerifyEmailRequest;
use Modules\Auth\Services\RegistrationService;
use Modules\Auth\Transformers\UserResource;

class AuthController extends Controller
{
    /**
     * Register a new Student or Instructor.
     */
    public function register(RegisterRequest $request, RegistrationService $service): JsonResponse
    {
        $user = $service->create(
            $request->safe()->except(['role', 'phone_country']),
            $request->validated('role')
        );

        return ApiResponse::success('Registration successful. Please verify your email address.', new UserResource($user), 201);
    }

    /**
     * Authenticate and issue JWT bearer token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $token = auth('api')->attempt($request->only('email', 'password'));

        if (! $token) {
            return ApiResponse::error('Invalid credentials.', 'INVALID_CREDENTIALS', 401);
        }

        return ApiResponse::jwt($token, new UserResource($request->user('api')));
    }

    /**
     * Verify user email via signed token parameters.
     */
    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $user = User::findOrFail($request->validated('id'));

        $signature = hash_hmac('sha256', $request->validated('id').$request->validated('hash').$request->validated('expires'), config('app.key'));

        if (! hash_equals($signature, (string) $request->validated('signature')) || $request->validated('expires') < now()->timestamp) {
            return ApiResponse::error('Invalid or expired verification link.', 'INVALID_SIGNATURE', 400);
        }

        if (! hash_equals(sha1($user->getEmailForVerification()), (string) $request->validated('hash'))) {
            return ApiResponse::error('Invalid email verification hash.', 'INVALID_HASH', 400);
        }

        if ($user->hasVerifiedEmail()) {
            return ApiResponse::success('Email is already verified.', new UserResource($user));
        }

        $user->markEmailAsVerified();

        return ApiResponse::success('Email verified successfully.', new UserResource($user));
    }
}
