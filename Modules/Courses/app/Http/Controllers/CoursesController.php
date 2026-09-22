<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Courses\Http\Requests\RetrieveAllCoursesRequest;
use Modules\Courses\Http\Resources\CourseResource;
use Modules\Courses\Models\Course;

class CoursesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getAllCourses(RetrieveAllCoursesRequest $request): JsonResponse
    {
        $filters = $request->safe()->except(['page', 'per_page']);

        $courses = Course::with('instructor')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['category'] ?? null, fn ($q, $c) => $q->inCategory($c))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->withStatus($s))
            ->when($filters['instructor_id'] ?? null, fn ($q, $i) => $q->taughtBy($i))
            ->when($filters['published_at'] ?? null, fn ($q, $d) => $q->publishedOn($d))
            ->when($filters['archived_at'] ?? null, fn ($q, $d) => $q->archivedOn($d))
            ->paginate($request->validated('per_page', 10));

        return ApiResponse::data(CourseResource::collection($courses));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('courses::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('courses::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('courses::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
