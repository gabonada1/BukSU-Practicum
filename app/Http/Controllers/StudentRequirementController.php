<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesTenantPermissions;
use App\Http\Controllers\Concerns\InteractsWithTenantRouting;
use App\Http\Controllers\Concerns\RecordsTenantAudit;
use App\Models\Student;
use App\Models\StudentRequirement;
use App\Support\Tenancy\CurrentTenant;
use App\Support\Tenancy\TenantUploadManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StudentRequirementController extends Controller
{
    use AuthorizesTenantPermissions, InteractsWithTenantRouting, RecordsTenantAudit;

    public function store(
        Request $request,
        CurrentTenant $currentTenant,
        TenantUploadManager $uploadManager
    ): RedirectResponse {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        $this->authorizeTenantPermission('requirement.review', $tenant);

        $data = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('tenant.tenant_users', 'id')->where('role', 'student')],
            'requirement_name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['submitted', 'approved', 'revision', 'rejected'])],
            'notes' => ['nullable', 'string', 'max:1000'],
            'feedback' => ['nullable', 'string', 'max:1500'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ]);

        $requirement = StudentRequirement::query()->create($data + [
            'file_path' => $request->hasFile('file')
                ? $uploadManager->store($request->file('file'), $tenant, 'requirements')
                : null,
            'submitted_at' => now(),
            'reviewed_at' => $data['status'] === 'submitted' ? null : now(),
        ]);
        $this->auditTenantActivity($request, 'created student requirement', $requirement, null, $requirement->toArray());

        return $this->redirectToTenantRoute(
            $request,
            $tenant,
            'admin.dashboard',
            ['section' => 'requirements'],
            'Form or requirement recorded.'
        );
    }

    public function update(
        Request $request,
        CurrentTenant $currentTenant,
        StudentRequirement $requirement,
        TenantUploadManager $uploadManager
    ): RedirectResponse {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        $this->authorizeTenantPermission('requirement.review', $tenant);

        $data = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('tenant.tenant_users', 'id')->where('role', 'student')],
            'requirement_name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['submitted', 'approved', 'revision', 'rejected'])],
            'notes' => ['nullable', 'string', 'max:1000'],
            'feedback' => ['nullable', 'string', 'max:1500'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ]);

        $oldValues = $requirement->toArray();
        $requirement->update($data + [
            'file_path' => $uploadManager->replace(
                $request->file('file'),
                $tenant,
                'requirements',
                $requirement->file_path
            ),
            'reviewed_at' => $data['status'] === 'submitted' ? null : now(),
        ]);
        $this->auditTenantActivity($request, 'updated student requirement', $requirement, $oldValues, $requirement->fresh()->toArray());

        return $this->redirectToTenantRoute(
            $request,
            $tenant,
            'admin.dashboard',
            ['section' => 'requirements'],
            'Form or requirement updated.'
        );
    }

    public function storeStudent(
        Request $request,
        CurrentTenant $currentTenant,
        TenantUploadManager $uploadManager
    ): RedirectResponse {
        $tenant = $currentTenant->tenant();
        /** @var Student|null $student */
        $student = Auth::guard('student')->user();

        abort_unless($tenant && $student, 404);
        $this->authorizeTenantPermission('requirement.submit', $tenant);

        $data = $request->validate([
            'requirement_name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ]);

        $requirement = StudentRequirement::query()->create([
            'student_id' => $student->getKey(),
            'requirement_name' => $data['requirement_name'],
            'status' => 'submitted',
            'file_path' => $uploadManager->store($request->file('file'), $tenant, 'requirements'),
            'notes' => $data['notes'] ?? null,
            'feedback' => null,
            'submitted_at' => now(),
            'reviewed_at' => null,
        ]);
        $this->auditTenantActivity($request, 'submitted student requirement', $requirement, null, $requirement->toArray());

        return redirect()->to($this->tenantRoute($tenant, 'student.dashboard').'?section=requirements')
            ->with('status', 'Document uploaded. Your internship coordinator can now review it.');
    }
}
