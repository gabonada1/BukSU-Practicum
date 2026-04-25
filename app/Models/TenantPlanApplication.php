<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantPlanApplication extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'college_name',
        'contact_name',
        'contact_email',
        'contact_phone',
        'admin_email',
        'selected_plan',
        'preferred_subdomain',
        'preferred_domain',
        'notes',
        'payment_status',
        'payment_amount',
        'payment_currency',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'stripe_subscription_id',
        'stripe_customer_email',
        'paid_at',
        'status',
        'tenant_id',
        'reviewed_by',
        'reviewed_at',
        'approval_notes',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(CentralSuperadmin::class, 'reviewed_by');
    }

    public function getAuditLabel(): string
    {
        return $this->college_name;
    }

    public function isPaid(): bool
    {
        return in_array($this->payment_status, ['paid', 'manual_test'], true);
    }

    public function canBeApproved(): bool
    {
        return $this->isPaid()
            && ! $this->tenant_id
            && in_array($this->status, ['submitted', 'pending_approval'], true);
    }
}
