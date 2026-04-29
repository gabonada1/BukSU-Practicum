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
            <h1>{{ $tenant->name }}</h1>
            <p>Enter your email and we will send a 6-digit reset code.</p>
        </div>

        @if ($errors->any())
            <div class="error-panel">
                <strong>Reset request failed.</strong>
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

        <form method="POST" action="{{ $sendCodeAction }}" class="register-auth-form">
            @csrf
            @if ($selectedLoginRole)
                <input type="hidden" name="role" value="{{ $selectedLoginRole }}">
            @endif
            <label>
                Email
                <input type="email" name="email" value="{{ old('email', request('email')) }}" placeholder="you@buksu.edu.ph" required autofocus>
            </label>
            <button type="submit">Send Reset Code</button>
        </form>

        <div class="hero-actions">
            <a href="{{ $loginUrl }}" class="button secondary auth-register-link">Back to Sign In</a>
        </div>
    </article>
@endsection
