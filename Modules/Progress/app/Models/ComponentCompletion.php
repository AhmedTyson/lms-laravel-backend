<?php

namespace Modules\Progress\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Enrollment\Models\Enrollment;

class ComponentCompletion extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id', 'component_type', 'component_id',
        'completed_at', 'is_override', 'overridden_by',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'is_override' => 'boolean',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    // Native polymorphic target (ADR-010). Types resolved via the morph map
    // in ProgressServiceProvider: lesson|assignment|quiz. No type-switches.
    public function component(): MorphTo
    {
        return $this->morphTo();
    }

    public function overrider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overridden_by');
    }
}
