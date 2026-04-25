<?php

namespace App\Models;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class TenantUser extends Authenticatable
{
    use Notifiable, UsesTenantConnection;

    protected $table = 'tenant_users';

    protected $fillable = [
        'role',
        'name',
        'first_name',
        'last_name',
        'student_number',
        'email',
        'password',
        'must_change_password',
        'program',
        'course_id',
        'required_hours',
        'completed_hours',
        'status',
        'partner_company_id',
        'position',
        'department',
        'is_active',
        'suspended_at',
        'email_verified_at',
        'email_verification_token',
        'verification_sent_at',
        'registered_at',
        'registered_via_self_service',
        'password_reset_code',
        'password_reset_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_token',
        'password_reset_code',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'required_hours' => 'decimal:2',
            'completed_hours' => 'decimal:2',
            'is_active' => 'boolean',
            'suspended_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'verification_sent_at' => 'datetime',
            'registered_at' => 'datetime',
            'registered_via_self_service' => 'boolean',
            'password_reset_expires_at' => 'datetime',
        ];
    }

    public function scopeRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }

    public function partnerCompany(): BelongsTo
    {
        return $this->belongsTo(PartnerCompany::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(StudentRequirement::class, 'student_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(InternshipApplication::class, 'student_id');
    }

    public function hourLogs(): HasMany
    {
        return $this->hasMany(OjtHourLog::class, 'student_id');
    }

    public function getFullNameAttribute(): string
    {
        $name = trim(implode(' ', array_filter([
            $this->first_name,
            $this->last_name,
        ])));

        return $name !== '' ? $name : (string) $this->name;
    }

    public function getAuditLabel(): string
    {
        return match ($this->role) {
            'student' => trim($this->full_name) ?: (string) $this->email,
            'supervisor' => (string) ($this->name ?: $this->email),
            default => (string) ($this->name ?: $this->email),
        };
    }

    public function canAccessPortal(): bool
    {
        if (! $this->is_active || $this->suspended_at) {
            return false;
        }

        if ($this->role === 'admin') {
            return true;
        }

        return ! is_null($this->email_verified_at);
    }

    public function accountStatusLabel(): string
    {
        if ($this->suspended_at || ! $this->is_active) {
            return 'suspended';
        }

        if ($this->role !== 'admin' && ! $this->email_verified_at) {
            return 'pending verification';
        }

        return 'active';
    }
}
