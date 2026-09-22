<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
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
        $courses = QueryBuilder::for(Course::class)
            ->allowedFilters(...[
                AllowedFilter::callback('search', fn ($query, $term) => $query->where(fn ($where) => $where
                    ->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%"))),
                AllowedFilter::partial('category'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('instructor_id'),
                AllowedFilter::callback('published_at', fn ($query, $date) => $query->whereDate('published_at', $date)),
                AllowedFilter::callback('archived_at', fn ($query, $date) => $query->whereDate('archived_at', $date)),
            ])
            ->allowedSorts(...['title', 'created_at', 'published_at'])
            ->defaultSort('-created_at')
            ->paginate($request->validated('per_page', 10));

        return ApiResponse::data(CourseResource::collection($courses));
    }
}
