<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesTenantPermissions;
use App\Http\Controllers\Concerns\InteractsWithTenantRouting;
use App\Http\Requests\ModuleRequest;
use App\Models\Module;
use App\Support\Security\AuditLogger;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ModuleController extends Controller
{
    use AuthorizesTenantPermissions, InteractsWithTenantRouting;

    public function index()
    {
        return "THIS IS MODULE INDEX";
    }

    public function store(ModuleRequest $request, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        $this->authorizeTenantPermission('user.update', $tenant);

        $validated = $request->validated();

        $module = Module::query()->create($validated);

        $actor = Auth::guard('tenant_admin')->user();

        if ($actor) {
            AuditLogger::log('tenant_admin', $actor->id, $actor->name, 'created', $module, null, $module->toArray(), $request);
        }

        return $this->redirectToTenantRoute($request, $tenant, 'admin.profile.show', status: 'Module added successfully.');
    }

    public function update(ModuleRequest $request, CurrentTenant $currentTenant, Module $module): RedirectResponse
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        $this->authorizeTenantPermission('user.update', $tenant);

        $validated = $request->validated();

        $oldValues = $module->toArray();
        $module->update($validated);

        $actor = Auth::guard('tenant_admin')->user();

        if ($actor) {
            AuditLogger::log('tenant_admin', $actor->id, $actor->name, 'updated', $module, $oldValues, $module->fresh()->toArray(), $request);
        }

        return $this->redirectToTenantRoute($request, $tenant, 'admin.profile.show', status: 'Module updated.');
    }

    public function destroy(Request $request, CurrentTenant $currentTenant, Module $module): RedirectResponse
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        $this->authorizeTenantPermission('user.update', $tenant);

        $oldValues = $module->toArray();
        $module->delete();

        $actor = Auth::guard('tenant_admin')->user();

        if ($actor) {
            AuditLogger::log('tenant_admin', $actor->id, $actor->name, 'deleted', $module, $oldValues, null, $request);
        }

        return $this->redirectToTenantRoute($request, $tenant, 'admin.profile.show', status: 'Module removed.');
    }
}
