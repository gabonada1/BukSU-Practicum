<?php

namespace App\Models;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OjtHourLog extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'student_id',
        'log_date',
        'hours',
        'activity',
        'status',
        'supervisor_name',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'log_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function getAuditLabel(): string
    {
        return 'Hour log #'.$this->getKey().' - '.$this->log_date?->format('M d, Y');
    }
}
