<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Exceptions\OAuthAccountConflictException;
use Modules\Auth\Exceptions\OAuthNotConfiguredException;
use Modules\Auth\Http\Requests\GoogleCallbackRequest;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Services\GoogleAuthService;

class OAuthController extends Controller
{
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
        } catch (OAuthAccountConflictException) {
            return ApiResponse::error('Account email must be verified before linking Google OAuth.', 'ACCOUNT_LINKING_REQUIRED', 409);
        } catch (Exception) {
            return ApiResponse::error('Invalid Google authorization code.', 'INVALID_GOOGLE_CODE', 422);
        }

        return ApiResponse::jwt(auth('api')->login($user), new UserResource($user));
    }
}
