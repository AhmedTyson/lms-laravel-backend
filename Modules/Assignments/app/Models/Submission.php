<?php

namespace Modules\Assignments\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Enrollment\Models\Enrollment;

class Submission extends Model
{
    use HasFactory;

    // Privileged grading/outcome fields (grade, graded_by, graded_at, status, is_late)
    // are set via forceFill inside authorized grading services only (handbook 10 §3).
    protected $fillable = [
        'assignment_id', 'enrollment_id', 'attempt_number', 'file_path',
        'content', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'is_late' => 'boolean',
            'grade' => 'decimal:2',
            'graded_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
