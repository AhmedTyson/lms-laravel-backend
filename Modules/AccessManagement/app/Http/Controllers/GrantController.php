<?php

namespace Modules\AccessManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Modules\AccessManagement\Exceptions\NotSubordinateException;
use Modules\AccessManagement\Exceptions\PermissionCeilingException;
use Modules\AccessManagement\Http\Requests\DelegationRequest;
use Modules\AccessManagement\Services\DelegationService;
use Modules\AccessManagement\Transformers\GrantResource;

class GrantController extends Controller
{
    public function store(DelegationRequest $request, DelegationService $service): JsonResponse
    {
        try {
            $grant = $service->record($request->validated(), $request->user('api'), 'grant');
        } catch (NotSubordinateException|PermissionCeilingException) {
            return ApiResponse::error('Grant not permitted.', 'GRANT_FORBIDDEN', 403);
        } catch (ModelNotFoundException) {
            return ApiResponse::error('Grantee not found.', 'NOT_FOUND', 404);
        }

        return ApiResponse::success('Grant recorded.', new GrantResource($grant), 201);
    }

    public function revoke(DelegationRequest $request, DelegationService $service): JsonResponse
    {
        try {
            $grant = $service->record($request->validated(), $request->user('api'), 'revoke');
        } catch (NotSubordinateException|PermissionCeilingException) {
            return ApiResponse::error('Revoke not permitted.', 'REVOKE_FORBIDDEN', 403);
        } catch (ModelNotFoundException) {
            return ApiResponse::error('Grantee not found.', 'NOT_FOUND', 404);
        }

        return ApiResponse::success('Revoke recorded.', new GrantResource($grant), 201);
    }
}
