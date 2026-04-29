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
    $selectedRole = in_array($selectedRole ?? null, ['student', 'teacher'], true) ? $selectedRole : null;
    $hasCourses = $courses->isNotEmpty();
    $departmentOptions = $courses->map(fn ($course) => trim($course->code.' - '.$course->name));
    $registerBaseUrl = $registerPageUrl;
@endphp

@extends('layouts.tenant')

@section('content')
    <section class="register-shell">
        <article class="auth-card auth-card-wide register-stack-card">
            <div class="register-panel">
                <div class="register-panel-brand">
                    <div class="first-login-logo-wrap">
                        <img src="{{ $systemLogo }}" alt="{{ $tenantPortalTitle }} Logo" class="first-login-logo">
                    </div>
                    <span class="app-section-kicker">Portal Registration</span>
                </div>

                <div class="register-panel-copy">
                    <p class="first-login-eyebrow">{{ $tenantPortalTitle }}</p>
                    <h1>Register for {{ $tenant->name }}</h1>
                    <p>Select the account type you need, then continue with the matching registration form.</p>
                </div>

                <div class="register-role-grid">
                    <a href="{{ $registerPageUrl }}?role=student" class="register-role-card{{ $selectedRole === 'student' ? ' is-active' : '' }}">
                        <strong>Student</strong>
                        <p>Apply for internships, upload requirements, and track approved hours.</p>
                    </a>
                    <a href="{{ $registerPageUrl }}?role=teacher" class="register-role-card{{ $selectedRole === 'teacher' ? ' is-active' : '' }}">
                        <strong>Company Supervisor</strong>
                        <p>Monitor assigned interns, validate logs, and manage evaluations.</p>
                    </a>
                </div>

                <a href="{{ $loginUrl }}" class="button auth-primary-link register-back-link">Back to Login</a>
            </div>
        </article>

        @if ($selectedRole)
            <div class="modal-shell" aria-hidden="false">
                <div class="modal-card register-modal-card" role="dialog" aria-modal="true" aria-labelledby="register-modal-title">
                    <div class="modal-header">
                        <div>
                            <h3 id="register-modal-title">{{ $selectedRole === 'student' ? 'Student Registration' : 'Company Supervisor Registration' }}</h3>
                            <p>
                                {{ $selectedRole === 'student'
                                    ? 'Complete your student account details to access the practicum portal.'
                                    : 'Complete your supervisor account details to manage intern activity.' }}
                            </p>
                        </div>
                        <a href="{{ $registerBaseUrl }}" class="modal-close-button" aria-label="Close registration modal">&times;</a>
                    </div>

                    @if ($errors->any())
                        <div class="register-modal-feedback error-panel">
                            <strong>Registration failed.</strong>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($selectedRole === 'student')
                        <form method="POST" action="{{ $registerAction }}" class="register-auth-form">
                            @csrf
                            <input type="hidden" name="role" value="student">
                            <div class="form-grid">
                                <label>Student Number <input type="text" name="student_number" value="{{ old('student_number') }}" required></label>
                                <label>First Name <input type="text" name="first_name" value="{{ old('first_name') }}" required></label>
                                <label>Last Name <input type="text" name="last_name" value="{{ old('last_name') }}" required></label>
                                <label>Email <input type="email" name="email" value="{{ old('email') }}" required></label>

                                @if ($hasCourses)
                                    <label class="field-span-2">
                                        Course / Program
                                        <select name="course_id">
                                            <option value="">Select course</option>
                                            @foreach ($courses as $course)
                                                <option value="{{ $course->id }}" @selected((string) old('course_id') === (string) $course->id)>
                                                    {{ $course->code }} - {{ $course->name }} ({{ number_format($course->required_ojt_hours, 0) }} hrs)
                                                </option>
                                            @endforeach
                                        </select>
                                    </label>
                                @else
                                    <label class="field-span-2">
                                        Program
                                        <input type="text" name="program" value="{{ old('program', 'BS Information Technology') }}">
                                    </label>
                                @endif

                                <label>Password <input type="password" name="password" required></label>
                                <label>Confirm Password <input type="password" name="password_confirmation" required></label>
                            </div>

                            <div class="hero-actions register-form-actions">
                                <button type="submit">Register Student</button>
                            </div>
                        </form>
                    @else
                        <form method="POST" action="{{ $registerAction }}" class="register-auth-form">
                            @csrf
                            <input type="hidden" name="role" value="teacher">
                            <div class="form-grid">
                                <label>Full Name <input type="text" name="name" value="{{ old('name') }}" required></label>
                                <label>Email <input type="email" name="email" value="{{ old('email') }}" required></label>
                                <label class="field-span-2">
                                    Company
                                    <select name="partner_company_id" required>
                                        <option value="">Select company</option>
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}" @selected((string) old('partner_company_id') === (string) $company->id)>{{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                    @if ($companies->isEmpty())
                                        <small>No active partner companies are available yet. Ask the tenant admin to add one before supervisor registration.</small>
                                    @endif
                                </label>

                                @if ($departmentOptions->isNotEmpty())
                                    <label>
                                        Department / Unit
                                        <select name="department" required>
                                            <option value="">Select course</option>
                                            @foreach ($departmentOptions as $option)
                                                <option value="{{ $option }}" @selected(old('department') === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                @else
                                    <label>Department / Unit <input type="text" name="department" value="{{ old('department', $tenant->name) }}" required></label>
                                @endif

                                <label>Position / Title <input type="text" name="position" value="{{ old('position', 'Company Supervisor') }}" required></label>
                                <label>Password <input type="password" name="password" required></label>
                                <label>Confirm Password <input type="password" name="password_confirmation" required></label>
                            </div>

                            <div class="hero-actions register-form-actions">
                                <button type="submit">Register Company Supervisor</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        @endif
    </section>
@endsection
