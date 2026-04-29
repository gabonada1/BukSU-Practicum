<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;

use App\Http\Controllers\Concerns\AuthorizesTenantPermissions;
use App\Http\Controllers\Concerns\InteractsWithTenantRouting;
use App\Http\Controllers\Concerns\RecordsTenantAudit;
use App\Http\Requests\StudentRequest;
use App\Mail\StudentCredentialsMail;
use App\Models\Course;
use App\Models\InternshipApplication;
use App\Models\PartnerCompany;
use App\Models\Student;
use App\Models\TenantUser;
use App\Support\Security\PasswordGenerator;
use App\Support\Tenancy\CurrentTenant;
use App\Support\Tenancy\TenantUrlGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class StudentController extends Controller
{
    use AuthorizesTenantPermissions, InteractsWithTenantRouting, RecordsTenantAudit;

    public function store(
        StudentRequest $request,
        CurrentTenant $currentTenant,
        PasswordGenerator $passwordGenerator
    ): RedirectResponse {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        $this->authorizeTenantPermission('user.create', $tenant);

        $data = $request->validated();

        $this->ensureEmailIsAvailable($data['email']);
        $plainPassword = filled($data['password'] ?? null)
            ? $data['password']
            : $passwordGenerator->generate();

        $this->ensureCompanyHasCapacity(
            $data['partner_company_id'] ?? null,
            $data['status']
        );

        $data = $this->applyCourseAndHourDefaults($data, $tenant->settings ?? []);

        $student = Student::query()->create(array_merge($data, [
            'password' => $plainPassword,
            'completed_hours' => 0,
            'is_active' => true,
            'email_verified_at' => now(),
            'registered_at' => now(),
        ]));
        $this->auditTenantActivity($request, 'created student', $student, null, $student->toArray());

        rescue(function () use ($tenant, $student, $plainPassword) {
            Mail::to($student->email)->send(
                new StudentCredentialsMail($tenant, $student, $plainPassword, app(TenantUrlGenerator::class))
            );
        }, report: true);

        return $this->redirectToTenantRoute(
            $request,
            $tenant,
            'admin.dashboard',
            ['section' => 'students'],
            'Student added and portal credentials emailed.'
        );
    }

    public function update(StudentRequest $request, CurrentTenant $currentTenant, Student $student): RedirectResponse
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        $this->authorizeTenantPermission('user.update', $tenant);

        $data = $request->validated();

        $this->ensureEmailIsAvailable($data['email'], $student->getKey());
        $this->ensureCompanyHasCapacity(
            $data['partner_company_id'] ?? null,
            $data['status'],
            $student->getKey()
        );

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $data = $this->applyCourseAndHourDefaults($data, $tenant->settings ?? []);
        $data['suspended_at'] = $data['is_active'] ? null : now();
        $data['email_verified_at'] = $request->boolean('email_verified')
            ? ($student->email_verified_at ?? now())
            : null;

        unset($data['email_verified']);

        $oldValues = $student->toArray();
        $student->update($data);
        $this->syncStudentApplicationStatus($student->fresh());
        $this->auditTenantActivity($request, 'updated student', $student, $oldValues, $student->fresh()->toArray());

        return $this->redirectToTenantRoute(
            $request,
            $tenant,
            'admin.dashboard',
            ['section' => 'students'],
            'Student record updated.'
        );
    }

    protected function ensureEmailIsAvailable(string $email, ?int $ignoreStudentId = null): void
    {
        $emailTaken = TenantUser::query()
            ->when($ignoreStudentId, fn ($query) => $query->whereKeyNot($ignoreStudentId))
            ->where('email', $email)
            ->exists();

        if ($emailTaken) {
            throw ValidationException::withMessages([
                'email' => 'This email is already being used by another university portal account.',
            ]);
        }
    }

    protected function applyCourseAndHourDefaults(array $data, array $settings): array
    {
        $allowOverride = (bool) ($settings['allow_student_hour_override'] ?? false);
        $defaultHours = (float) ($settings['default_ojt_hours'] ?? 486);

        if (! empty($data['course_id'])) {
            $course = Course::query()->find($data['course_id']);

            if ($course) {
                if (! $allowOverride || blank($data['required_hours'] ?? null)) {
                    $data['required_hours'] = $course->required_ojt_hours;
                }

                $data['program'] = $course->code;
            }
        }

        if (blank($data['required_hours'] ?? null)) {
            $data['required_hours'] = $defaultHours;
        }

        return $data;
    }

    protected function ensureCompanyHasCapacity(?int $companyId, string $status, ?int $ignoreStudentId = null): void
    {
        if (! $companyId || ! in_array($status, ['accepted', 'deployed'], true)) {
            return;
        }

        $company = PartnerCompany::query()->findOrFail($companyId);

        $occupiedSlots = Student::query()
            ->where('partner_company_id', $companyId)
            ->whereIn('status', ['accepted', 'deployed'])
            ->when($ignoreStudentId, fn ($query) => $query->whereKeyNot($ignoreStudentId))
            ->count();

        if ($occupiedSlots >= $company->intern_slot_limit) {
            throw ValidationException::withMessages([
                'partner_company_id' => 'This partner organization has already reached its OJT slot limit.',
            ]);
        }
    }

    protected function syncStudentApplicationStatus(Student $student): void
    {
        if (! in_array($student->status, ['accepted', 'deployed'], true) || ! $student->partner_company_id) {
            return;
        }

        $application = InternshipApplication::query()
            ->where('student_id', $student->getKey())
            ->where('partner_company_id', $student->partner_company_id)
            ->whereIn('status', ['pending', 'accepted', 'deployed'])
            ->latest('applied_at')
            ->latest('id')
            ->first();

        if (! $application) {
            return;
        }

        $application->update([
            'status' => $student->status,
            'reviewed_at' => now(),
        ]);

        InternshipApplication::query()
            ->where('student_id', $student->getKey())
            ->whereKeyNot($application->getKey())
            ->whereIn('status', ['pending', 'accepted'])
            ->update([
                'status' => 'rejected',
                'admin_feedback' => 'Closed after another internship application was approved.',
                'reviewed_at' => now(),
            ]);
    }
}
