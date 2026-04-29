<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;

use App\Http\Controllers\Concerns\AuthorizesTenantPermissions;
use App\Http\Controllers\Concerns\InteractsWithTenantRouting;
use App\Http\Controllers\Concerns\RecordsTenantAudit;
use App\Http\Requests\PartnerCompanyRequest;
use App\Models\PartnerCompany;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\RedirectResponse;

class PartnerCompanyController extends Controller
{
    use AuthorizesTenantPermissions, InteractsWithTenantRouting, RecordsTenantAudit;

    public function store(PartnerCompanyRequest $request, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        $this->authorizeTenantPermission('company.manage', $tenant);

        $company = PartnerCompany::query()->create($request->payload() + [
            'is_active' => true,
        ]);
        $this->auditTenantActivity($request, 'created partner organization', $company, null, $company->toArray());

        return $this->redirectToTenantRoute(
            $request,
            $tenant,
            'admin.dashboard',
            ['section' => 'companies'],
            'Partner organization added.'
        );
    }

    public function update(PartnerCompanyRequest $request, CurrentTenant $currentTenant, PartnerCompany $company): RedirectResponse
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        $this->authorizeTenantPermission('company.manage', $tenant);

        $oldValues = $company->toArray();
        $company->update($request->payload());
        $this->auditTenantActivity($request, 'updated partner organization', $company, $oldValues, $company->fresh()->toArray());

        return $this->redirectToTenantRoute(
            $request,
            $tenant,
            'admin.dashboard',
            ['section' => 'companies'],
            'Partner organization updated.'
        );
    }
}
