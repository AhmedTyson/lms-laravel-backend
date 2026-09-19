<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Http\Requests\VerifyEmailRequest;
use Modules\Auth\Transformers\UserResource;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    /**
     * Register a new Student or Instructor.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $role = $request->validated('role');

        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone_number' => $request->validated('phone_number'),
            'password' => $request->validated('password'),
            'manager_id' => null, // Student & unapproved instructor have manager_id = null (RULE-002)
            'approval_status' => $role === 'instructor' ? 'pending' : null, // SCOPE-004
        ]);

        // Assign Spatie Role if role exists
        if (class_exists(Role::class) && Role::where('name', $role)->where('guard_name', 'api')->exists()) {
            $user->assignRole(Role::findByName($role, 'api'));
        }

        event(new Registered($user));

        return response()->json([
            'message' => 'Registration successful. Please verify your email address.',
            'data' => new UserResource($user),
        ], 201);
    }

    /**
     * Authenticate and issue JWT bearer token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials.',
                'error_code' => 'INVALID_CREDENTIALS',
            ], 401);
        }

        /** @var User $user */
        $user = auth('api')->user();

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => new UserResource($user),
        ], 200);
    }

    /**
     * Verify user email via signed token parameters.
     */
    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $user = User::findOrFail($request->validated('id'));

        if (! hash_equals(sha1($user->getEmailForVerification()), (string) $request->validated('hash'))) {
            return response()->json([
                'message' => 'Invalid email verification hash.',
                'error_code' => 'INVALID_HASH',
            ], 400);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email is already verified.',
                'data' => new UserResource($user),
            ], 200);
        }

        $user->markEmailAsVerified();

        return response()->json([
            'message' => 'Email verified successfully.',
            'data' => new UserResource($user),
        ], 200);
    }

    /**
     * Send password reset link to user.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'If your email is registered, you will receive a password reset link shortly.',
            ], 200);
        }

        return response()->json([
            'message' => 'Unable to send password reset link.',
            'error_code' => 'RESET_LINK_FAILED',
        ], 400);
    }

    /**
     * Reset user password using token.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

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
            return response()->json([
                'message' => 'Password reset successfully.',
            ], 200);
        }

        return response()->json([
            'message' => 'Invalid or expired password reset token.',
            'error_code' => 'INVALID_RESET_TOKEN',
        ], 400);
    }

    /**
     * Redirect to Google OAuth consent.
     */
    public function googleRedirect(): JsonResponse
    {
        return response()->json([
            'url' => 'https://accounts.google.com/o/oauth2/v2/auth?client_id=mock-client-id&redirect_uri=mock-callback',
        ], 200);
    }

    /**
     * Callback for Google OAuth.
     */
    public function googleCallback(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
            'role' => ['nullable', 'string', 'in:student,instructor'],
        ]);

        // Mock Google user retrieval for Phase 7 API contract
        $email = 'google_user_'.Str::random(6).'@gmail.com';
        $role = $request->input('role', 'student');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Google User',
                'password' => Hash::make(Str::random(16)),
                'email_verified_at' => now(),
                'approval_status' => $role === 'instructor' ? 'pending' : null,
            ]
        );

        $token = auth('api')->login($user);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => new UserResource($user),
        ], 200);
    }

    /**
     * Invalidate (blacklist) current JWT.
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return response()->json([
            'message' => 'Successfully logged out.',
        ], 200);
    }

    /**
     * Refresh active JWT.
     */
    public function refresh(): JsonResponse
    {
        $newToken = auth('api')->refresh();

        return response()->json([
            'access_token' => $newToken,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ], 200);
    }

    /**
     * Get authenticated user profile.
     */
    public function me(): JsonResponse
    {
        return response()->json([
            'data' => new UserResource(auth('api')->user()),
        ], 200);
    }
}
