<?php

namespace Modules\AccessManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Modules\AccessManagement\Http\Requests\StoreGroupMemberRequest;
use Modules\AccessManagement\Models\Group;
use Modules\AccessManagement\Services\GroupService;
use Modules\AccessManagement\Transformers\GroupMemberResource;
use Modules\AccessManagement\Transformers\GroupResource;

class GroupMemberController extends Controller
{
    public function store(StoreGroupMemberRequest $request, Group $group, GroupService $service): JsonResponse
    {
        Gate::authorize('manage', $group);

        $member = $service->addMember($group, $request->validated('user_id'));

        return ApiResponse::success('Member added.', new GroupMemberResource($member), 201);
    }

    public function destroy(Group $group, int $user, GroupService $service): JsonResponse
    {
        Gate::authorize('manage', $group);

        try {
            $group = $service->removeMember($group, $user);
        } catch (ModelNotFoundException) {
            return ApiResponse::error('Membership not found.', 'NOT_FOUND', 404);
        }

        return ApiResponse::success('Member removed.', new GroupResource($group));
    }
}
