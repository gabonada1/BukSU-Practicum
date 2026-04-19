<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesTenantPermissions;
use App\Models\Course;
use App\Models\InternshipApplication;
use App\Models\OjtHourLog;
use App\Models\PartnerCompany;
use App\Models\Student;
use App\Models\StudentRequirement;
use App\Models\Supervisor;
use App\Models\TenantAdmin;
use App\Support\Security\RbacMatrix;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\View\View;

class TenantDashboardController extends Controller
{
    use AuthorizesTenantPermissions;

    public function __invoke(CurrentTenant $currentTenant): View
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        $this->authorizeTenantPermission('user.read', $tenant);

        $companies = PartnerCompany::query()->with('supervisors')->latest()->paginate(5, ['*'], 'companies_page')->withQueryString();
        $students = Student::query()->with(['partnerCompany', 'applications', 'course'])->latest()->paginate(5, ['*'], 'students_page')->withQueryString();
        $applications = InternshipApplication::query()->with(['student', 'partnerCompany'])->latest()->paginate(5, ['*'], 'applications_page')->withQueryString();
        $courses = Course::active()->get();
        $supervisors = Supervisor::query()->with('partnerCompany')->latest()->paginate(5, ['*'], 'supervisors_page')->withQueryString();
        $requirements = StudentRequirement::query()->with('student')->latest()->paginate(5, ['*'], 'requirements_page')->withQueryString();
        $hourLogs = OjtHourLog::query()->with('student')->latest('log_date')->paginate(5, ['*'], 'hours_page')->withQueryString();
        
        // Get all records for statistics and editing
        $allCompanies = PartnerCompany::query()->with('supervisors')->latest()->get();
        $allStudents = Student::query()->with(['partnerCompany', 'applications', 'course'])->latest()->get();
        $allApplications = InternshipApplication::query()->with(['student', 'partnerCompany'])->latest()->get();
        $allSupervisors = Supervisor::query()->with('partnerCompany')->latest()->get();
        $allRequirements = StudentRequirement::query()->with('student')->latest()->get();
        $allHourLogs = OjtHourLog::query()->with('student')->latest('log_date')->get();
        
        $userDirectory = $this->userDirectory($allStudents, $allSupervisors);
        $currentSection = request()->query('section', 'companies');
        $editKey = request()->query('edit');
        $studentApplicationStudentId = (int) request()->query('student_applications');
        $portalTitle = data_get($tenant->settings, 'branding.portal_title', config('app.name', 'University Practicum'));
        $adminPermissions = collect(array_keys(RbacMatrix::defaultTenantMatrix()))
            ->mapWithKeys(fn (string $permission) => [
                $permission => RbacMatrix::tenantAllows($tenant, RbacMatrix::TENANT_ADMIN_ROLE, $permission),
            ])
            ->all();
        $selectedStudent = $studentApplicationStudentId > 0
            ? $allStudents->firstWhere('id', $studentApplicationStudentId)
            : null;
        $selectedStudentApplications = $selectedStudent
            ? $allApplications->where('student_id', $selectedStudent->getKey())->values()
            : collect();

        return view('tenant.admin.dashboard', [
            'tenant' => $tenant,
            'pageTitle' => 'Internship Coordinator Dashboard | '.$portalTitle,
            'roles' => [
                'College Admin / Internship Coordinator',
                'Students',
                'Company Supervisor',
                'Partner Organizations',
            ],
            'modules' => [
                'Partner companies and internship slot management',
                'Student applications and deployment assignments',
                'Forms, requirements, and progress report submissions',
                'OJT hour tracking, validation, and attendance review',
                'Company supervisor evaluations and report validation',
                'College-level monitoring for forms, reports, and evaluations',
            ],
            'databaseStrategy' => [
                'The University Administration database stores the university registry, license tiers, platform settings, and shared authentication hooks.',
                'Each university portal uses a dedicated database for partner companies, student applications, forms and requirements, progress reports, and evaluation forms.',
                'The sample tenant uses Bukidnon State University - College of Technologies.',
            ],
            'stats' => [
                'companies' => $allCompanies->count(),
                'students' => $allStudents->count(),
                'applications' => $allApplications->count(),
                'supervisors' => $allSupervisors->count(),
                'users' => $userDirectory->count(),
                'approved_requirements' => StudentRequirement::query()->where('status', 'approved')->count(),
                'approved_hours' => round((float) OjtHourLog::query()->where('status', 'approved')->sum('hours'), 2),
            ],
            'companies' => $companies,
            'students' => $students,
            'applications' => $applications,
            'selectedStudentForApplications' => $selectedStudent,
            'selectedStudentApplications' => $selectedStudentApplications,
            'courses' => $courses,
            'supervisors' => $supervisors,
            'supervisorOptions' => $allSupervisors,
            'requirements' => $requirements,
            'hourLogs' => $hourLogs,
            'ojtSettings' => [
                'default_ojt_hours' => $tenant->settings['default_ojt_hours'] ?? 486,
                'allow_student_hour_override' => $tenant->settings['allow_student_hour_override'] ?? false,
                'ojt_hours_note' => $tenant->settings['ojt_hours_note'] ?? null,
            ],
            'userDirectory' => $userDirectory,
            'editing' => [
                'companies' => $currentSection === 'companies' ? $allCompanies->firstWhere('id', (int) $editKey) : null,
                'applications' => $allApplications->firstWhere('id', (int) $editKey),
                'supervisors' => $currentSection === 'supervisors' ? $allSupervisors->firstWhere('id', (int) $editKey) : null,
                'students' => $currentSection === 'students' ? $allStudents->firstWhere('id', (int) $editKey) : null,
                'requirements' => $currentSection === 'requirements' ? $allRequirements->firstWhere('id', (int) $editKey) : null,
                'hours' => $currentSection === 'hours' ? $allHourLogs->firstWhere('id', (int) $editKey) : null,
                'users' => $currentSection === 'users' ? $userDirectory->firstWhere('key', $editKey) : null,
            ],
            'requirementStatuses' => ['submitted', 'approved', 'revision', 'rejected'],
            'hourStatuses' => ['pending', 'approved', 'rejected'],
            'studentStatuses' => ['pending', 'accepted', 'deployed', 'completed'],
            'applicationStatuses' => ['pending', 'accepted', 'rejected', 'deployed'],
            'userRoleOptions' => ['admin', 'supervisor', 'student'],
            'formActions' => $this->formActions($tenant),
            'rbacIndexUrl' => route('tenant.admin.rbac.index'),
            'tenantPermissions' => $adminPermissions,
        ]);
    }

    protected function formActions($tenant): array
    {
        return [
            'companies' => route('tenant.admin.companies.store'),
            'applications' => route('tenant.admin.applications.store'),
            'students' => route('tenant.admin.students.store'),
            'supervisors' => route('tenant.admin.supervisors.store'),
            'requirements' => route('tenant.admin.requirements.store'),
            'hours' => route('tenant.admin.hours.store'),
        ];
    }

    protected function userDirectory($students, $supervisors)
    {
        $admins = TenantAdmin::query()->latest()->get()->map(function (TenantAdmin $admin) {
            return [
                'key' => 'admin:'.$admin->getKey(),
                'type' => 'admin',
                'id' => $admin->getKey(),
                'role' => 'admin',
                'name' => $admin->name,
                'email' => $admin->email,
                'status' => $admin->accountStatusLabel(),
                'is_active' => $admin->is_active,
                'email_verified_at' => now(),
                'context' => 'Internship Coordinator',
                'model' => $admin,
            ];
        });

        $supervisorItems = $supervisors->map(function (Supervisor $supervisor) {
            return [
                'key' => 'supervisor:'.$supervisor->getKey(),
                'type' => 'supervisor',
                'id' => $supervisor->getKey(),
                'role' => 'supervisor',
                'name' => $supervisor->name,
                'email' => $supervisor->email,
                'status' => $supervisor->accountStatusLabel(),
                'is_active' => $supervisor->is_active,
                'email_verified_at' => $supervisor->email_verified_at,
                'context' => $supervisor->department ?: ($supervisor->partnerCompany?->name ?: 'Company Supervisor'),
                'model' => $supervisor,
            ];
        });

        $studentItems = $students->map(function (Student $student) {
            return [
                'key' => 'student:'.$student->getKey(),
                'type' => 'student',
                'id' => $student->getKey(),
                'role' => 'student',
                'name' => $student->full_name,
                'email' => $student->email,
                'status' => $student->accountStatusLabel(),
                'is_active' => $student->is_active,
                'email_verified_at' => $student->email_verified_at,
                'context' => $student->course?->code ?: ($student->program ?: 'No program set'),
                'model' => $student,
            ];
        });

        return $admins
            ->concat($supervisorItems)
            ->concat($studentItems)
            ->sortBy('name')
            ->values();
    }
}
