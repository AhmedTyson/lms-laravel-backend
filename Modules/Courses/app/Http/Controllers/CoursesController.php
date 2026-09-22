<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Courses\Http\Requests\RetrieveAllCoursesRequest;
use Modules\Courses\Http\Resources\CourseResource;
use Modules\Courses\Queries\CourseQuery;

class CoursesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getAllCourses(RetrieveAllCoursesRequest $request, CourseQuery $queries): JsonResponse
    {
        return ApiResponse::data(CourseResource::collection(
            $queries->paginate($request->validated())
        ));
    }
}
