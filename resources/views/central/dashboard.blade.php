@php
    $layoutMode = 'dashboard';
    $currentSection = request()->query('section', 'overview');
    $reviewId = (int) request()->query('review');
    $editId = (int) request()->query('edit');
    $dashboardBaseUrl = route('central.dashboard');
    $sections = ['overview' => 'Overview', 'applications' => 'Applications', 'directory' => 'College Directory'];

    if (! array_key_exists($currentSection, $sections)) {
        $currentSection = 'overview';
    }

    $reviewingApplication = $applications->firstWhere('id', $reviewId);
    $editingTenant = $tenants->firstWhere('id', $editId);
    $pendingApplications = $applications->whereIn('status', ['submitted', 'pending_approval']);
    $recentApplications = $applications->take(5);
    $recentTenants = $tenants->take(5);
    $premiumCount = $tenants->where('plan', 'premium')->count();
    $bandwidthGraphProfiles = $tenantBandwidthProfiles->sortByDesc('used_gb')->take(6)->values();
    $bandwidthGraphMax = max(1, (float) $bandwidthGraphProfiles->max('limit_gb'));
    $recentActivity = collect()
        ->merge($tenants->take(4)->map(fn ($tenant) => [
            'title' => 'Created tenant: '.$tenant->name,
            'meta' => 'System Administrator - '.optional($tenant->created_at)->format('m/d/Y'),
        ]))
        ->merge($applications->take(4)->map(fn ($application) => [
            'title' => ucfirst(str_replace('_', ' ', $application->status)).' application: '.$application->college_name,
            'meta' => 'Central Admin - '.optional($application->created_at)->format('m/d/Y'),
        ]))
        ->take(5);
    $centralActor = auth('central_superadmin')->user();
    $auditLogPages = $auditLogs->isEmpty() ? collect([collect()]) : $auditLogs->chunk(12)->values();
@endphp

@extends('layouts.central')

@section('content')
    @if (session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="error-panel">
            <strong>Action not completed.</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($currentSection === 'overview')
        <section class="section-card audit-toolbar-card">
            <div class="section-header">
                <div>
                    <span class="mini-kicker">Security</span>
                    <h2>Audit Logs</h2>
                    <p>Review recent recorded system activity from the central dashboard.</p>
                </div>
                <button type="button" class="secondary" data-audit-open>
                    <i class="fa-solid fa-clipboard-list"></i>
                    Audit Logs
                </button>
            </div>
        </section>

        <section class="metric-grid">
            <article class="metric-card">
                <span>Active Tenants</span>
                <strong>{{ $stats['active_tenants'] }}</strong>
                <small>College portals currently accessible.</small>
            </article>
            <article class="metric-card">
                <span>Suspended</span>
                <strong>{{ $stats['suspended_tenants'] }}</strong>
                <small>Tenants temporarily blocked from access.</small>
            </article>
            <article class="metric-card">
                <span>Expiring Soon</span>
                <strong>{{ $stats['expiring_tenants'] }}</strong>
                <small>Subscriptions ending within 30 days.</small>
            </article>
            <article class="metric-card">
                <span>Recent Applications</span>
                <strong>{{ $recentApplications->count() }}</strong>
                <small>Newest tenant requests in the review queue.</small>
            </article>
        </section>

        <section class="content-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
            <article class="section-card">
                <div class="table-header">
                    <div>
                        <h2>Recent Tenants</h2>
                        <p>Latest provisioned colleges and their current usage.</p>
                    </div>
                </div>

                @if ($recentTenants->isEmpty())
                    <p>No tenant records yet.</p>
                @else
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Status</th>
                                    <th>Bandwidth</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentTenants as $tenant)
                                    @php $tenantProfile = $tenantBandwidthProfiles->get($tenant->id); @endphp
                                    <tr>
                                        <td><strong>{{ $tenant->name }}</strong></td>
                                        <td>{{ $tenant->code ?: 'N/A' }}</td>
                                        <td><span class="table-badge">{{ ucfirst($tenant->subscriptionStatus()) }}</span></td>
                                        <td>{{ number_format((float) ($tenantProfile['used_gb'] ?? 0), 0) }} GB</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </article>

            <article class="section-card">
                <div class="table-header">
                    <div>
                        <h2>Recent Activity</h2>
                        <p>Latest tenant and application movement across the system.</p>
                    </div>
                </div>

                <ul class="clean-list">
                    @foreach ($recentActivity as $item)
                        <li>
                            <strong>{{ $item['title'] }}</strong>
                            <p>{{ $item['meta'] }}</p>
                        </li>
                    @endforeach
                </ul>
            </article>
        </section>

        <section class="section-card">
            <div class="section-header">
                <div>
                    <span class="mini-kicker">Bandwidth</span>
                    <h2>Tenant bandwidth usage</h2>
                    <p>Compare recent tenant usage against allocated plan capacity.</p>
                </div>
                <span class="status-pill">{{ number_format($bandwidthTotals['utilization_pct'], 1) }}% used</span>
            </div>

            @if ($bandwidthGraphProfiles->isEmpty())
                <p>No tenants available yet.</p>
            @else
                <div class="stats-grid">
                    @foreach ($bandwidthGraphProfiles as $profile)
                        @php
                            $limitHeight = 100;
                            $usedHeight = max(6, (int) round(($profile['used_gb'] / $bandwidthGraphMax) * 100));
                        @endphp
                        <article class="metric-card">
                            <span>{{ $profile['tenant']->name }}</span>
                            <strong>{{ number_format($profile['used_gb'], 0) }} GB</strong>
                            <small>{{ number_format($profile['limit_gb'], 0) }} GB capacity</small>
                            <div class="progress-track"><span style="width: {{ min(100, (int) round(($profile['used_gb'] / max(1, $profile['limit_gb'])) * 100)) }}%"></span></div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    @if ($currentSection === 'applications')
        <section class="dashboard-grid">
            @if ($reviewingApplication)
                @php
                    $suggestedSubdomain = \App\Http\Controllers\Central\PlanApplicationController::suggestedSubdomain($reviewingApplication->college_name);
                    $suggestedDatabase = \App\Http\Controllers\Central\PlanApplicationController::suggestedDatabaseName(
                        $reviewingApplication->college_name,
                        $reviewingApplication->admin_email,
                        $reviewingApplication->getKey(),
                    );
                    $suggestedBandwidth = $planBandwidthDefaults[$reviewingApplication->selected_plan] ?? 150;
                @endphp

                <div class="modal-shell" aria-hidden="false">
                <article class="section-card modal-card modal-card-wide">
                    <div class="section-header">
                        <div>
                            <h2>Review {{ $reviewingApplication->college_name }}</h2>
                            <p>{{ $reviewingApplication->contact_name }} · {{ $reviewingApplication->admin_email }}</p>
                        </div>
                        <a class="modal-close-button" href="{{ $dashboardBaseUrl.'?section=applications' }}" aria-label="Close review modal">&times;</a>
                    </div>

                    <div class="metric-grid">
                        <article class="metric-card">
                            <span>Plan</span>
                            <strong>{{ strtoupper($reviewingApplication->selected_plan) }}</strong>
                            <small>Payment: {{ strtoupper($reviewingApplication->payment_status) }}</small>
                        </article>
                        <article class="metric-card">
                            <span>Requested Access</span>
                            <strong>{{ $reviewingApplication->preferred_subdomain ?: 'No subdomain requested' }}</strong>
                            <small>{{ $reviewingApplication->preferred_domain ?: 'No custom domain requested' }}</small>
                        </article>
                    </div>

                    <form method="POST" action="{{ route('central.plan-applications.approve', $reviewingApplication) }}">
                        @csrf
                        <div class="form-grid">
                            <label>
                                Tenant Database
                                <input type="text" value="{{ $suggestedDatabase }}" readonly>
                                <small>A hashed database name will be created automatically once this application is approved.</small>
                            </label>
                            <label>Subdomain<input type="text" name="subdomain" value="{{ old('subdomain', $reviewingApplication->preferred_subdomain ?: $suggestedSubdomain) }}"></label>
                            <label>Domain<input type="text" name="domain" value="{{ old('domain', $reviewingApplication->preferred_domain) }}" placeholder="Optional custom domain"></label>
                            <label>Subscription Starts<input type="date" name="subscription_starts_at" value="{{ old('subscription_starts_at', now()->toDateString()) }}" required></label>
                            <label>Subscription Expires<input type="date" name="subscription_expires_at" value="{{ old('subscription_expires_at', now()->addMonth()->toDateString()) }}"></label>
                            <label>Bandwidth Allocation (GB)<input type="number" min="1" step="1" name="bandwidth_limit_gb" value="{{ old('bandwidth_limit_gb', $suggestedBandwidth) }}" required></label>
                            <label>Current Usage (GB)<input type="number" min="0" step="0.1" name="bandwidth_used_gb" value="{{ old('bandwidth_used_gb', 0) }}"></label>
                            <label>
                                Coordinator Password
                                <div class="input-action-row">
                                    <input id="generated-admin-password" type="text" name="admin_password" value="{{ old('admin_password', app(\App\Support\Security\PasswordGenerator::class)->generate()) }}" required>
                                    <button type="button" class="button secondary input-action-button" data-generate-password data-target="#generated-admin-password">Generate Password</button>
                                </div>
                                <small>This exact temporary password will be stored for the coordinator account and sent by email.</small>
                            </label>
                            <label class="field-span-2">Approval Notes<textarea name="approval_notes" rows="3" placeholder="Optional notes for this approval">{{ old('approval_notes') }}</textarea></label>
                        </div>

                        <div class="hero-actions">
                            <button type="submit">Approve and Provision Tenant</button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('central.plan-applications.reject', $reviewingApplication) }}">
                        @csrf
                        <label>Rejection Reason<textarea name="rejection_reason" rows="3" placeholder="Explain why this application cannot be approved yet." required>{{ old('rejection_reason') }}</textarea></label>
                        <div class="hero-actions">
                            <button type="submit" class="danger">Reject Application</button>
                        </div>
                    </form>
                </article>
                </div>
            @endif

            <article class="section-card">
                <div class="table-header">
                    <div>
                        <h2>Plan Applications</h2>
                        <p>{{ $applications->count() }} total submissions across all colleges.</p>
                    </div>
                </div>

                @if ($applications->isEmpty())
                    <p>No plan applications submitted yet.</p>
                @else
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>College</th>
                                    <th>Plan</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Requested Access</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($applications as $application)
                                    <tr>
                                        <td>
                                            <strong>{{ $application->college_name }}</strong>
                                            <p>{{ $application->contact_name }} · {{ $application->admin_email }}</p>
                                        </td>
                                        <td><span class="table-badge">{{ strtoupper($application->selected_plan) }}</span></td>
                                        <td><span class="table-badge">{{ strtoupper($application->payment_status) }}</span></td>
                                        <td><span class="table-badge">{{ str_replace('_', ' ', strtoupper($application->status)) }}</span></td>
                                        <td>
                                            <strong>{{ $application->preferred_subdomain ?: 'No subdomain requested' }}</strong>
                                            <p>{{ $application->preferred_domain ?: 'No custom domain requested' }}</p>
                                        </td>
                                        <td>{{ $application->created_at?->format('M d, Y h:i A') }}</td>
                                        <td>
                                            <div class="link-row">
                                                <a class="action-icon-button action-icon-button-secondary" href="{{ $dashboardBaseUrl.'?section=applications&review='.$application->id }}" title="Review application" aria-label="Review application">
                                                    <i class="fa-solid fa-eye"></i>
                                                    <span class="sr-only">Review application</span>
                                                </a>
                                                @if ($application->tenant)
                                                    <a class="action-icon-button" href="{{ app(\App\Support\Tenancy\TenantUrlGenerator::class)->loginUrl($application->tenant) }}" target="_blank" rel="noopener noreferrer" title="Open portal" aria-label="Open portal">
                                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                                        <span class="sr-only">Open portal</span>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($applications->hasPages())
                        <div class="pagination">
                            {{ $applications->links() }}
                        </div>
                    @endif
                @endif
            </article>
        </section>
    @endif

    @if ($currentSection === 'directory')
        <section class="dashboard-grid">
            @if ($editingTenant)
                @php
                    $editingContact = $tenantContacts[$editingTenant->id] ?? ['name' => null, 'email' => null];
                    $editingDomainHosts = $editingTenant->domains->where('is_active', true)->pluck('host')->implode(PHP_EOL);
                    $editingBandwidth = $tenantBandwidthProfiles->get($editingTenant->id);
                @endphp

                <div class="modal-shell" aria-hidden="false">
                <article class="section-card modal-card modal-card-wide">
                    <div class="section-header">
                        <div>
                            <h2>Edit {{ $editingTenant->name }}</h2>
                            <p>Update subscription, access, and tenant metadata without leaving the dashboard.</p>
                        </div>
                        <a class="modal-close-button" href="{{ $dashboardBaseUrl.'?section=directory' }}" aria-label="Close tenant edit modal">&times;</a>
                    </div>

                    <form method="POST" action="{{ route('central.tenants.update', $editingTenant) }}">
                        @csrf
                        @method('PATCH')
                        <div class="form-grid">
                            <label>College Name <input type="text" name="name" value="{{ old('name', $editingTenant->name) }}" required></label>
                            <label>
                                License Tier
                                <select name="plan" required>
                                    @foreach ($plans as $planKey => $plan)
                                        <option value="{{ $planKey }}" @selected(old('plan', $editingTenant->plan) === $planKey)>{{ $plan['label'] }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>
                                Portal Access
                                <select name="is_active" required>
                                    <option value="1" @selected((string) old('is_active', (int) $editingTenant->is_active) === '1')>Active</option>
                                    <option value="0" @selected((string) old('is_active', (int) $editingTenant->is_active) === '0')>Suspended</option>
                                </select>
                            </label>
                            <label>Subscription Starts <input type="date" name="subscription_starts_at" value="{{ old('subscription_starts_at', $editingTenant->subscription_starts_at?->toDateString()) }}" required></label>
                            <label>Subscription Expires <input type="date" name="subscription_expires_at" value="{{ old('subscription_expires_at', $editingTenant->subscription_expires_at?->toDateString()) }}"></label>
                            <label>Bandwidth Allocation (GB) <input type="number" min="1" step="1" name="bandwidth_limit_gb" value="{{ old('bandwidth_limit_gb', $editingBandwidth['limit_gb'] ?? $planBandwidthDefaults[$editingTenant->plan] ?? 150) }}" required></label>
                            <label>Current Usage (GB) <input type="number" min="0" step="0.1" name="bandwidth_used_gb" value="{{ old('bandwidth_used_gb', $editingBandwidth['used_gb'] ?? 0) }}"></label>
                            <label>Coordinator Name <input type="text" name="admin_name" value="{{ old('admin_name', $editingContact['name']) }}" placeholder="Internship Coordinator"></label>
                            <label>Coordinator Email <input type="email" name="admin_email" value="{{ old('admin_email', $editingContact['email']) }}" required></label>
                            <label>Tenant Code <input type="text" value="{{ $editingTenant->code }}" readonly></label>
                            <label>Tenant Database <input type="text" value="{{ $editingTenant->database }}" readonly></label>
                            <label class="field-span-2">
                                Approved Domain Hosts
                                <textarea name="domain_hosts" rows="4" placeholder="one-host-per-line.example.edu">{{ old('domain_hosts', $editingDomainHosts) }}</textarea>
                            </label>
                        </div>

                        <div class="hero-actions">
                            <button type="submit">Save Changes</button>
                        </div>
                    </form>

                    <div class="hero-actions">
                        <form method="POST" action="{{ route('central.tenants.status', $editingTenant) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="is_active" value="{{ $editingTenant->is_active ? '0' : '1' }}">
                            <button type="submit" class="secondary">{{ $editingTenant->is_active ? 'Deactivate Tenant' : 'Activate Tenant' }}</button>
                        </form>
                        <form method="POST" action="{{ route('central.tenants.notify', $editingTenant) }}">
                            @csrf
                            <input type="hidden" name="notification" value="subscription">
                            <button type="submit" class="secondary">Send Subscription Email</button>
                        </form>
                        <form method="POST" action="{{ route('central.tenants.notify', $editingTenant) }}">
                            @csrf
                            <input type="hidden" name="notification" value="credentials">
                            <button type="submit" class="secondary">Reissue Credentials</button>
                        </form>
                        <form method="POST" action="{{ route('central.tenants.notify', $editingTenant) }}">
                            @csrf
                            <input type="hidden" name="notification" value="{{ $editingTenant->is_active ? 'activation' : 'suspension' }}">
                            <button type="submit" class="secondary">Send Status Email</button>
                        </form>
                    </div>
                </article>
                </div>
            @endif

            <article class="section-card">
                <div class="table-header">
                    <div>
                        <h2>Tenant Directory</h2>
                        <p>{{ $tenants->count() }} provisioned tenants.</p>
                    </div>
                </div>

                @if ($tenants->isEmpty())
                    <p>No tenants provisioned yet.</p>
                @else
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tenant</th>
                                    <th>Plan</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tenants as $tenant)
                                    @php
                                        $tenantPortalUrl = app(\App\Support\Tenancy\TenantUrlGenerator::class)->loginUrl($tenant);
                                        $tenantPortalBaseUrl = preg_replace('#/login$#', '/', $tenantPortalUrl);
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $tenant->name }}</strong>
                                            <p>{{ $tenantPortalBaseUrl }}</p>
                                        </td>
                                        <td><span class="table-badge">{{ strtoupper($tenant->plan) }}</span></td>
                                        <td><span class="table-badge">{{ ucfirst($tenant->subscriptionStatus()) }}</span></td>
                                        <td>
                                            <div class="link-row">
                                                <a class="action-icon-button" href="{{ $tenantPortalUrl }}" target="_blank" rel="noopener noreferrer" title="Open portal" aria-label="Open portal">
                                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                                    <span class="sr-only">Open portal</span>
                                                </a>
                                                <a class="action-icon-button action-icon-button-secondary" href="{{ $dashboardBaseUrl.'?section=directory&edit='.$tenant->id }}" title="Edit tenant" aria-label="Edit tenant">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                    <span class="sr-only">Edit tenant</span>
                                                </a>
                                                <form method="POST" action="{{ route('central.tenants.status', $tenant) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="is_active" value="{{ $tenant->is_active ? '0' : '1' }}">
                                                    <button type="submit" class="action-icon-button" title="{{ $tenant->is_active ? 'Deactivate tenant' : 'Activate tenant' }}" aria-label="{{ $tenant->is_active ? 'Deactivate tenant' : 'Activate tenant' }}">
                                                        <i class="fa-solid {{ $tenant->is_active ? 'fa-power-off' : 'fa-bolt' }}"></i>
                                                        <span class="sr-only">{{ $tenant->is_active ? 'Deactivate' : 'Activate' }}</span>
                                                    </button>
                                                </form>
                                                <form
                                                    method="POST"
                                                    action="{{ route('central.tenants.destroy', $tenant) }}"
                                                    data-confirm
                                                    data-confirm-title="Delete tenant?"
                                                    data-confirm-message="Delete {{ $tenant->name }} and permanently drop database {{ $tenant->database }}? This cannot be undone."
                                                    data-confirm-submit-label="Delete tenant"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="action-icon-button action-icon-button-danger" title="Delete tenant" aria-label="Delete tenant">
                                                        <i class="fa-solid fa-trash"></i>
                                                        <span class="sr-only">Delete</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($tenants->hasPages())
                        <div class="pagination">
                            {{ $tenants->links() }}
                        </div>
                    @endif
                @endif
            </article>
        </section>
    @endif

    <div class="modal-shell" data-audit-modal hidden aria-hidden="true">
        <article class="section-card modal-card modal-card-wide audit-modal-card" role="dialog" aria-modal="true" aria-labelledby="audit-modal-title">
            <div class="section-header">
                <div>
                    <span class="mini-kicker">Security</span>
                    <h2 id="audit-modal-title">Audit Logs</h2>
                    <p>{{ $auditLogs->count() }} recent entries available for review and PDF export.</p>
                </div>
                <button type="button" class="modal-close-button" data-audit-close aria-label="Close audit logs">&times;</button>
            </div>

            <div class="audit-modal-body">
                <div class="audit-modal-actions">
                    <button type="button" data-audit-print>
                        <i class="fa-solid fa-file-pdf"></i>
                        Download PDF
                    </button>
                    <button type="button" class="secondary" data-audit-close>Close</button>
                </div>

                @if ($auditLogs->isEmpty())
                    <div class="empty-state">
                        <strong>No audit logs yet.</strong>
                        <p>New audit events will appear here after actions are recorded by the system.</p>
                    </div>
                @else
                    <div class="table-wrap audit-table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Tenant</th>
                                    <th>Actor</th>
                                    <th>Action</th>
                                    <th>Record</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($auditLogs as $log)
                                    <tr>
                                        <td>{{ $log['occurred_at'] }}</td>
                                        <td>{{ $log['tenant'] }}</td>
                                        <td>
                                            <strong>{{ $log['actor'] }}</strong>
                                            <p>{{ $log['actor_type'] }}</p>
                                        </td>
                                        <td><span class="table-badge">{{ $log['action'] }}</span></td>
                                        <td>
                                            <strong>{{ $log['subject'] }}</strong>
                                            <p>{{ $log['url'] }}</p>
                                        </td>
                                        <td>{{ $log['ip_address'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </article>
    </div>

    <div class="audit-print-report" aria-hidden="true">
        @foreach ($auditLogPages as $pageIndex => $pageLogs)
            <section class="audit-print-page">
                <header class="audit-print-header">
                    <img src="{{ asset('images/logos/logo.jpg') }}" alt="Bukidnon State University Logo">
                    <div>
                        <strong>Bukidnon State University</strong>
                        <h1>Audit Logs Report</h1>
                        <p>Prepared by {{ $centralActor?->name ?: 'Central Superadmin' }} · Generated {{ now()->format('M d, Y h:i A') }}</p>
                    </div>
                </header>

                @if ($pageLogs->isEmpty())
                    <p class="audit-print-empty">No audit log entries are available.</p>
                @else
                    <table class="audit-print-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Tenant</th>
                                <th>Actor</th>
                                <th>Action</th>
                                <th>Record</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pageLogs as $log)
                                <tr>
                                    <td>{{ $log['occurred_at'] }}</td>
                                    <td>{{ $log['tenant'] }}</td>
                                    <td>{{ $log['actor'] }}</td>
                                    <td>{{ $log['action'] }}</td>
                                    <td>{{ $log['subject'] }}</td>
                                    <td>{{ $log['ip_address'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                <footer class="audit-print-footer">
                    <span>{{ config('app.name', 'University Practicum') }}</span>
                    <span>Page {{ $pageIndex + 1 }} of {{ $auditLogPages->count() }}</span>
                </footer>
            </section>
        @endforeach
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.querySelector('[data-audit-modal]');
            const openButton = document.querySelector('[data-audit-open]');
            const closeButtons = document.querySelectorAll('[data-audit-close]');
            const printButton = document.querySelector('[data-audit-print]');

            function openAuditModal() {
                if (! modal) {
                    return;
                }

                modal.removeAttribute('hidden');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('modal-open');
            }

            function closeAuditModal() {
                if (! modal) {
                    return;
                }

                modal.setAttribute('hidden', 'hidden');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
            }

            openButton?.addEventListener('click', openAuditModal);
            closeButtons.forEach(function (button) {
                button.addEventListener('click', closeAuditModal);
            });
            modal?.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeAuditModal();
                }
            });
            printButton?.addEventListener('click', function () {
                window.print();
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && modal && !modal.hasAttribute('hidden')) {
                    closeAuditModal();
                }
            });
        });
    </script>
@endsection
