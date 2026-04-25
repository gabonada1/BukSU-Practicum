<?php

namespace App\Support\Security;

use App\Models\Tenant;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuditLogger
{
    public static function log(
        string $actorType,
        int|string|null $actorId,
        ?string $actorName,
        string $action,
        mixed $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?Request $request = null
    ): void {
        $tenant = self::tenantFor($subject);

        Log::info('tenant_audit', [
            'tenant_id' => $tenant?->getKey(),
            'tenant_name' => $tenant?->name,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'actor_name' => $actorName,
            'action' => $action,
            'subject_type' => $subject instanceof Model ? $subject::class : null,
            'subject_id' => $subject instanceof Model ? $subject->getKey() : null,
            'subject_label' => method_exists($subject, 'getAuditLabel') ? $subject->getAuditLabel() : null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'url' => $request?->fullUrl(),
        ]);
    }

    protected static function tenantFor(mixed $subject): ?Tenant
    {
        if ($subject instanceof Tenant) {
            return $subject;
        }

        if ($subject instanceof Model && filled($subject->getAttribute('tenant_id'))) {
            return Tenant::query()->find($subject->getAttribute('tenant_id'));
        }

        return rescue(fn () => app(CurrentTenant::class)->tenant(), report: false);
    }
}
