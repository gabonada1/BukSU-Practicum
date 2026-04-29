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
    <article class="auth-card auth-card-large">
        <div class="auth-brand">
            <div class="auth-brand-logo">
                <img src="{{ $systemLogo }}" alt="{{ $tenantPortalTitle }} Logo">
            </div>
            <span class="app-section-kicker">Password Reset</span>
            <h1>Create a new password</h1>
            <p>Use the 6-digit code sent to your email for {{ $tenant->name }}.</p>
        </div>

        @if ($errors->any())
            <div class="error-panel">
                <strong>Password reset failed.</strong>
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

        <form method="POST" action="{{ $resetAction }}" class="register-auth-form">
            @csrf
            @if ($selectedLoginRole)
                <input type="hidden" name="role" value="{{ $selectedLoginRole }}">
            @endif
            <label>
                Email
                <input type="email" name="email" value="{{ old('email', request('email')) }}" placeholder="you@buksu.edu.ph" required autofocus>
            </label>
            <label>
                Reset Code
                <input type="text" name="code" value="{{ old('code') }}" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="123456" required>
            </label>
            <label>
                New Password
                <input type="password" name="password" placeholder="At least 8 characters" required>
            </label>
            <label>
                Confirm New Password
                <input type="password" name="password_confirmation" placeholder="Repeat your new password" required>
            </label>
            <button type="submit">Reset Password</button>
        </form>

        <div class="hero-actions">
            <a href="{{ $requestCodeUrl }}" class="button secondary auth-register-link">Request New Code</a>
        </div>
    </article>
@endsection
