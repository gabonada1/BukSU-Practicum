<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicket extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'tenant_id',
        'tenant_name',
        'requester_id',
        'requester_name',
        'requester_email',
        'subject',
        'category',
        'priority',
        'status',
        'message',
        'superadmin_response',
        'resolved_by',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(CentralSuperadmin::class, 'resolved_by');
    }

    public function getAuditLabel(): string
    {
        return '#'.$this->getKey().' '.$this->subject;
    }
}
