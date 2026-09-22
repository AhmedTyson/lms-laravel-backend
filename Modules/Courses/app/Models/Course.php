<?php

namespace Modules\Courses\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Courses\Database\Factories\CourseFactory;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'instructor_id', 'title', 'category', 'description',
        'status', 'published_at', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    protected static function newFactory(): CourseFactory
    {
        return CourseFactory::new();
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        $query->where(fn ($w) => $w
            ->where('title', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%"));
    }

    #[Scope]
    protected function inCategory(Builder $query, string $category): void
    {
        $query->where('category', $category);
    }

    #[Scope]
    protected function withStatus(Builder $query, string $status): void
    {
        $query->where('status', $status);
    }

    #[Scope]
    protected function taughtBy(Builder $query, int $instructorId): void
    {
        $query->where('instructor_id', $instructorId);
    }

    #[Scope]
    protected function publishedOn(Builder $query, string $date): void
    {
        $query->whereDate('published_at', $date);
    }

    #[Scope]
    protected function archivedOn(Builder $query, string $date): void
    {
        $query->whereDate('archived_at', $date);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }
}
