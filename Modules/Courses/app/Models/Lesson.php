<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Courses\Database\Factories\LessonFactory;
use Modules\Progress\Models\ComponentCompletion;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = ['course_id', 'title', 'content_reference', 'order'];

    protected static function newFactory(): LessonFactory
    {
        return LessonFactory::new();
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function completions(): MorphMany
    {
        return $this->morphMany(ComponentCompletion::class, 'component', 'component_type', 'component_id');
    }
}
