<?php

namespace Modules\Auth\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use Exception;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Modules\Auth\Exceptions\OAuthNotConfiguredException;
use Modules\Auth\Http\Requests\ForgotPasswordRequest;
use Modules\Auth\Http\Requests\GoogleCallbackRequest;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Http\Requests\ResetPasswordRequest;
use Modules\Auth\Http\Requests\VerifyEmailRequest;
use Modules\Auth\Services\GoogleAuthService;
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

    public function googleRedirect(GoogleAuthService $service): JsonResponse
    {
        try {
            return response()->json(['url' => $service->redirectUrl()]);
        } catch (OAuthNotConfiguredException) {
            return ApiResponse::error('Google OAuth is not configured.', 'OAUTH_NOT_CONFIGURED', 503);
        }
    }

    public function googleCallback(GoogleCallbackRequest $request, GoogleAuthService $service): JsonResponse
    {
        try {
            $user = $service->handle($request->validated('role', 'student'));
        } catch (OAuthNotConfiguredException) {
            return ApiResponse::error('Google OAuth is not configured.', 'OAUTH_NOT_CONFIGURED', 503);
        } catch (Exception) {
            return ApiResponse::error('Invalid Google authorization code.', 'INVALID_GOOGLE_CODE', 422);
        }

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
