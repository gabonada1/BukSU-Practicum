<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait ScopesTenantUserRole
{
    abstract protected static function tenantUserRole(): string;

    public function initializeScopesTenantUserRole(): void
    {
        $this->attributes['role'] ??= static::tenantUserRole();
    }

    protected static function bootScopesTenantUserRole(): void
    {
        static::addGlobalScope('tenant_user_role', function (Builder $query): void {
            $query->where($query->qualifyColumn('role'), static::tenantUserRole());
        });

        static::creating(function ($model): void {
            $model->role = static::tenantUserRole();
        });
    }
}
