<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;

use App\Http\Controllers\Concerns\AuthorizesTenantPermissions;
use App\Http\Controllers\Concerns\InteractsWithTenantRouting;
use App\Http\Controllers\Concerns\RecordsTenantAudit;
use App\Http\Requests\InternshipApplicationRequest;
use App\Http\Requests\SubmitInternshipApplicationRequest;
use App\Models\InternshipApplication;
use App\Models\PartnerCompany;
use App\Models\Student;
use App\Models\StudentRequirement;
use App\Support\Tenancy\CurrentTenant;
use App\Support\Tenancy\TenantUploadManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class InternshipApplicationController extends Controller
{
    use AuthorizesTenantPermissions, InteractsWithTenantRouting, RecordsTenantAudit;

    public function storeAdmin(
        InternshipApplicationRequest $request,
        CurrentTenant $currentTenant,
        TenantUploadManager $uploadManager
    ): RedirectResponse {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        $this->authorizeTenantPermission('application.manage', $tenant);

        $data = $request->validated();
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
        $this->auditTenantActivity($request, 'created internship application', $application, null, $application->toArray());

        return $this->redirectToTenantRoute(
            $request,
            $tenant,
            'admin.dashboard',
            ['section' => 'students', 'student_applications' => $student->getKey()],
            'Internship application recorded.'
        );
    }

    public function updateAdmin(
        InternshipApplicationRequest $request,
        CurrentTenant $currentTenant,
        InternshipApplication $application,
        TenantUploadManager $uploadManager
    ): RedirectResponse {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        $this->authorizeTenantPermission('application.manage', $tenant);

        $data = $request->validated();
        $student = Student::query()->findOrFail($data['student_id']);
        $company = PartnerCompany::query()->findOrFail($data['partner_company_id']);

        $this->ensureCompanyHasCapacity($company, $student, $data['status']);

        $oldValues = $application->toArray();
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
        $this->auditTenantActivity($request, 'updated internship application', $application, $oldValues, $application->fresh()->toArray());

        return $this->redirectToTenantRoute(
            $request,
            $tenant,
            'admin.dashboard',
            ['section' => 'students', 'student_applications' => $student->getKey()],
            'Internship application updated.'
        );
    }

    public function storeStudent(
        SubmitInternshipApplicationRequest $request,
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

        $data = $request->validated();

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
        $this->auditTenantActivity($request, 'submitted internship application', $application, null, $application->toArray());

        return redirect()->to($this->tenantRoute($tenant, 'student.dashboard').'?section=applications')
            ->with('status', 'Internship application submitted. You can now track it from your dashboard.');
    }

    /**
     * @param  array<string, string|null>  $documents
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
