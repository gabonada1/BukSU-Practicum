<?php

namespace App\Http\Controllers\Concerns;

use App\Support\Security\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait RecordsTenantAudit
{
    protected function auditTenantActivity(
        Request $request,
        string $action,
        mixed $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): void {
        [$actorType, $actorId, $actorName] = $this->tenantAuditActor();

        AuditLogger::log(
            $actorType,
            $actorId,
            $actorName,
            $action,
            $subject,
            $oldValues,
            $newValues,
            $request,
        );
    }

    protected function tenantAuditActor(): array
    {
        if ($actor = Auth::guard('tenant_admin')->user()) {
            return ['tenant_admin', $actor->getKey(), $actor->name];
        }

        if ($actor = Auth::guard('supervisor')->user()) {
            return ['supervisor', $actor->getKey(), $actor->name];
        }

        if ($actor = Auth::guard('student')->user()) {
            return ['student', $actor->getKey(), $actor->full_name];
        }

        return ['system', null, null];
    }
}
