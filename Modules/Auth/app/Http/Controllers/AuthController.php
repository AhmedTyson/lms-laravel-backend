<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Http\Requests\VerifyEmailRequest;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Services\RegistrationService;

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
        $user = User::where('email', $request->validated('email'))->first();

        if ($user && ! $user->hasVerifiedEmail() && config('lms.auth.require_verified')) {
            return ApiResponse::error('Email not verified. Check your inbox.', 'EMAIL_NOT_VERIFIED', 403);
        }

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
        $fields = $request->validated();
        $user = User::findOrFail($fields['id']);

        if (! $this->hasValidSignature($fields)) {
            return ApiResponse::error('Invalid or expired verification link.', 'INVALID_SIGNATURE', 400);
        }

        if (! $this->hasValidHash($user, $fields['hash'])) {
            return ApiResponse::error('Invalid email verification hash.', 'INVALID_HASH', 400);
        }

        if ($user->hasVerifiedEmail()) {
            return ApiResponse::success('Email is already verified.', new UserResource($user));
        }

        $user->markEmailAsVerified();

        return ApiResponse::success('Email verified successfully.', new UserResource($user));
    }

    /** @param array{id: mixed, hash: mixed, expires: mixed, signature: mixed} $fields */
    private function hasValidSignature(array $fields): bool
    {
        $expected = hash_hmac('sha256', $fields['id'].$fields['hash'].$fields['expires'], config('app.key'));

        return hash_equals($expected, (string) $fields['signature'])
            && $fields['expires'] >= now()->timestamp;
    }

    private function hasValidHash(User $user, mixed $hash): bool
    {
        return hash_equals(sha1($user->getEmailForVerification()), (string) $hash);
    }
}
