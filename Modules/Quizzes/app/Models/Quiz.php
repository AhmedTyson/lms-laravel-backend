<?php

namespace Modules\Quizzes\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Courses\Models\Course;
use Modules\Progress\Models\ComponentCompletion;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id', 'title', 'opens_at', 'closes_at',
        'max_attempts', 'passing_threshold',
    ];

    protected function casts(): array
    {
        return [
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
            'passing_threshold' => 'decimal:2',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }

    public function completions(): MorphMany
    {
        return $this->morphMany(ComponentCompletion::class, 'component', 'component_type', 'component_id');
    }

    public function totalPoints(): float
    {
        return (float) $this->questions()->sum('points');
    }
}
