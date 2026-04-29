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
    $emailPlaceholder = 'you@buksu.edu.ph';
    $selectedLoginRole = $selectedLoginRole ?? null;
    $tenantAccessLabel = preg_replace('#^https?://#', '', app(\App\Support\Tenancy\TenantUrlGenerator::class)->loginUrl($tenant));
    $forgotPasswordUrl = $selectedLoginRole
        ? route('tenant.password.request.role', ['role' => $selectedLoginRole], false)
        : route('tenant.password.request', [], false);
@endphp

@extends('layouts.tenant')

@section('content')
    <article class="auth-card auth-card-large">
        <div class="auth-brand">
            <div class="auth-brand-logo">
                <img src="{{ $systemLogo }}" alt="{{ $tenantPortalTitle }} Logo">
            </div>
            <span class="app-section-kicker">Tenant Access</span>
            <h1>{{ $tenant->name }}</h1>
            <p>{{ $tenantPortalTitle }} · {{ $tenantAccessLabel }}</p>
        </div>

        @if ($errors->any())
            <div class="error-panel">
                <strong>Login failed.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ $loginAction }}" class="register-auth-form">
            @csrf
            @if ($selectedLoginRole)
                <input type="hidden" name="role" value="{{ $selectedLoginRole }}">
            @endif
            <label>
                Email
                <input type="email" name="email" value="{{ old('email') }}" placeholder="{{ $emailPlaceholder }}" required>
            </label>
            <label>
                Password
                <input type="password" name="password" placeholder="Enter your password" required>
            </label>
            <div class="auth-inline-actions">
                <a href="{{ $forgotPasswordUrl }}">Forgot password?</a>
            </div>
            <label class="checkline">
                <input type="checkbox" name="remember" value="1">
                <span>Keep me signed in on this device</span>
            </label>
            <button type="submit">Sign In</button>
        </form>

        <div class="hero-actions">
            <a href="{{ $registerUrl }}" class="button secondary auth-register-link">Register</a>
        </div>
    </article>
@endsection
