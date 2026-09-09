<?php

namespace Modules\Progress\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Enrollment\Models\Enrollment;

class ProgressRecord extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['enrollment_id', 'percent_complete', 'completed_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'percent_complete' => 'decimal:2',
            'completed_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }
}
