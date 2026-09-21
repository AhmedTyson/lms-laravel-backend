<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Http\Resources\UserResource;

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

    public function refresh(): JsonResponse
    {
        return ApiResponse::jwt(auth('api')->refresh());
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::data(new UserResource($request->user('api')));
    }
}
