<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Http\Resources\UserResource;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\JWTGuard;

class SessionController extends Controller
{
    /**
     * Invalidate (blacklist) current JWT.
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return ApiResponse::success('Successfully logged out.');
    }

    public function refresh(Request $request): JsonResponse
    {
        try {
            /** @var JWTGuard $guard */
            $guard = auth('api');
            $token = $guard->setRequest($request)->getToken();

            if (! $token) {
                return ApiResponse::error('Unauthenticated.', 'UNAUTHENTICATED', 401);
            }

            $payload = $guard->manager()->setRefreshFlow()->decode($token);
            $user = User::find($payload->get('sub'));

            if (! $user || $user->suspended_at !== null || (int) ($payload->get('ver') ?? 1) !== (int) ($user->token_version ?? 1)) {
                return ApiResponse::error('Unauthenticated.', 'UNAUTHENTICATED', 401);
            }

            $newToken = $guard->claims(['ver' => (int) ($user->token_version ?? 1)])->refresh();
            $guard->manager()->setRefreshFlow(false);
            $guard->unsetToken();
        } catch (JWTException) {
            $guard->manager()->setRefreshFlow(false);
            $guard->unsetToken();

            return ApiResponse::error('Unauthenticated.', 'UNAUTHENTICATED', 401);
        }

        return ApiResponse::jwt($newToken);
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::data(new UserResource($request->user('api')));
    }
}
