<?php

namespace Modules\Assignments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Courses\Models\Course;
use Modules\Progress\Models\ComponentCompletion;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id', 'title', 'description', 'due_date',
        'resubmission_allowed', 'max_score', 'passing_threshold',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
            'resubmission_allowed' => 'boolean',
            'max_score' => 'decimal:2',
            'passing_threshold' => 'decimal:2',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class)->orderBy('attempt_number');
    }

    public function completions(): MorphMany
    {
        return $this->morphMany(ComponentCompletion::class, 'component', 'component_type', 'component_id');
    }
}
