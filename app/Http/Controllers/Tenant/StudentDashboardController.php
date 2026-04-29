<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;

use App\Http\Controllers\Concerns\AuthorizesTenantPermissions;
use App\Models\PartnerCompany;
use App\Support\Security\RbacMatrix;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentDashboardController extends Controller
{
    use AuthorizesTenantPermissions;

    public function __invoke(Request $request): View
    {
        $student = Auth::guard('student')->user();

        abort_unless($student, 403);
        $this->authorizeTenantPermission('report.view');

        $student->load([
            'partnerCompany',
            'partnerCompany.supervisors',
            'requirements',
            'hourLogs',
            'applications.partnerCompany.supervisors',
        ]);

        $activeApplication = $student->applications
            ->whereIn('status', ['pending', 'accepted', 'deployed'])
            ->sortByDesc(fn ($application) => $application->applied_at?->timestamp ?? 0)
            ->first();
        $supervisorCompany = $student->partnerCompany ?: $activeApplication?->partnerCompany;
        $assignedSupervisors = $supervisorCompany?->supervisors ?? collect();

        $companies = PartnerCompany::query()
            ->with(['supervisors', 'students'])
            ->where('is_active', true)
            ->latest()
            ->get();

        $tenant = app(\App\Support\Tenancy\CurrentTenant::class)->tenant();
        $portalTitle = data_get($tenant?->settings, 'branding.portal_title', config('app.name', 'University Practicum'));
        $section = $request->string('section')->toString();

        if (! in_array($section, ['applications', 'requirements', 'logs'], true)) {
            $section = 'applications';
        }

        return view('tenant.student.dashboard', [
            'tenant' => $tenant,
            'pageTitle' => 'Student Dashboard | '.$portalTitle,
            'student' => $student,
            'companies' => $companies,
            'currentSection' => $section,
            'assignedSupervisors' => $assignedSupervisors,
            'studentApplicationAction' => route('tenant.student.applications.store'),
            'studentRequirementAction' => route('tenant.student.requirements.store'),
            'studentHourLogAction' => route('tenant.student.hours.store'),
            'canSubmitApplications' => RbacMatrix::tenantAllows($tenant, 'student', 'application.submit'),
            'canSubmitRequirements' => RbacMatrix::tenantAllows($tenant, 'student', 'requirement.submit'),
            'canSubmitHourLogs' => RbacMatrix::tenantAllows($tenant, 'student', 'hours.submit'),
        ]);
    }
}
