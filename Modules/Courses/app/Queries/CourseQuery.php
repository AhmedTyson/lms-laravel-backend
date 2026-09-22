<?php

namespace Modules\Courses\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
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
                AllowedFilter::callback('search', fn (Builder $builder, string $term) => $this->applySearch($builder, $term)),
                AllowedFilter::partial('category'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('instructor_id'),
                AllowedFilter::callback('published_at', fn (Builder $builder, string $date) => $builder->whereDate('published_at', $date)),
                AllowedFilter::callback('archived_at', fn (Builder $builder, string $date) => $builder->whereDate('archived_at', $date)),
            ])
            ->allowedSorts(...['title', 'created_at', 'published_at'])
            ->defaultSort('-created_at')
            ->paginate($params['per_page'] ?? 10);
    }

    private function applySearch(Builder $builder, string $term): void
    {
        $builder->where(fn (Builder $nested) => $nested
            ->where('title', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%"));
    }
}
