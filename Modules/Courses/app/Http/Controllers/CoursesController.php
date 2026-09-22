<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Courses\Http\Requests\RetrieveAllCoursesRequest;
use Modules\Courses\Http\Resources\CourseResource;
use Modules\Courses\Models\Course;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CoursesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getAllCourses(RetrieveAllCoursesRequest $request): JsonResponse
    {
        $courses = QueryBuilder::for(Course::with('instructor'))
            ->allowedFilters(...[
                AllowedFilter::callback('search', fn ($q, $s) => $q->where(fn ($w) => $w
                    ->where('title', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%"))),
                AllowedFilter::partial('category'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('instructor_id'),
                AllowedFilter::callback('published_at', fn ($q, $d) => $q->whereDate('published_at', $d)),
                AllowedFilter::callback('archived_at', fn ($q, $d) => $q->whereDate('archived_at', $d)),
            ])
            ->allowedSorts(...['title', 'created_at', 'published_at'])
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
