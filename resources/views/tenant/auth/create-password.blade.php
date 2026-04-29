@php
    $layoutMode = 'login';
    $hideTenantHeader = true;
    $tenantBranding = is_array($tenant->settings['branding'] ?? null) ? $tenant->settings['branding'] : [];
    $tenantPortalTitle = filled($tenantBranding['portal_title'] ?? null)
        ? $tenantBranding['portal_title']
        : config('app.name', 'University Practicum');
    $systemLogo = filled($tenantBranding['logo_path'] ?? null)
        ? asset($tenantBranding['logo_path'])
        : asset('images/logos/logo.jpeg');
@endphp

@extends('layouts.tenant')

@section('content')
    <section class="first-login-shell">
        <article class="auth-card auth-card-wide first-login-card">
            <aside class="first-login-panel">
                <div class="first-login-brand">
                    <div class="first-login-logo-wrap">
                        <img src="{{ $systemLogo }}" alt="{{ $tenantPortalTitle }} Logo" class="first-login-logo">
                    </div>
                    <span class="app-section-kicker">First Login</span>
                </div>

                <div class="first-login-summary">
                    <p class="first-login-eyebrow">{{ $tenantPortalTitle }}</p>
                    <h1>Create a New Password</h1>
                    <p>
                        Your account was created with a temporary password. Create your personal password now before continuing to the university portal.
                    </p>
                </div>

                <div class="first-login-tenant">
                    <strong>{{ $tenant->name }}</strong>
                    <span>Password Setup Required</span>
                </div>

                <div class="auth-note first-login-note">
                    <strong>Next step</strong>
                    <p>After saving your new password, you will be sent directly to the tenant admin dashboard.</p>
                </div>
            </aside>

            <div class="first-login-form-area">
                @if ($errors->any())
                    <div class="error-panel">
                        <strong>Password not updated.</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ $passwordSetupAction }}" class="first-login-form">
                    @csrf
                    <label>
                        New Password
                        <input type="password" name="password" placeholder="Create a strong password" required>
                    </label>
                    <label>
                        Confirm Password
                        <input type="password" name="password_confirmation" placeholder="Confirm your new password" required>
                    </label>
                    <button type="submit">Save New Password</button>
                </form>
            </div>
        </article>
    </section>
@endsection
