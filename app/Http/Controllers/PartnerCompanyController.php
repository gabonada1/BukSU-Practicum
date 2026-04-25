<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesTenantPermissions;
use App\Http\Controllers\Concerns\InteractsWithTenantRouting;
use App\Http\Controllers\Concerns\RecordsTenantAudit;
use App\Models\PartnerCompany;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PartnerCompanyController extends Controller
{
    use AuthorizesTenantPermissions, InteractsWithTenantRouting, RecordsTenantAudit;

    /**
     * @return array<string, mixed>
     */
    protected function validatedPayload(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'available_positions' => ['nullable', 'array'],
            'available_positions.*' => ['string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'intern_slot_limit' => ['required', 'integer', 'min:1'],
        ]);

        $validated['available_positions'] = collect($validated['available_positions'] ?? [])
            ->map(fn ($position) => trim((string) $position))
            ->filter()
            ->unique()
            ->implode(PHP_EOL);

        $validated['required_documents'] = null;

        return $validated;
    }

    public function store(Request $request, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        $this->authorizeTenantPermission('company.manage', $tenant);

        $company = PartnerCompany::query()->create($this->validatedPayload($request) + [
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

    public function update(Request $request, CurrentTenant $currentTenant, PartnerCompany $company): RedirectResponse
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        $this->authorizeTenantPermission('company.manage', $tenant);

        $oldValues = $company->toArray();
        $company->update($this->validatedPayload($request));
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
