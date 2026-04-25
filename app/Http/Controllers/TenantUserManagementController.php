<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesTenantPermissions;
use App\Http\Controllers\Concerns\InteractsWithTenantRouting;
use App\Http\Controllers\Concerns\RecordsTenantAudit;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\TenantAdmin;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TenantUserManagementController extends Controller
{
    use AuthorizesTenantPermissions, InteractsWithTenantRouting, RecordsTenantAudit;

    public function update(Request $request, CurrentTenant $currentTenant, string $type, int $id): RedirectResponse
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        Gate::forUser(Auth::guard('tenant_admin')->user())->authorize('manage-tenant-users');

        $user = $this->resolveUser($type, $id);

        $data = $request->validate([
            'role' => ['required', Rule::in(['admin', 'supervisor', 'student'])],
            'is_active' => ['required', Rule::in(['0', '1'])],
        ]);

        $isActive = (bool) $data['is_active'];
        $targetRole = $data['role'];
        $currentRole = $this->roleFromType($type);
        $currentAdmin = Auth::guard('tenant_admin')->user();
        $oldValues = $user->toArray();

        $this->authorizeTenantPermission('user.update', $tenant);

        if ($currentRole !== $targetRole) {
            $this->authorizeTenantPermission('user.role.assign', $tenant);
        }

        if ($user->is_active !== $isActive) {
            $this->authorizeTenantPermission('user.suspend', $tenant);
        }

        if ($currentRole === 'admin' && $currentAdmin && $currentAdmin->getKey() === $user->getKey()) {
            if (! $isActive || $targetRole !== 'admin') {
                throw ValidationException::withMessages([
                    'role' => 'You cannot suspend or reassign the internship coordinator account that is currently signed in.',
                ]);
            }
        }

        DB::transaction(function () use ($user, $currentRole, $targetRole, $isActive) {
            if ($currentRole === $targetRole) {
                $user->update([
                    'is_active' => $isActive,
                    'suspended_at' => $isActive ? null : now(),
                ]);

                return;
            }

            if ($user instanceof Student && ($user->requirements()->exists() || $user->hourLogs()->exists())) {
                throw ValidationException::withMessages([
                    'role' => 'Students with forms, requirements, or progress records cannot be reassigned to another role.',
                ]);
            }

            $payload = $this->mapPayloadForRole($user, $targetRole, $isActive);

            $this->modelClassForRole($targetRole)::query()->create($payload);
            $user->delete();
        });
        $this->auditTenantActivity($request, 'updated portal account', $user, $oldValues, [
            'role' => $targetRole,
            'is_active' => $isActive,
        ]);

        return $this->redirectToTenantRoute(
            $request,
            $tenant,
            'admin.dashboard',
            ['section' => 'users'],
            'College portal account updated.'
        );
    }

    protected function resolveUser(string $type, int $id): Model
    {
        return match ($type) {
            'admin' => TenantAdmin::query()->findOrFail($id),
            'supervisor' => Supervisor::query()->findOrFail($id),
            default => Student::query()->findOrFail($id),
        };
    }

    protected function roleFromType(string $type): string
    {
        return in_array($type, ['admin', 'supervisor'], true) ? $type : 'student';
    }

    protected function modelClassForRole(string $role): string
    {
        return match ($role) {
            'admin' => TenantAdmin::class,
            'supervisor' => Supervisor::class,
            default => Student::class,
        };
    }

    protected function mapPayloadForRole(Model $user, string $targetRole, bool $isActive): array
    {
        $hashedPassword = $user->getRawOriginal('password');
        $supportsVerification = $user instanceof Student || $user instanceof Supervisor;
        $common = [
            'email' => $user->email,
            'password' => $hashedPassword,
            'is_active' => $isActive,
            'suspended_at' => $isActive ? null : now(),
            'email_verified_at' => $supportsVerification ? ($user->email_verified_at ?? now()) : now(),
            'registered_at' => $supportsVerification ? ($user->registered_at ?? now()) : now(),
        ];

        $name = $user instanceof Student ? $user->full_name : $user->name;

        return match ($targetRole) {
            'admin' => $common + [
                'name' => $name,
            ],
            'supervisor' => $common + [
                'name' => $name,
                'position' => $user instanceof Supervisor ? $user->position : 'Company Supervisor',
                'department' => $user instanceof Supervisor ? $user->department : ($user instanceof Student ? $user->program : null),
                'partner_company_id' => $user->partner_company_id ?? null,
                'registered_via_self_service' => false,
            ],
            default => $common + [
                'student_number' => $user instanceof Student ? $user->student_number : 'TMP-'.Str::upper(Str::random(8)),
                'first_name' => $user instanceof Student ? $user->first_name : Str::of($name)->before(' ')->toString(),
                'last_name' => $user instanceof Student
                    ? $user->last_name
                    : (Str::of($name)->after(' ')->trim()->toString() ?: 'User'),
                'program' => $user instanceof Student ? $user->program : null,
                'required_hours' => $user instanceof Student ? $user->required_hours : 486,
                'completed_hours' => $user instanceof Student ? $user->completed_hours : 0,
                'status' => $user instanceof Student ? $user->status : 'pending',
                'partner_company_id' => $user->partner_company_id ?? null,
                'email_verified_at' => $user instanceof Student ? $user->email_verified_at : now(),
                'registered_at' => $user instanceof Student ? $user->registered_at : now(),
                'registered_via_self_service' => false,
            ],
        };
    }
}
