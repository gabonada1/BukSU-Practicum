<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;

use App\Http\Requests\TenantLoginRequest;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\TenantAdmin;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TenantAuthController extends Controller
{
    public function admin(CurrentTenant $currentTenant): View|RedirectResponse
    {
        return $this->renderLoginPage($currentTenant, null);
    }

    public function storeAdmin(TenantLoginRequest $request, CurrentTenant $currentTenant): RedirectResponse
    {
        $role = $this->normalizeRole($request->string('role')->toString());

        if ($role === 'admin' && ! $request->filled('role')) {
            $role = $this->roleForEmail($request->string('email')->toString()) ?? '';
        }

        if (! $role) {
            throw ValidationException::withMessages([
                'email' => 'No account was found for this email in the selected university portal.',
            ]);
        }

        $this->scopeSessionToRole($request, $currentTenant, $role);

        return $this->authenticateForRole($request, $currentTenant, $role);
    }

    public function create(CurrentTenant $currentTenant, string $role): View|RedirectResponse
    {
        return $this->renderLoginPage($currentTenant, $this->normalizeRole($role));
    }

    public function store(TenantLoginRequest $request, CurrentTenant $currentTenant, string $role): RedirectResponse
    {
        $normalizedRole = $this->normalizeRole($role);
        $this->scopeSessionToRole($request, $currentTenant, $normalizedRole);

        return $this->authenticateForRole($request, $currentTenant, $normalizedRole);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $role = $this->normalizeRole((string) $request->route('role', ''));
        $guard = $role ? $this->guardForRole($role) : null;

        foreach (array_filter([$guard]) ?: ['tenant_admin', 'supervisor', 'student'] as $logoutGuard) {
            if (Auth::guard($logoutGuard)->check()) {
                Auth::guard($logoutGuard)->logout();
                $request->session()->forget("tenant_context.{$logoutGuard}");
            }
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to($this->loginPagePath($role ?: 'admin'));
    }

    protected function guardForRole(string $role): string
    {
        return match ($role) {
            'admin' => 'tenant_admin',
            'supervisor' => 'supervisor',
            default => 'student',
        };
    }

    protected function loginAction($tenant, ?string $role): string
    {
        if (! $role) {
            return route('tenant.login.default.store', [], false);
        }

        return route('tenant.login.store', ['role' => $role], false);
    }

    protected function roleForEmail(string $email): ?string
    {
        $roles = [
            'admin' => TenantAdmin::query(),
            'supervisor' => Supervisor::query(),
            'student' => Student::query(),
        ];

        foreach ($roles as $role => $query) {
            if ($query->where('email', $email)->exists()) {
                return $role;
            }
        }

        return null;
    }

    protected function canAccessPortal($user, string $role): bool
    {
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'canAccessPortal')) {
            return $user->canAccessPortal();
        }

        return true;
    }

    protected function blockedMessage(string $role): string
    {
        return match ($role) {
            'student' => 'This student account is suspended or still waiting for email verification.',
            'supervisor' => 'This company supervisor account is suspended or still waiting for email verification.',
            default => 'This internship coordinator account is suspended. Please contact University Practicum Administration.',
        };
    }

    protected function renderLoginPage(CurrentTenant $currentTenant, ?string $role): View|RedirectResponse
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);

        $portalTitle = data_get($tenant->settings, 'branding.portal_title', config('app.name', 'University Practicum'));

        if ($role && Auth::guard($this->guardForRole($role))->check()) {
            return redirect()->to($this->dashboardPath($role));
        }

        return view('tenant.auth.login', [
            'tenant' => $tenant,
            'pageTitle' => 'University Portal | '.$portalTitle,
            'selectedLoginRole' => $role,
            'loginAction' => $this->loginAction($tenant, $role),
            'registerUrl' => route('tenant.register.create', [], false),
        ]);
    }

    protected function authenticateForRole(TenantLoginRequest $request, CurrentTenant $currentTenant, string $role): RedirectResponse
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);

        $guard = $this->guardForRole($role);
        $providerName = config("auth.guards.{$guard}.provider");
        $provider = $providerName ? Auth::createUserProvider($providerName) : null;
        $credentials = $request->only('email', 'password');
        $authenticatedUser = $provider?->retrieveByCredentials($credentials);

        if (! $authenticatedUser || ! $provider->validateCredentials($authenticatedUser, $credentials)) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials do not match our records.',
            ]);
        }
        Auth::guard($guard)->login($authenticatedUser, $request->boolean('remember'));
        $authenticatedUser = Auth::guard($guard)->user();

        if (! $this->canAccessPortal($authenticatedUser, $role)) {
            Auth::guard($guard)->logout();

            throw ValidationException::withMessages([
                'email' => $this->blockedMessage($role),
            ]);
        }

        $request->session()->regenerate();
        $request->session()->put("tenant_context.{$guard}", (string) $tenant->getRouteKey());

        if ($role === 'admin' && $authenticatedUser->must_change_password) {
            return redirect()->to(route('tenant.admin.password.setup.show', [], false));
        }

        return redirect()->to($this->dashboardPath($role));
    }

    protected function dashboardPath(string $role): string
    {
        return match ($role) {
            'admin' => route('tenant.admin.dashboard', [], false),
            'supervisor' => route('tenant.supervisor.dashboard', [], false),
            default => route('tenant.student.dashboard', [], false),
        };
    }

    protected function loginPagePath(string $role = 'admin'): string
    {
        return $role === 'admin'
            ? route('tenant.login.default', [], false)
            : route('tenant.login', ['role' => $role], false);
    }

    protected function normalizeRole(string $role): string
    {
        return in_array($role, ['admin', 'supervisor', 'student'], true) ? $role : 'admin';
    }

    protected function scopeSessionToRole(Request $request, CurrentTenant $currentTenant, string $role): void
    {
        $baseCookie = Str::slug((string) config('app.name', 'laravel')).'-session';
        $cookie = $baseCookie;

        $request->session()->setName($cookie);

        config([
            'session.cookie' => $cookie,
            'session.path' => '/',
            'session.domain' => null,
        ]);
    }
}
