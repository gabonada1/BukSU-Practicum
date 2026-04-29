<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;

use App\Http\Controllers\Concerns\AuthorizesTenantPermissions;
use App\Http\Controllers\Concerns\InteractsWithTenantRouting;
use App\Http\Controllers\Concerns\RecordsTenantAudit;
use App\Http\Requests\SupervisorRequest;
use App\Models\Supervisor;
use App\Models\TenantUser;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class SupervisorController extends Controller
{
    use AuthorizesTenantPermissions, InteractsWithTenantRouting, RecordsTenantAudit;

    public function store(SupervisorRequest $request, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        $this->authorizeTenantPermission('user.create', $tenant);

        $data = $request->validated();

        $this->ensureEmailIsAvailable($data['email']);

        $supervisor = Supervisor::query()->create($data + [
            'is_active' => true,
            'email_verified_at' => now(),
            'registered_at' => now(),
        ]);
        $this->auditTenantActivity($request, 'created company supervisor', $supervisor, null, $supervisor->toArray());

        return $this->redirectToTenantRoute(
            $request,
            $tenant,
            'admin.dashboard',
            ['section' => 'supervisors'],
            'Company supervisor added.'
        );
    }

    public function update(SupervisorRequest $request, CurrentTenant $currentTenant, Supervisor $supervisor): RedirectResponse
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        $this->authorizeTenantPermission('user.update', $tenant);

        $data = $request->validated();

        $this->ensureEmailIsAvailable($data['email'], $supervisor->getKey());

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $data['suspended_at'] = $data['is_active'] ? null : now();
        $data['email_verified_at'] = $request->boolean('email_verified')
            ? ($supervisor->email_verified_at ?? now())
            : null;
        unset($data['email_verified']);

        $oldValues = $supervisor->toArray();
        $supervisor->update($data);
        $this->auditTenantActivity($request, 'updated company supervisor', $supervisor, $oldValues, $supervisor->fresh()->toArray());

        return $this->redirectToTenantRoute(
            $request,
            $tenant,
            'admin.dashboard',
            ['section' => 'supervisors'],
            'Company supervisor updated.'
        );
    }

    protected function ensureEmailIsAvailable(string $email, ?int $ignoreSupervisorId = null): void
    {
        $emailTaken = TenantUser::query()
            ->when($ignoreSupervisorId, fn ($query) => $query->whereKeyNot($ignoreSupervisorId))
            ->where('email', $email)
            ->exists();

        if ($emailTaken) {
            throw ValidationException::withMessages([
                'email' => 'This email is already being used by another university portal account.',
            ]);
        }
    }
}
