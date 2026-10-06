<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonPlan extends Model
{
    use HasUlids;

    protected $fillable = [
        'teacher_id',
        'title',
        'original_path',
        'original_filename',
        'revised_path',
        'revised_filename',
        'status',
        'completed_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id', 'ulid');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by', 'ulid');
    }

    public function currentFilePath(): string
    {
        return $this->revised_path ?? $this->original_path;
    }

    public function currentFilename(): string
    {
        return $this->revised_filename ?? $this->original_filename;
    }
}
