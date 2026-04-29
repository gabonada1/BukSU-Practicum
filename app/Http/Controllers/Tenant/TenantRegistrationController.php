<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;

use App\Http\Controllers\Concerns\InteractsWithTenantRouting;
use App\Http\Requests\TenantRegistrationRequest;
use App\Mail\StudentRegistrationVerificationMail;
use App\Mail\TeacherRegistrationVerificationMail;
use App\Models\Course;
use App\Models\PartnerCompany;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\TenantUser;
use App\Support\Tenancy\CurrentTenant;
use App\Support\Tenancy\TenantUrlGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TenantRegistrationController extends Controller
{
    use InteractsWithTenantRouting;

    protected array $registrationRoles = ['student', 'teacher'];

    public function create(CurrentTenant $currentTenant): View
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);

        $selectedRole = request()->query('role', old('role'));

        if (! in_array($selectedRole, $this->registrationRoles, true)) {
            $selectedRole = null;
        }

        $portalTitle = data_get($tenant->settings, 'branding.portal_title', config('app.name', 'University Practicum'));

        return view('tenant.auth.register', [
            'tenant' => $tenant,
            'pageTitle' => 'Register | '.$portalTitle,
            'selectedRole' => $selectedRole,
            'courses' => Course::active()->get(),
            'companies' => PartnerCompany::query()->where('is_active', true)->orderBy('name')->get(),
            'ojtSettings' => [
                'default_ojt_hours' => $tenant->settings['default_ojt_hours'] ?? 486,
                'allow_student_hour_override' => $tenant->settings['allow_student_hour_override'] ?? false,
                'ojt_hours_note' => $tenant->settings['ojt_hours_note'] ?? null,
            ],
            'registerPageUrl' => $this->tenantRoute($tenant, 'register.create'),
            'registerAction' => $this->tenantRoute($tenant, 'register.store'),
            'loginUrl' => $this->tenantRoute($tenant, 'login.default'),
        ]);
    }

    public function store(TenantRegistrationRequest $request, CurrentTenant $currentTenant, TenantUrlGenerator $urlGenerator): RedirectResponse
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);

        $data = $request->validated();
        $role = $data['role'];

        if ($role === 'student') {
            $student = $this->registerStudent($data, $tenant->settings ?? []);

            rescue(function () use ($tenant, $student, $urlGenerator) {
                Mail::to($student->email)->send(
                    new StudentRegistrationVerificationMail($tenant, $student, $urlGenerator)
                );
            }, report: true);

            return $this->redirectToTenantRoute(
                $request,
                $tenant,
                'login.default',
                status: 'Student registration received. Please check your email and verify your account before signing in to the university portal.'
            );
        }

        $teacher = $this->registerTeacher($data);

        rescue(function () use ($tenant, $teacher, $urlGenerator) {
            Mail::to($teacher->email)->send(
                new TeacherRegistrationVerificationMail($tenant, $teacher, $urlGenerator)
            );
        }, report: true);

        return $this->redirectToTenantRoute(
            $request,
            $tenant,
            'login.default',
            status: 'Company supervisor registration received. Please check your email and verify your account before signing in to the university portal.'
        );
    }

    public function verify(CurrentTenant $currentTenant, string $token): RedirectResponse
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);

        $student = Student::query()->where('email_verification_token', $token)->first();
        $message = 'Email verified. You can now sign in to your student portal.';

        if (! $student) {
            $teacher = Supervisor::query()->where('email_verification_token', $token)->firstOrFail();
            $teacher->forceFill([
                'email_verified_at' => now(),
                'email_verification_token' => null,
            ])->save();

            $message = 'Email verified. You can now sign in to your company supervisor workspace.';
        } else {
            $student->forceFill([
                'email_verified_at' => now(),
                'email_verification_token' => null,
            ])->save();
        }

        return redirect()->to($this->tenantRoute($tenant, 'login.default'))
            ->with('status', $message);
    }

    protected function ensureEmailIsAvailable(string $email): void
    {
        $emailTaken = TenantUser::query()->where('email', $email)->exists();

        if ($emailTaken) {
            throw ValidationException::withMessages([
                'email' => 'This email is already being used by another university portal account.',
            ]);
        }
    }

    protected function registerStudent(array $data, array $settings): Student
    {
        $this->ensureEmailIsAvailable($data['email']);
        unset($data['role']);

        $requiredHours = (float) ($settings['default_ojt_hours'] ?? 486);

        if (! empty($data['course_id'])) {
            $course = Course::query()->find($data['course_id']);

            if ($course) {
                $data['program'] = $course->code;
                $requiredHours = (float) $course->required_ojt_hours;
            }
        }

        return Student::query()->create($data + [
            'required_hours' => $requiredHours,
            'completed_hours' => 0,
            'status' => 'pending',
            'is_active' => true,
            'email_verification_token' => Str::random(64),
            'verification_sent_at' => now(),
            'registered_at' => now(),
            'registered_via_self_service' => true,
        ]);
    }

    protected function registerTeacher(array $data): Supervisor
    {
        $this->ensureEmailIsAvailable($data['email']);
        unset($data['role']);

        return Supervisor::query()->create($data + [
            'is_active' => true,
            'email_verification_token' => Str::random(64),
            'verification_sent_at' => now(),
            'registered_at' => now(),
            'registered_via_self_service' => true,
        ]);
    }
}
