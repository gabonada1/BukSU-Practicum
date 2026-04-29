<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;

use App\Http\Controllers\Concerns\InteractsWithTenantRouting;
use App\Support\Security\RbacMatrix;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class TenantRbacController extends Controller
{
    use InteractsWithTenantRouting;

    public function index(CurrentTenant $currentTenant): View
    {
        $tenant = $currentTenant->tenant();
        abort_unless($tenant, 404);

        $admin = Auth::guard('tenant_admin')->user();
        Gate::forUser($admin)->authorize('manage-tenant-users');

        $definitions = array_intersect_key(RbacMatrix::definitions(), RbacMatrix::defaultTenantMatrix());
        $roles = RbacMatrix::tenantRoles();
        $matrix = data_get($tenant->settings, 'rbac.matrix', RbacMatrix::defaultTenantMatrix());

        return view('tenant.rbac.index', [
            'tenant' => $tenant,
            'pageTitle' => 'RBAC | '.$tenant->name,
            'roles' => $roles,
            'definitions' => $definitions,
            'matrix' => RbacMatrix::normalize($matrix, $roles, $definitions),
            'saveAction' => route('tenant.admin.rbac.update'),
            'resetAction' => route('tenant.admin.rbac.reset'),
        ]);
    }

    public function update(Request $request, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->tenant();
        abort_unless($tenant, 404);

        $admin = Auth::guard('tenant_admin')->user();
        Gate::forUser($admin)->authorize('manage-tenant-users');

        $definitions = array_intersect_key(RbacMatrix::definitions(), RbacMatrix::defaultTenantMatrix());
        $roles = RbacMatrix::tenantRoles();
        $matrix = RbacMatrix::normalize($request->input('permissions', []), $roles, $definitions);

        $settings = $tenant->settings ?? [];
        $settings['rbac']['matrix'] = $matrix;
        $tenant->forceFill(['settings' => $settings])->save();

        return $this->redirectToTenantRoute($request, $tenant, 'admin.rbac.index', status: 'Tenant role permissions saved.');
    }

    public function reset(Request $request, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->tenant();
        abort_unless($tenant, 404);

        $admin = Auth::guard('tenant_admin')->user();
        Gate::forUser($admin)->authorize('manage-tenant-users');

        $settings = $tenant->settings ?? [];
        unset($settings['rbac']);
        $tenant->forceFill(['settings' => $settings])->save();

        return $this->redirectToTenantRoute($request, $tenant, 'admin.rbac.index', status: 'Tenant role permissions reset to defaults.');
    }
}
