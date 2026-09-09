<?php

namespace Modules\Quizzes\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id', 'prompt', 'type', 'order', 'points',
        'correct_boolean', 'correct_number', 'numeric_tolerance',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'decimal:2',
            'correct_boolean' => 'boolean',
            'correct_number' => 'decimal:4',
            'numeric_tolerance' => 'decimal:4',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order');
    }

    public function acceptedAnswers(): HasMany
    {
        return $this->hasMany(QuestionAcceptedAnswer::class);
    }
}
