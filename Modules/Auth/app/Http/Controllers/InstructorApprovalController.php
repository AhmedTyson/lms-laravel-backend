<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
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

        if ($instructor->approval_status === 'approved') {
            return response()->json([
                'message' => 'Instructor is already approved.',
                'error_code' => 'ALREADY_APPROVED',
            ], 409);
        }

        $managerId = $request->validated('manager_id') ?? auth('api')->id();
        $manager = User::findOrFail($managerId);

        // Assign manager & recompute subtree depth atomically (ADR-007 / ADR-013)
        $managerService->assign($instructor, $manager);

        $instructor->update([
            'approval_status' => 'approved',
        ]);

        event(new InstructorApproved($instructor));

        return response()->json([
            'message' => 'Instructor approved successfully.',
            'data' => new UserResource($instructor),
        ], 200);
    }
}
