<?php

namespace App\Models;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternshipApplication extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'student_id',
        'partner_company_id',
        'position_applied',
        'resume_path',
        'endorsement_letter_path',
        'moa_path',
        'clearance_path',
        'student_notes',
        'status',
        'applied_at',
        'deployed_at',
        'admin_feedback',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'applied_at' => 'date',
            'deployed_at' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function partnerCompany(): BelongsTo
    {
        return $this->belongsTo(PartnerCompany::class);
    }

    public function getAuditLabel(): string
    {
        return '#'.$this->getKey().' '.$this->position_applied;
    }
}
