<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesTenantPermissions;
use App\Http\Controllers\Concerns\InteractsWithTenantRouting;
use App\Models\InternshipApplication;
use App\Models\PartnerCompany;
use App\Models\Student;
use App\Models\StudentRequirement;
use App\Support\Tenancy\CurrentTenant;
use App\Support\Tenancy\TenantUploadManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InternshipApplicationController extends Controller
{
    use AuthorizesTenantPermissions, InteractsWithTenantRouting;

    public function storeAdmin(
        Request $request,
        CurrentTenant $currentTenant,
        TenantUploadManager $uploadManager
    ): RedirectResponse {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        $this->authorizeTenantPermission('application.manage', $tenant);

        $data = $this->validateApplication($request, requireStudent: true);
        $student = Student::query()->findOrFail($data['student_id']);
        $company = PartnerCompany::query()->findOrFail($data['partner_company_id']);

        $this->ensureCompanyHasCapacity($company, $student, $data['status']);

        $application = InternshipApplication::query()->create($this->payloadForSave(
            $request,
            $tenant,
            $uploadManager,
            $data,
        ) + [
            'applied_at' => now(),
        ]);

        $this->syncStudentAssignment($student, $application, $data['status']);

        return $this->redirectToTenantRoute(
            $request,
            $tenant,
            'admin.dashboard',
            ['section' => 'students', 'student_applications' => $student->getKey()],
            'Internship application recorded.'
        );
    }

    public function updateAdmin(
        Request $request,
        CurrentTenant $currentTenant,
        InternshipApplication $application,
        TenantUploadManager $uploadManager
    ): RedirectResponse {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        $this->authorizeTenantPermission('application.manage', $tenant);

        $data = $this->validateApplication($request, requireStudent: true, ignoreApplication: $application);
        $student = Student::query()->findOrFail($data['student_id']);
        $company = PartnerCompany::query()->findOrFail($data['partner_company_id']);

        $this->ensureCompanyHasCapacity($company, $student, $data['status']);

        $application->update($this->payloadForSave(
            $request,
            $tenant,
            $uploadManager,
            $data,
            $application,
        ) + [
            'deployed_at' => $data['status'] === 'deployed'
                ? ($application->deployed_at ?? now())
                : null,
            'reviewed_at' => $data['status'] === 'pending' ? null : now(),
        ]);

        $this->syncStudentAssignment($student, $application->fresh(), $data['status']);

        return $this->redirectToTenantRoute(
            $request,
            $tenant,
            'admin.dashboard',
            ['section' => 'students', 'student_applications' => $student->getKey()],
            'Internship application updated.'
        );
    }

    public function storeStudent(
        Request $request,
        CurrentTenant $currentTenant,
        TenantUploadManager $uploadManager
    ): RedirectResponse {
        $tenant = $currentTenant->tenant();
        $student = Auth::guard('student')->user();

        abort_unless($tenant && $student, 404);
        $this->authorizeTenantPermission('application.submit', $tenant);

        if (InternshipApplication::query()
            ->where('student_id', $student->getKey())
            ->whereIn('status', ['pending', 'accepted', 'deployed'])
            ->exists()) {
            throw ValidationException::withMessages([
                'partner_company_id' => 'You already have an active internship application. Wait for the internship coordinator to review it before submitting another one.',
            ]);
        }

        $data = $request->validate([
            'partner_company_id' => ['required', 'integer', 'exists:tenant.partner_companies,id'],
            'position_applied' => ['required', 'string', 'max:255'],
            'student_notes' => ['nullable', 'string', 'max:1500'],
            'resume' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'endorsement_letter' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'moa' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'clearance' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ]);

        $application = InternshipApplication::query()->create([
            'student_id' => $student->getKey(),
            'partner_company_id' => $data['partner_company_id'],
            'position_applied' => $data['position_applied'],
            'resume_path' => $uploadManager->store($request->file('resume'), $tenant, 'applications/resumes'),
            'endorsement_letter_path' => $request->hasFile('endorsement_letter')
                ? $uploadManager->store($request->file('endorsement_letter'), $tenant, 'applications/endorsements')
                : null,
            'moa_path' => $request->hasFile('moa')
                ? $uploadManager->store($request->file('moa'), $tenant, 'applications/moa')
                : null,
            'clearance_path' => $request->hasFile('clearance')
                ? $uploadManager->store($request->file('clearance'), $tenant, 'applications/clearance')
                : null,
            'student_notes' => $data['student_notes'] ?? null,
            'status' => 'pending',
            'applied_at' => now(),
        ]);

        $this->syncApplicationRequirements($student, [
            'Resume' => $application->resume_path,
            'Endorsement Letter' => $application->endorsement_letter_path,
            'MOA' => $application->moa_path,
            'Clearance' => $application->clearance_path,
        ]);

        return redirect()->to($this->tenantRoute($tenant, 'student.dashboard').'?section=applications')
            ->with('status', 'Internship application submitted. You can now track it from your dashboard.');
    }

    /**
     * @param array<string, string|null> $documents
     */
    protected function syncApplicationRequirements(Student $student, array $documents): void
    {
        foreach ($documents as $requirementName => $filePath) {
            if (! filled($filePath)) {
                continue;
            }

            StudentRequirement::query()->updateOrCreate(
                [
                    'student_id' => $student->getKey(),
                    'requirement_name' => $requirementName,
                ],
                [
                    'status' => 'submitted',
                    'file_path' => $filePath,
                    'notes' => 'Submitted with internship application.',
                    'feedback' => null,
                    'submitted_at' => now(),
                    'reviewed_at' => null,
                ]
            );
        }
    }

    protected function validateApplication(
        Request $request,
        bool $requireStudent,
        ?InternshipApplication $ignoreApplication = null
    ): array {
        return $request->validate([
            'student_id' => array_filter([
                $requireStudent ? 'required' : null,
                'integer',
                Rule::exists('tenant.tenant_users', 'id')->where('role', 'student'),
            ]),
            'partner_company_id' => ['required', 'integer', 'exists:tenant.partner_companies,id'],
            'position_applied' => ['required', 'string', 'max:255'],
            'student_notes' => ['nullable', 'string', 'max:1500'],
            'status' => ['required', Rule::in(['pending', 'accepted', 'rejected', 'deployed'])],
            'admin_feedback' => ['nullable', 'string', 'max:1500'],
            'resume' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'endorsement_letter' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'moa' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'clearance' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ]);
    }

    protected function payloadForSave(
        Request $request,
        $tenant,
        TenantUploadManager $uploadManager,
        array $data,
        ?InternshipApplication $application = null
    ): array {
        return [
            'student_id' => $data['student_id'],
            'partner_company_id' => $data['partner_company_id'],
            'position_applied' => $data['position_applied'],
            'student_notes' => $data['student_notes'] ?? null,
            'status' => $data['status'],
            'admin_feedback' => $data['admin_feedback'] ?? null,
            'resume_path' => $uploadManager->replace(
                $request->file('resume'),
                $tenant,
                'applications/resumes',
                $application?->resume_path
            ),
            'endorsement_letter_path' => $uploadManager->replace(
                $request->file('endorsement_letter'),
                $tenant,
                'applications/endorsements',
                $application?->endorsement_letter_path
            ),
            'moa_path' => $uploadManager->replace(
                $request->file('moa'),
                $tenant,
                'applications/moa',
                $application?->moa_path
            ),
            'clearance_path' => $uploadManager->replace(
                $request->file('clearance'),
                $tenant,
                'applications/clearance',
                $application?->clearance_path
            ),
        ];
    }

    protected function ensureCompanyHasCapacity(PartnerCompany $company, Student $student, string $status): void
    {
        if (! in_array($status, ['accepted', 'deployed'], true)) {
            return;
        }

        $occupiedSlots = Student::query()
            ->where('partner_company_id', $company->getKey())
            ->whereIn('status', ['accepted', 'deployed'])
            ->whereKeyNot($student->getKey())
            ->count();

        if ($occupiedSlots >= $company->intern_slot_limit) {
            throw ValidationException::withMessages([
                'partner_company_id' => 'This partner organization has already reached its OJT slot limit.',
            ]);
        }
    }

    protected function syncStudentAssignment(Student $student, InternshipApplication $application, string $status): void
    {
        if (in_array($status, ['accepted', 'deployed'], true)) {
            $student->update([
                'partner_company_id' => $application->partner_company_id,
                'status' => $status,
            ]);

            InternshipApplication::query()
                ->where('student_id', $student->getKey())
                ->whereKeyNot($application->getKey())
                ->whereIn('status', ['pending', 'accepted'])
                ->update([
                    'status' => 'rejected',
                    'admin_feedback' => 'Closed after another OJT application was approved.',
                    'reviewed_at' => now(),
                ]);

            return;
        }

        if (in_array($status, ['pending', 'rejected'], true)
            && $student->partner_company_id === $application->partner_company_id) {
            $student->update([
                'partner_company_id' => null,
                'status' => 'pending',
            ]);
        }
    }
}
