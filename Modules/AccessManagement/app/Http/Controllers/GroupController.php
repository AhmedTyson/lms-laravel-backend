<?php

namespace Modules\AccessManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\AccessManagement\Http\Requests\StoreGroupRequest;
use Modules\AccessManagement\Models\Group;
use Modules\AccessManagement\Services\GroupService;
use Modules\AccessManagement\Transformers\GroupResource;

class GroupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $groups = Group::where('owner_id', $request->user('api')->id)
            ->orWhereHas('members', fn ($q) => $q->where('user_id', $request->user('api')->id))
            ->paginate();

        return ApiResponse::data(GroupResource::collection($groups));
    }

    public function store(StoreGroupRequest $request, GroupService $service): JsonResponse
    {
        $group = $service->create($request->validated(), $request->user('api'));

        return ApiResponse::success('Group created.', new GroupResource($group), 201);
    }

    public function show(Group $group): JsonResponse
    {
        Gate::authorize('view', $group);

        return ApiResponse::data(new GroupResource($group));
    }

    public function destroy(Group $group, GroupService $service): JsonResponse
    {
        Gate::authorize('manage', $group);

        $service->delete($group);

        return ApiResponse::success('Group deleted.');
    }
}
