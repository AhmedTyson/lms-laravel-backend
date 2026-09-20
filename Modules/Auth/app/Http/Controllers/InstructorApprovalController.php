<?php

namespace Modules\Auth\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\AccessManagement\Services\ManagerAssignmentService;
use Modules\Auth\Events\InstructorApproved;
use Modules\Auth\Http\Requests\ApproveInstructorRequest;
use Modules\Auth\Transformers\UserResource;

class InstructorApprovalController extends Controller
{
    /**
     * Approve a pending instructor and set manager.
     */
    public function approve(ApproveInstructorRequest $request, int $id, ManagerAssignmentService $managerService): JsonResponse
    {
        $instructor = User::findOrFail($id);

        if ($instructor->approval_status === ApprovalStatus::Approved) {
            return ApiResponse::error('Instructor is already approved.', 'ALREADY_APPROVED', 409);
        }

        $managerId = $request->validated('manager_id') ?? auth('api')->id();
        $manager = User::findOrFail($managerId);

        // Assign manager & recompute subtree depth atomically (ADR-007 / ADR-013)
        $managerService->assign($instructor, $manager);

        $instructor->forceFill([
            'approval_status' => ApprovalStatus::Approved,
        ])->save();

        event(new InstructorApproved($instructor));

        return ApiResponse::success('Instructor approved successfully.', new UserResource($instructor->fresh()));
    }
}
