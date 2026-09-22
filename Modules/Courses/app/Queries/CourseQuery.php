<?php

namespace Modules\Courses\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Courses\Models\Course;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CourseQuery
{
    /** @param array<string, mixed> $params */
    public function paginate(array $params): LengthAwarePaginator
    {
        return QueryBuilder::for(Course::class)
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
            ->paginate($params['per_page'] ?? 10);
    }
}
