<?php

namespace App\Models;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartnerCompany extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'name',
        'industry',
        'available_positions',
        'required_documents',
        'address',
        'contact_person',
        'contact_email',
        'contact_phone',
        'intern_slot_limit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(InternshipApplication::class);
    }

    public function supervisors(): HasMany
    {
        return $this->hasMany(Supervisor::class);
    }

    public function availablePositionsList(): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $this->available_positions))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }

    public function requiredDocumentsList(): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $this->required_documents))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }

    public function getAuditLabel(): string
    {
        return (string) $this->name;
    }
}
