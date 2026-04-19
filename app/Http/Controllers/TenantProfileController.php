<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithTenantRouting;
use App\Models\Course;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\TenantAdmin;
use App\Models\TenantUser;
use App\Support\Tenancy\CurrentTenant;
use App\Support\Tenancy\TenantUploadManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TenantProfileController extends Controller
{
    use InteractsWithTenantRouting;

    public function show(CurrentTenant $currentTenant): View
    {
        $requestedRole = $this->requestedRole();
        [$role, , $user] = $this->currentUser($requestedRole);
        $tenant = $currentTenant->tenant();

        abort_unless($tenant && $user, 404);

        $brandingSettings = $this->brandingSettings($tenant);

        if ($role === 'student' && $user instanceof Student) {
            $user->loadMissing('course');
        }

        $courses = $role === 'admin'
            ? Course::query()->withCount('students')->orderBy('sort_order')->orderBy('code')->get()
            : collect();

        return view('tenant.profile.show', [
            'tenant' => $tenant,
            'pageTitle' => 'Profile | '.$brandingSettings['portal_title'],
            'profileRole' => $role,
            'profileUser' => $user,
            'courses' => $courses,
            'brandingSettings' => $brandingSettings,
            'ojtSettings' => $this->ojtSettings($tenant),
            'profileUpdateAction' => $this->tenantRoute($tenant, $this->profileRouteName($role, 'profile.update')),
            'passwordUpdateAction' => $this->tenantRoute($tenant, $this->profileRouteName($role, 'profile.password.update')),
            'brandingSettingsAction' => $this->tenantRoute($tenant, 'admin.profile.branding-settings'),
            'courseStoreAction' => $this->tenantRoute($tenant, 'courses.store'),
            'ojtSettingsAction' => $this->tenantRoute($tenant, 'admin.profile.ojt-settings'),
            'courseActions' => $courses->mapWithKeys(fn (Course $course) => [
                $course->getKey() => [
                    'update' => $this->tenantRoute($tenant, 'courses.update', ['course' => $course]),
                    'destroy' => $this->tenantRoute($tenant, 'courses.destroy', ['course' => $course]),
                ],
            ])->all(),
        ]);
    }

    public function update(Request $request, CurrentTenant $currentTenant): RedirectResponse
    {
        [$role, , $user] = $this->currentUser($this->requestedRole());
        $tenant = $currentTenant->tenant();

        abort_unless($tenant && $user, 404);

        $data = match ($role) {
            'student' => $request->validate([
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('tenant.tenant_users', 'email')->ignore($user->getKey())],
                'program' => ['nullable', 'string', 'max:255'],
            ]),
            'supervisor' => $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('tenant.tenant_users', 'email')->ignore($user->getKey())],
                'position' => ['nullable', 'string', 'max:255'],
            ]),
            default => $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('tenant.tenant_users', 'email')->ignore($user->getKey())],
            ]),
        };

        $this->ensureEmailStaysUniqueAcrossRoles($role, $data['email'], $user->getKey());

        $user->update($data);

        return $this->redirectToTenantRoute($request, $tenant, $this->profileRouteName($role, 'profile.show'), status: 'Profile updated.');
    }

    public function updatePassword(Request $request, CurrentTenant $currentTenant): RedirectResponse
    {
        [$role, $guard, $user] = $this->currentUser($this->requestedRole());
        $tenant = $currentTenant->tenant();

        abort_unless($tenant && $user, 404);

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $user->getAuthPassword())) {
            throw ValidationException::withMessages([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        $user->update([
            'password' => $data['password'],
        ]);

        Auth::guard($guard)->setUser($user->fresh());

        return $this->redirectToTenantRoute($request, $tenant, $this->profileRouteName($role, 'profile.show'), status: 'Password updated.');
    }

    public function saveOjtSettings(Request $request, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        abort_unless(Auth::guard('tenant_admin')->check(), 403);

        $validated = $request->validate([
            'default_ojt_hours' => ['required', 'numeric', 'min:1', 'max:9999'],
            'allow_student_hour_override' => ['nullable', 'boolean'],
            'ojt_hours_note' => ['nullable', 'string', 'max:500'],
        ]);

        $settings = $tenant->settings ?? [];
        $settings['default_ojt_hours'] = (float) $validated['default_ojt_hours'];
        $settings['allow_student_hour_override'] = $request->boolean('allow_student_hour_override', false);
        $settings['ojt_hours_note'] = $validated['ojt_hours_note'] ?? null;

        $tenant->update([
            'settings' => $settings,
        ]);

        return $this->redirectToTenantRoute($request, $tenant, 'admin.profile.show', status: 'OJT hours settings saved.')
            ->withFragment('ojt-settings');
    }

    public function saveBrandingSettings(
        Request $request,
        CurrentTenant $currentTenant,
        TenantUploadManager $uploadManager,
    ): RedirectResponse {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        abort_unless(Auth::guard('tenant_admin')->check(), 403);

        $validated = $request->validate([
            'portal_title' => ['required', 'string', 'max:120'],
            'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'page_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'page_alt_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'surface_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'surface_soft_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'surface_alt_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_muted_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'border_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'portal_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $settings = $tenant->settings ?? [];
        $branding = is_array($settings['branding'] ?? null) ? $settings['branding'] : [];
        $portalTitle = trim($validated['portal_title']);

        $branding['portal_title'] = $portalTitle !== '' ? $portalTitle : config('app.name', 'University Practicum');
        $branding['accent'] = strtoupper($validated['accent_color']);
        $branding['secondary'] = strtoupper($validated['secondary_color']);
        $branding['page'] = strtoupper($validated['page_color']);
        $branding['page_alt'] = strtoupper($validated['page_alt_color']);
        $branding['surface'] = strtoupper($validated['surface_color']);
        $branding['surface_soft'] = strtoupper($validated['surface_soft_color']);
        $branding['surface_alt'] = strtoupper($validated['surface_alt_color']);
        $branding['text'] = strtoupper($validated['text_color']);
        $branding['text_muted'] = strtoupper($validated['text_muted_color']);
        $branding['border'] = strtoupper($validated['border_color']);
        $branding['logo_path'] = $uploadManager->replace(
            $request->file('portal_logo'),
            $tenant,
            'branding',
            $branding['logo_path'] ?? null,
        );

        $settings['branding'] = $branding;

        $tenant->update([
            'settings' => $settings,
        ]);

        return $this->redirectToTenantRoute($request, $tenant, 'admin.profile.show', status: 'Portal branding saved.')
            ->withFragment('portal-branding');
    }

    protected function currentUser(?string $preferredRole = null): array
    {
        if ($preferredRole === 'admin' && ($user = Auth::guard('tenant_admin')->user())) {
            return ['admin', 'tenant_admin', $this->freshTenantUser($user)];
        }

        if ($preferredRole === 'supervisor' && ($user = Auth::guard('supervisor')->user())) {
            return ['supervisor', 'supervisor', $this->freshTenantUser($user)];
        }

        if ($preferredRole === 'student' && ($user = Auth::guard('student')->user())) {
            return ['student', 'student', $this->freshTenantUser($user)];
        }

        if ($user = Auth::guard('tenant_admin')->user()) {
            return ['admin', 'tenant_admin', $this->freshTenantUser($user)];
        }

        if ($user = Auth::guard('supervisor')->user()) {
            return ['supervisor', 'supervisor', $this->freshTenantUser($user)];
        }

        $student = Auth::guard('student')->user();

        return ['student', 'student', $student ? $this->freshTenantUser($student) : null];
    }

    protected function requestedRole(): ?string
    {
        return match (true) {
            request()->routeIs('tenant*.admin.*') => 'admin',
            request()->routeIs('tenant*.supervisor.*') => 'supervisor',
            request()->routeIs('tenant*.student.*') => 'student',
            default => null,
        };
    }

    protected function profileRouteName(string $role, string $suffix): string
    {
        return "{$role}.{$suffix}";
    }

    protected function freshTenantUser($user)
    {
        return $user::query()->findOrFail($user->getKey());
    }

    protected function ensureEmailStaysUniqueAcrossRoles(string $role, string $email, int $ignoreId): void
    {
        $conflict = TenantUser::query()
            ->where('email', $email)
            ->whereKeyNot($ignoreId)
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'email' => 'This email is already assigned to another university portal role.',
            ]);
        }
    }

    protected function ojtSettings($tenant): array
    {
        $settings = $tenant->settings ?? [];

        return [
            'default_ojt_hours' => $settings['default_ojt_hours'] ?? 486,
            'allow_student_hour_override' => $settings['allow_student_hour_override'] ?? false,
            'ojt_hours_note' => $settings['ojt_hours_note'] ?? null,
        ];
    }

    protected function brandingSettings($tenant): array
    {
        $settings = $tenant->settings ?? [];
        $branding = is_array($settings['branding'] ?? null) ? $settings['branding'] : [];
        $accent = (string) ($branding['accent'] ?? '');
        $secondary = (string) ($branding['secondary'] ?? '');
        $page = (string) ($branding['page'] ?? '');
        $pageAlt = (string) ($branding['page_alt'] ?? '');
        $surface = (string) ($branding['surface'] ?? '');
        $surfaceSoft = (string) ($branding['surface_soft'] ?? '');
        $surfaceAlt = (string) ($branding['surface_alt'] ?? '');
        $text = (string) ($branding['text'] ?? '');
        $textMuted = (string) ($branding['text_muted'] ?? '');
        $border = (string) ($branding['border'] ?? '');

        return [
            'portal_title' => filled($branding['portal_title'] ?? null)
                ? $branding['portal_title']
                : config('app.name', 'University Practicum'),
            'accent' => preg_match('/^#[0-9A-Fa-f]{6}$/', $accent) ? strtoupper($accent) : '#7B1C2E',
            'secondary' => preg_match('/^#[0-9A-Fa-f]{6}$/', $secondary) ? strtoupper($secondary) : '#F5A623',
            'page' => preg_match('/^#[0-9A-Fa-f]{6}$/', $page) ? strtoupper($page) : '#09111F',
            'page_alt' => preg_match('/^#[0-9A-Fa-f]{6}$/', $pageAlt) ? strtoupper($pageAlt) : '#0E1830',
            'surface' => preg_match('/^#[0-9A-Fa-f]{6}$/', $surface) ? strtoupper($surface) : '#0F172A',
            'surface_soft' => preg_match('/^#[0-9A-Fa-f]{6}$/', $surfaceSoft) ? strtoupper($surfaceSoft) : '#16213B',
            'surface_alt' => preg_match('/^#[0-9A-Fa-f]{6}$/', $surfaceAlt) ? strtoupper($surfaceAlt) : '#1B2946',
            'text' => preg_match('/^#[0-9A-Fa-f]{6}$/', $text) ? strtoupper($text) : '#EEF4FF',
            'text_muted' => preg_match('/^#[0-9A-Fa-f]{6}$/', $textMuted) ? strtoupper($textMuted) : '#9EABC5',
            'border' => preg_match('/^#[0-9A-Fa-f]{6}$/', $border) ? strtoupper($border) : '#8094C4',
            'logo_path' => $branding['logo_path'] ?? null,
        ];
    }
}
