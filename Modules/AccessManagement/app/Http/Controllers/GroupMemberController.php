<?php

namespace Modules\AccessManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\AccessManagement\Http\Requests\StoreGroupMemberRequest;
use Modules\AccessManagement\Models\Group;
use Modules\AccessManagement\Services\GroupService;
use Modules\AccessManagement\Transformers\GroupMemberResource;
use Modules\AccessManagement\Transformers\GroupResource;

class GroupMemberController extends Controller
{
    public function store(StoreGroupMemberRequest $request, Group $group, GroupService $service): JsonResponse
    {
        $member = $service->addMember($group, $request->validated('user_id'));

        return ApiResponse::success('Member added.', new GroupMemberResource($member), 201);
    }

    public function destroy(Group $group, int $user, GroupService $service): JsonResponse
    {
        $group = $service->removeMember($group, $user);

        return ApiResponse::success('Member removed.', new GroupResource($group));
    }
}
