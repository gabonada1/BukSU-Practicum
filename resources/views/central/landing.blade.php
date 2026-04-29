@php
    $layoutMode = 'dashboard';
    $hideCentralHeader = true;
    $systemLogo = asset('images/logos/logo.jpeg');
    $showApplicationModal = $errors->any() || old('college_name') || old('contact_name') || old('admin_email');
    $rolloutSteps = [
        [
            'title' => 'Apply and choose a plan',
            'copy' => 'Share your college details, preferred workspace name, and rollout notes in one guided intake form.',
        ],
        [
            'title' => 'Review and approval',
            'copy' => 'The platform team reviews the request, verifies setup details, and confirms the subscription before provisioning.',
        ],
        [
            'title' => 'Launch your portal',
            'copy' => 'Your coordinator receives access, then students and supervisors move into the tenant workspace.',
        ],
    ];
    $platformHighlights = [
        [
            'title' => 'Student application tracking',
            'copy' => 'Monitor submissions, approvals, deployment, and supporting documents without scattered spreadsheets.',
        ],
        [
            'title' => 'Supervisor and company coordination',
            'copy' => 'Keep partner organizations, available slots, and supervisor records organized in one portal.',
        ],
        [
            'title' => 'Requirements and OJT progress',
            'copy' => 'Handle forms, logs, evaluations, and completion tracking with role-based access.',
        ],
    ];
    $planCtas = [
        'basic' => 'Start with Basic',
        'pro' => 'Choose Pro',
        'premium' => 'Go Premium',
    ];
    $planBadges = [
        'basic' => 'Starter',
        'pro' => 'Most practical',
        'premium' => 'Full access',
    ];
@endphp

@extends('layouts.central')

@section('content')
    <section class="landing-shell landing-shell-enhanced">
        <section class="landing-hero landing-hero-enhanced">
            <div class="landing-stack landing-hero-copy">
                <span class="app-section-kicker">University Practicum Platform</span>
                <h1>One OJT workspace for applications, documents, hours, and evaluations.</h1>
                <p>
                    Give every college its own secure portal where coordinators can approve placements, students can submit
                    requirements, and supervisors can keep progress visible from deployment to completion.
                </p>

                <div class="hero-actions">
                    <button type="button" class="button landing-apply-button" data-landing-modal-open>Apply for a Plan</button>
                    <a href="{{ $centralLoginUrl }}" class="button secondary">Central Admin Login</a>
                </div>

                <div class="landing-trust-row">
                    <span class="metric-pill">Tenant-based architecture</span>
                    <span class="metric-pill">Role-based access</span>
                    <span class="metric-pill">Practicum-first workflows</span>
                </div>
            </div>

            <div class="landing-hero-visual landing-hero-panel">
                <div class="landing-portal-preview">
                    <div class="landing-preview-header">
                        <div class="landing-preview-logo">
                            <img src="{{ $systemLogo }}" alt="University Practicum Logo">
                        </div>
                        <div>
                            <span>Tenant Portal</span>
                            <strong>OJT Operations</strong>
                        </div>
                    </div>

                    <div class="landing-preview-flow">
                        <span>Applications</span>
                        <span>Requirements</span>
                        <span>Hour Logs</span>
                        <span>Supervisor Review</span>
                    </div>

                    <div class="landing-preview-list">
                        <div>
                            <strong>Students</strong>
                            <span>Submit applications and upload requirements</span>
                        </div>
                        <div>
                            <strong>Coordinators</strong>
                            <span>Review records, courses, hours, and approvals</span>
                        </div>
                        <div>
                            <strong>Supervisors</strong>
                            <span>Monitor assigned interns and validate logs</span>
                        </div>
                    </div>
                </div>
                <article class="hero-stat hero-stat-primary">
                    <span>Active Tenants</span>
                    <strong>{{ $stats['active_tenants'] }}</strong>
                    <p>College workspaces currently running their practicum operations inside the platform.</p>
                </article>
                <article class="hero-stat">
                    <span>Pending Applications</span>
                    <strong>{{ $stats['submitted_applications'] }}</strong>
                    <p>Requests waiting for provisioning review and subscription approval.</p>
                </article>
                <article class="hero-stat">
                    <span>Premium Tenants</span>
                    <strong>{{ $stats['premium_tenants'] }}</strong>
                    <p>Advanced tenant workspaces using the full practicum toolkit.</p>
                </article>
            </div>
        </section>

        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif

        @if ($errors->any() && ! $showApplicationModal)
            <div class="error-panel">
                <strong>Application not submitted.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="landing-detail-grid">
            <article class="section-card landing-story-card">
                <div class="section-header">
                    <div>
                        <span class="mini-kicker">Why it works</span>
                        <h2>Built around the actual OJT lifecycle</h2>
                        <p>Each tenant portal is shaped around the work colleges really do every semester, not just generic user management.</p>
                    </div>
                </div>

                <div class="benefit-grid">
                    @foreach ($platformHighlights as $highlight)
                        <article class="benefit-card">
                            <h3>{{ $highlight['title'] }}</h3>
                            <p>{{ $highlight['copy'] }}</p>
                        </article>
                    @endforeach
                </div>
            </article>

            <article class="section-card landing-rollout-card">
                <div class="section-header">
                    <div>
                        <span class="mini-kicker">Launch Flow</span>
                        <h2>From request to live portal</h2>
                    </div>
                </div>

                <div class="landing-rollout-list">
                    @foreach ($rolloutSteps as $index => $step)
                        <article class="landing-rollout-step">
                            <span class="landing-rollout-number">{{ $index + 1 }}</span>
                            <div>
                                <strong>{{ $step['title'] }}</strong>
                                <p>{{ $step['copy'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </article>
        </section>

        <section class="section-card landing-pricing-card" id="plans">
            <div class="section-header landing-pricing-header">
                <div>
                    <span class="mini-kicker">Plans</span>
                    <h2>Pick the access level that matches your practicum operation</h2>
                    <p>Each plan includes a tenant workspace, guided onboarding, and the workflows needed to run university OJT from one place.</p>
                </div>
            </div>

            <div class="plan-grid landing-plan-grid">
                @foreach ($plans as $planKey => $plan)
                    <article class="plan-card landing-plan-card landing-plan-card-{{ $planKey }}">
                        <div class="landing-plan-top">
                            <span class="plan-badge">{{ $planBadges[$planKey] ?? 'Plan' }}</span>
                            <span class="plan-price">PHP {{ number_format($plan['amount'] / 100, 2) }}</span>
                        </div>
                        <div>
                            <h2>{{ $plan['label'] }}</h2>
                            <p>{{ $plan['summary'] }}</p>
                        </div>

                        <ul class="clean-list landing-plan-feature-list">
                            @foreach ($plan['features'] as $feature)
                                <li>{{ $feature }}</li>
                            @endforeach
                        </ul>

                        <button type="button" class="button landing-plan-select-button" data-landing-modal-open data-plan-choice="{{ $planKey }}">
                            {{ $planCtas[$planKey] ?? 'Apply for this plan' }}
                        </button>
                    </article>
                @endforeach
            </div>
        </section>

        <article class="section-card landing-benefits-card">
            <div class="section-header">
                <div>
                    <span class="mini-kicker">Why colleges use this system</span>
                    <h2>One platform for university practicum rollout and day-to-day execution</h2>
                </div>
            </div>

            <div class="benefit-grid">
                @foreach ($benefits as $index => $benefit)
                    <article class="benefit-card">
                        <h3>{{ ['Clean tenant records', 'Role-based portals', 'Complete OJT workflow', 'Controlled rollout'][$index] ?? 'Practicum operations' }}</h3>
                        <p>{{ $benefit }}</p>
                    </article>
                @endforeach
            </div>
        </article>

        <article class="section-card landing-cta-card">
            <div class="section-header">
                <div>
                    <span class="mini-kicker">Ready to launch?</span>
                    <h2>Start your college workspace application</h2>
                    <p>Submit your institution details, choose a plan, and continue into the payment and provisioning flow.</p>
                </div>
                <div class="hero-actions">
                    <button type="button" class="button" data-landing-modal-open>Open Application Form</button>
                </div>
            </div>
        </article>

        <div class="modal-shell landing-application-modal-shell" @if (! $showApplicationModal) hidden aria-hidden="true" @else aria-hidden="false" @endif data-landing-modal>
            <div class="modal-card landing-application-modal" role="dialog" aria-modal="true" aria-labelledby="landing-application-title">
                <div class="modal-header">
                    <div>
                        <span class="mini-kicker">Tenant Onboarding</span>
                        <h3 id="landing-application-title">Apply for a new college workspace</h3>
                        <p>Tell us about your institution, preferred plan, and rollout requirements.</p>
                    </div>
                    <button type="button" class="modal-close-button" data-landing-modal-close aria-label="Close application form">&times;</button>
                </div>

                @if ($errors->any())
                    <div class="register-modal-feedback error-panel">
                        <strong>Application not submitted.</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ $applyAction }}" class="landing-application-form">
                    @csrf
                    <div class="form-grid">
                        <label>
                            University Name
                            <input type="text" name="college_name" value="{{ old('college_name') }}" placeholder="University Practicum - College of Technologies" required>
                        </label>
                        <label>
                            Contact Person
                            <input type="text" name="contact_name" value="{{ old('contact_name') }}" placeholder="Dean or Coordinator" required>
                        </label>
                        <label>
                            Contact Email
                            <input type="email" name="contact_email" value="{{ old('contact_email') }}" placeholder="dean@buksu.edu.ph" required>
                        </label>
                        <label>
                            Contact Phone
                            <input type="text" name="contact_phone" value="{{ old('contact_phone') }}" placeholder="09xxxxxxxxx">
                        </label>
                        <label>
                            Internship Coordinator Email
                            <input type="email" name="admin_email" value="{{ old('admin_email') }}" placeholder="coordinator@buksu.edu.ph" required>
                        </label>
                        <label>
                            Selected Plan
                            <select name="selected_plan" required>
                                @foreach ($plans as $planKey => $plan)
                                    <option value="{{ $planKey }}" @selected(old('selected_plan', 'premium') === $planKey)>
                                        {{ $plan['label'] }} - PHP {{ number_format($plan['amount'] / 100, 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            Preferred Subdomain
                            <input type="text" name="preferred_subdomain" value="{{ old('preferred_subdomain') }}" placeholder="nursing">
                        </label>
                        <label>
                            Preferred Domain
                            <input type="text" name="preferred_domain" value="{{ old('preferred_domain') }}" placeholder="Optional custom domain">
                        </label>
                        <label class="field-span-2">
                            Notes
                            <textarea name="notes" rows="4" placeholder="Tell us about your rollout timeline, practicum setup, or preferred go-live date.">{{ old('notes') }}</textarea>
                        </label>
                    </div>

                    <div class="modal-actions landing-application-actions">
                        <button type="submit">Continue to Payment</button>
                        <button type="button" class="button secondary" data-landing-modal-close>Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.querySelector('[data-landing-modal]');

            if (! modal) {
                return;
            }

            const openModal = function () {
                modal.removeAttribute('hidden');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('modal-open');
            };

            const closeModal = function () {
                modal.setAttribute('hidden', 'hidden');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
            };

            document.querySelectorAll('[data-landing-modal-open]').forEach(function (trigger) {
                trigger.addEventListener('click', function () {
                    const planChoice = trigger.getAttribute('data-plan-choice');
                    const planSelect = modal.querySelector('select[name="selected_plan"]');

                    if (planChoice && planSelect) {
                        planSelect.value = planChoice;
                    }

                    openModal();
                });
            });

            document.querySelectorAll('[data-landing-modal-close]').forEach(function (trigger) {
                trigger.addEventListener('click', closeModal);
            });

            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal();
                }
            });
        });
    </script>
@endsection
