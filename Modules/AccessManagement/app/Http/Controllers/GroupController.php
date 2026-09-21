<?php

namespace Modules\AccessManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\AccessManagement\Http\Requests\StoreGroupRequest;
use Modules\AccessManagement\Models\Group;
use Modules\AccessManagement\Services\GroupService;
use Modules\AccessManagement\Transformers\GroupResource;

class GroupController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::data(GroupResource::collection(Group::paginate()));
    }

    public function store(StoreGroupRequest $request, GroupService $service): JsonResponse
    {
        $group = $service->create($request->validated(), $request->user('api'));

        return ApiResponse::success('Group created.', new GroupResource($group), 201);
    }

    public function show(Group $group): JsonResponse
    {
        return ApiResponse::data(new GroupResource($group));
    }

    public function destroy(Group $group): JsonResponse
    {
        $group->members()->delete();
        $group->delete();

        return ApiResponse::success('Group deleted.');
    }
}
