<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;

use App\Http\Controllers\Concerns\InteractsWithTenantRouting;
use App\Http\Requests\TenantAdminPasswordSetupRequest;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class TenantAdminPasswordSetupController extends Controller
{
    use InteractsWithTenantRouting;

    public function create(CurrentTenant $currentTenant): View|RedirectResponse
    {
        $tenant = $currentTenant->tenant();
        $admin = Auth::guard('tenant_admin')->user();

        abort_unless($tenant && $admin, 404);

        if (! $admin->must_change_password) {
            return redirect()->to($this->tenantRoute($tenant, 'admin.dashboard'));
        }

        return view('tenant.auth.create-password', [
            'tenant' => $tenant,
            'pageTitle' => 'Create Password | '.data_get($tenant->settings, 'branding.portal_title', config('app.name', 'University Practicum')),
            'passwordSetupAction' => $this->tenantRoute($tenant, 'admin.password.setup.store'),
        ]);
    }

    public function store(TenantAdminPasswordSetupRequest $request, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->tenant();
        $admin = Auth::guard('tenant_admin')->user();

        abort_unless($tenant && $admin, 404);

        $data = $request->validated();

        $admin->update([
            'password' => $data['password'],
            'must_change_password' => false,
        ]);

        Auth::guard('tenant_admin')->setUser($admin->fresh());

        return $this->redirectToTenantRoute(
            $request,
            $tenant,
            'admin.dashboard',
            status: 'Password created successfully. Welcome to your university portal.'
        );
    }
}
