<?php

namespace Modules\Auth\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Modules\Auth\Http\Requests\ForgotPasswordRequest;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Http\Requests\ResetPasswordRequest;
use Modules\Auth\Http\Requests\VerifyEmailRequest;
use Modules\Auth\Transformers\UserResource;

class AuthController extends Controller
{
    /**
     * Register a new Student or Instructor.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $role = $request->validated('role');

        $user = User::create($request->safe()->except(['role', 'phone_country']));

        $user->forceFill([
            'manager_id' => null, // Student & unapproved instructor have manager_id = null (RULE-002)
            'approval_status' => $role === 'instructor' ? ApprovalStatus::Pending : null, // Instructors start as 'pending'; students have no approval lifecycle (SCOPE-004)
        ])->save();

        $user->assignRoleIfExists($role);

        event(new Registered($user));

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

        return ApiResponse::jwt($token, new UserResource(auth('api')->user()));
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

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return ApiResponse::success('If your email is registered, you will receive a password reset link shortly.');
        }

        return ApiResponse::error('Unable to send password reset link.', 'RESET_LINK_FAILED', 400);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return ApiResponse::success('Password reset successfully.');
        }

        return ApiResponse::error('Invalid or expired password reset token.', 'INVALID_RESET_TOKEN', 400);
    }

    public function googleRedirect(): JsonResponse
    {
        return response()->json([
            'url' => 'https://accounts.google.com/o/oauth2/v2/auth?client_id=mock-client-id&redirect_uri=mock-callback',
        ]);
    }

    public function googleCallback(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
            'role' => ['nullable', 'string', 'in:student,instructor'],
        ]);

        // Mock Google user retrieval for Phase 7 API contract
        $role = $request->input('role', 'student');

        $user = User::firstOrCreate(
            ['email' => 'google_user_'.Str::random(6).'@gmail.com'],
            [
                'name' => 'Google User',
                'password' => Hash::make(Str::random(16)),
                'email_verified_at' => now(),
            ]
        );

        $user->forceFill([
            'approval_status' => $role === 'instructor' ? ApprovalStatus::Pending : null,
        ])->save();

        return ApiResponse::jwt(auth('api')->login($user), new UserResource($user));
    }

    /**
     * Invalidate (blacklist) current JWT.
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return ApiResponse::success('Successfully logged out.');
    }

    public function refresh(): JsonResponse
    {
        return ApiResponse::jwt(auth('api')->refresh());
    }

    public function me(): JsonResponse
    {
        return ApiResponse::data(new UserResource(auth('api')->user()));
    }
}
