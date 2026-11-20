<?php

namespace Modules\Auth\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\AccessManagement\Services\ManagerAssignmentService;
use Modules\Auth\Events\InstructorApproved;
use Modules\Auth\Http\Requests\ApproveInstructorRequest;
use Modules\Auth\Http\Resources\UserResource;

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

        if ($instructor->approval_status !== ApprovalStatus::Pending) {
            return ApiResponse::error('Target user is not a pending instructor.', 'INVALID_INSTRUCTOR_STATE', 422);
        }

        $managerId = $request->validated('manager_id') ?? $request->user('api')?->id;
        $manager = User::findOrFail($managerId);

        if ($manager->approval_status === ApprovalStatus::Pending || $this->isStudent($manager)) {
            return ApiResponse::error('Assigned manager must be an approved instructor or admin.', 'INVALID_MANAGER', 422);
        }

        DB::transaction(function () use ($instructor, $manager, $managerService): void {
            // Assign manager & recompute subtree depth atomically (ADR-007 / ADR-013)
            $managerService->assign($instructor, $manager);

            $instructor->forceFill([
                'approval_status' => ApprovalStatus::Approved,
            ])->save();
        });

        event(new InstructorApproved($instructor));

        return ApiResponse::success('Instructor approved successfully.', new UserResource($instructor->fresh()));
    }

    private function isStudent(User $user): bool
    {
        if (! Schema::hasTable('roles')) {
            return false;
        }

        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId(0);

        try {
            return $user->hasRole('student', 'api') && ! $user->hasAnyRole(['instructor', 'admin'], 'api');
        } finally {
            setPermissionsTeamId($previousTeamId);
        }
    }
}
