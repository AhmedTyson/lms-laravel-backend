<?php

namespace Modules\AccessManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\AccessManagement\Exceptions\NotSubordinateException;
use Modules\AccessManagement\Exceptions\PermissionCeilingException;
use Modules\AccessManagement\Http\Requests\RevokeGrantRequest;
use Modules\AccessManagement\Http\Requests\StoreGrantRequest;
use Modules\AccessManagement\Services\DelegationService;
use Modules\AccessManagement\Transformers\GrantResource;

class GrantController extends Controller
{
    public function store(StoreGrantRequest $request, DelegationService $service): JsonResponse
    {
        try {
            $grant = $service->record($request->validated(), $request->user('api'), 'grant');
        } catch (NotSubordinateException|PermissionCeilingException) {
            return ApiResponse::error('Grant not permitted.', 'GRANT_FORBIDDEN', 403);
        }

        return ApiResponse::success('Grant recorded.', new GrantResource($grant), 201);
    }

    public function revoke(RevokeGrantRequest $request, DelegationService $service): JsonResponse
    {
        try {
            $grant = $service->record($request->validated(), $request->user('api'), 'revoke');
        } catch (NotSubordinateException|PermissionCeilingException) {
            return ApiResponse::error('Revoke not permitted.', 'REVOKE_FORBIDDEN', 403);
        }

        return ApiResponse::success('Revoke recorded.', new GrantResource($grant), 201);
    }
}
