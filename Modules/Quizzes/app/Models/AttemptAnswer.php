<?php

namespace Modules\Quizzes\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttemptAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'attempt_id', 'question_id', 'selected_option_ids',
        'answer_boolean', 'answer_text', 'answer_number', 'is_correct',
    ];

    protected function casts(): array
    {
        return [
            'selected_option_ids' => 'array',
            'answer_boolean' => 'boolean',
            'answer_number' => 'decimal:4',
            'is_correct' => 'boolean',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
