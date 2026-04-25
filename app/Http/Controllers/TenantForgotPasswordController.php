<?php

namespace App\Http\Controllers;

use App\Mail\TenantPasswordResetCodeMail;
use App\Models\TenantUser;
use App\Support\Tenancy\CurrentTenant;
use App\Support\Tenancy\TenantUrlGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class TenantForgotPasswordController extends Controller
{
    public function create(CurrentTenant $currentTenant, ?string $role = null): View
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);

        return view('tenant.auth.forgot-password', [
            'tenant' => $tenant,
            'pageTitle' => 'Forgot Password | '.$this->portalTitle($tenant),
            'selectedLoginRole' => $this->normalizeRole($role),
            'sendCodeAction' => $this->sendCodeAction($role),
            'loginUrl' => $this->loginUrl($role),
        ]);
    }

    public function store(Request $request, CurrentTenant $currentTenant, ?string $role = null): RedirectResponse
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);

        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = $this->userFor($validated['email'], $this->normalizeRole($role));

        if (! $user) {
            $message = app()->isLocal()
                ? 'No account was found for that email in this tenant portal.'
                : 'If that email exists in this tenant portal, a password reset code has been sent.';

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['email' => $message]);
        }

        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'password_reset_code' => Hash::make($code),
            'password_reset_expires_at' => now()->addMinutes(15),
        ])->save();

        try {
            Mail::to($user->email)->send(new TenantPasswordResetCodeMail(
                $tenant,
                $this->displayName($user),
                $code,
                app(TenantUrlGenerator::class),
            ));
        } catch (Throwable $exception) {
            report($exception);

            if (app()->isLocal()) {
                Log::warning('tenant_password_reset_code_mail_failed', [
                    'email' => $user->email,
                    'role' => $user->role,
                    'code' => $code,
                    'error' => $exception->getMessage(),
                ]);

                return redirect()
                    ->to($this->resetUrl($role).'?email='.urlencode($validated['email']))
                    ->with('status', "Email could not be sent with the current MAIL settings. Development reset code: {$code}");
            }

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['email' => 'The reset code could not be emailed right now. Please contact your administrator.']);
        }

        return redirect()
            ->to($this->resetUrl($role).'?email='.urlencode($validated['email']))
            ->with('status', 'If that email exists in this tenant portal, a password reset code has been sent.');
    }

    public function edit(CurrentTenant $currentTenant, ?string $role = null): View
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);

        return view('tenant.auth.reset-password', [
            'tenant' => $tenant,
            'pageTitle' => 'Reset Password | '.$this->portalTitle($tenant),
            'selectedLoginRole' => $this->normalizeRole($role),
            'resetAction' => $this->resetAction($role),
            'requestCodeUrl' => $this->forgotUrl($role),
        ]);
    }

    public function update(Request $request, CurrentTenant $currentTenant, ?string $role = null): RedirectResponse
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $this->userFor($validated['email'], $this->normalizeRole($role));

        if (! $user || ! $this->validCode($user, $validated['code'])) {
            throw ValidationException::withMessages([
                'code' => 'The reset code is invalid or has expired.',
            ]);
        }

        $user->forceFill([
            'password' => $validated['password'],
            'must_change_password' => false,
            'password_reset_code' => null,
            'password_reset_expires_at' => null,
            'remember_token' => null,
        ])->save();

        return redirect()
            ->to($this->loginUrl($role))
            ->with('status', 'Password reset successfully. You can now sign in with your new password.');
    }

    protected function userFor(string $email, ?string $role): ?TenantUser
    {
        $query = TenantUser::query()->where('email', $email);

        if ($role) {
            $query->where('role', $role === 'admin' ? 'admin' : $role);
        }

        return $query->first();
    }

    protected function validCode(TenantUser $user, string $code): bool
    {
        return filled($user->password_reset_code)
            && $user->password_reset_expires_at?->isFuture()
            && Hash::check($code, (string) $user->password_reset_code);
    }

    protected function displayName(TenantUser $user): string
    {
        return $user->full_name ?: ($user->name ?: 'Portal user');
    }

    protected function normalizeRole(?string $role): ?string
    {
        return in_array($role, ['admin', 'supervisor', 'student'], true) ? $role : null;
    }

    protected function sendCodeAction(?string $role): string
    {
        return $this->normalizeRole($role)
            ? route('tenant.password.email.role', ['role' => $role], false)
            : route('tenant.password.email', [], false);
    }

    protected function resetAction(?string $role): string
    {
        return $this->normalizeRole($role)
            ? route('tenant.password.update.role', ['role' => $role], false)
            : route('tenant.password.update', [], false);
    }

    protected function forgotUrl(?string $role): string
    {
        return $this->normalizeRole($role)
            ? route('tenant.password.request.role', ['role' => $role], false)
            : route('tenant.password.request', [], false);
    }

    protected function resetUrl(?string $role): string
    {
        return $this->normalizeRole($role)
            ? route('tenant.password.reset.role', ['role' => $role], false)
            : route('tenant.password.reset', [], false);
    }

    protected function loginUrl(?string $role): string
    {
        return $this->normalizeRole($role)
            ? route('tenant.login', ['role' => $role], false)
            : route('tenant.login.default', [], false);
    }

    protected function portalTitle($tenant): string
    {
        return data_get($tenant->settings, 'branding.portal_title', config('app.name', 'University Practicum'));
    }
}
