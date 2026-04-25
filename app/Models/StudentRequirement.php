<?php

namespace App\Models;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentRequirement extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'student_id',
        'requirement_name',
        'status',
        'file_path',
        'notes',
        'feedback',
        'submitted_at',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function fileUrl(): ?string
    {
        return $this->file_path ? asset($this->file_path) : null;
    }

    public function getAuditLabel(): string
    {
        return (string) $this->requirement_name;
    }
}
