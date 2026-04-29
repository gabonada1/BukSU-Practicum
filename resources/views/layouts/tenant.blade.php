@php
    $layoutMode = $layoutMode ?? 'dashboard';
    $hideTenantHeader = $hideTenantHeader ?? ($layoutMode === 'login');
    $tenantBranding = is_array($tenant->settings['branding'] ?? null) ? $tenant->settings['branding'] : [];
    $tenantPortalTitle = filled($tenantBranding['portal_title'] ?? null)
        ? $tenantBranding['portal_title']
        : config('app.name', 'University Practicum');
    $tenantCurrentVersion = data_get($tenant->settings, 'release_preferences.preferred_release_version', config('app.version', '1.0.0'));
    $tenantLogoPath = filled($tenantBranding['logo_path'] ?? null) ? ltrim((string) $tenantBranding['logo_path'], '/\\') : null;
    $tenantLogoVersion = $tenantLogoPath && file_exists(public_path($tenantLogoPath))
        ? '?v='.filemtime(public_path($tenantLogoPath))
        : '';
    $systemLogo = $tenantLogoPath
        ? asset($tenantLogoPath).$tenantLogoVersion
        : asset('images/logos/logo.jpeg');
    $tenantRole = $tenantRole ?? match (true) {
        request()->routeIs('tenant*.admin.*') => 'admin',
        request()->routeIs('tenant*.supervisor.*') => 'supervisor',
        request()->routeIs('tenant*.student.*') => 'student',
        auth('tenant_admin')->check() => 'admin',
        auth('supervisor')->check() => 'supervisor',
        auth('student')->check() => 'student',
        default => 'admin',
    };
    $tenantActor = match ($tenantRole) {
        'supervisor' => auth('supervisor')->user(),
        'student' => auth('student')->user(),
        default => auth('tenant_admin')->user(),
    };
    $tenantActorName = $tenantRole === 'student'
        ? optional($tenantActor)->full_name
        : optional($tenantActor)->name;
    $tenantRoleLabel = match ($tenantRole) {
        'supervisor' => 'Company Supervisor',
        'student' => 'Student Portal',
        default => 'Coordinator Console',
    };
    $tenantAccessLabel = preg_replace('#^https?://#', '', app(\App\Support\Tenancy\TenantUrlGenerator::class)->loginUrl($tenant));
    $tenantDashboardUrl = match ($tenantRole) {
        'supervisor' => route('tenant.supervisor.dashboard'),
        'student' => route('tenant.student.dashboard'),
        default => route('tenant.admin.dashboard'),
    };
    $tenantProfileUrl = match ($tenantRole) {
        'supervisor' => route('tenant.supervisor.profile.show'),
        'student' => route('tenant.student.profile.show'),
        default => route('tenant.admin.profile.show'),
    };
    $tenantLogoutAction = match ($tenantRole) {
        'supervisor' => route('tenant.supervisor.logout'),
        'student' => route('tenant.student.logout'),
        default => route('tenant.admin.logout'),
    };
    $tenantNavigation = match ($tenantRole) {
        'supervisor' => [
            ['label' => 'Students', 'href' => $tenantDashboardUrl.'?section=students', 'key' => 'students', 'meta' => 'Assigned interns', 'icon' => 'fa-graduation-cap'],
            ['label' => 'Hour Logs', 'href' => $tenantDashboardUrl.'?section=logs', 'key' => 'logs', 'meta' => 'Validation queue', 'icon' => 'fa-clock'],
            ['label' => 'Profile', 'href' => $tenantProfileUrl, 'active' => request()->routeIs('tenant*.supervisor.profile.*'), 'meta' => 'Account details', 'icon' => 'fa-user-circle'],
        ],
        'student' => [
            ['label' => 'Applications', 'href' => $tenantDashboardUrl.'?section=applications', 'key' => 'applications', 'meta' => 'Placement requests', 'icon' => 'fa-envelope'],
            ['label' => 'Requirements', 'href' => $tenantDashboardUrl.'?section=requirements', 'key' => 'requirements', 'meta' => 'Uploads and forms', 'icon' => 'fa-file-clipboard'],
            ['label' => 'Hour Logs', 'href' => $tenantDashboardUrl.'?section=logs', 'key' => 'logs', 'meta' => 'Progress records', 'icon' => 'fa-list-check'],
            ['label' => 'Profile', 'href' => $tenantProfileUrl, 'active' => request()->routeIs('tenant*.student.profile.*'), 'meta' => 'Student profile', 'icon' => 'fa-user-circle'],
        ],
        default => [
            ['label' => 'Companies', 'href' => $tenantDashboardUrl.'?section=companies', 'key' => 'companies', 'meta' => 'Partner records', 'icon' => 'fa-building'],
            ['label' => 'Supervisors', 'href' => $tenantDashboardUrl.'?section=supervisors', 'key' => 'supervisors', 'meta' => 'Industry contacts', 'icon' => 'fa-user-tie'],
            ['label' => 'Students', 'href' => $tenantDashboardUrl.'?section=students', 'key' => 'students', 'meta' => 'Intern roster', 'icon' => 'fa-graduation-cap'],
            ['label' => 'Users', 'href' => $tenantDashboardUrl.'?section=users', 'key' => 'users', 'meta' => 'User management', 'icon' => 'fa-users'],
            ['label' => 'RBAC', 'href' => route('tenant.admin.rbac.index'), 'active' => request()->routeIs('tenant*.admin.rbac.*'), 'meta' => 'Role permissions', 'icon' => 'fa-shield-halved'],
            ['label' => 'Updates', 'href' => route('tenant.admin.updates.index'), 'active' => request()->routeIs('tenant*.admin.updates.*'), 'meta' => 'Version rollout', 'icon' => 'fa-code-branch'],
            ['label' => 'Support', 'href' => route('tenant.admin.support.index'), 'active' => request()->routeIs('tenant*.admin.support.*'), 'meta' => 'Central help desk', 'icon' => 'fa-headset'],
            ['label' => 'Requirements', 'href' => $tenantDashboardUrl.'?section=requirements', 'key' => 'requirements', 'meta' => 'Document desk', 'icon' => 'fa-file-clipboard'],
            ['label' => 'Hours', 'href' => $tenantDashboardUrl.'?section=hours', 'key' => 'hours', 'meta' => 'Duty records', 'icon' => 'fa-clock'],
            ['label' => 'Audit Logs', 'href' => $tenantDashboardUrl.'?section=audit', 'key' => 'audit', 'meta' => 'Tenant activity', 'icon' => 'fa-clipboard-list'],
            ['label' => 'Profile', 'href' => $tenantProfileUrl, 'active' => request()->routeIs('tenant*.admin.profile.*'), 'meta' => 'Portal settings', 'icon' => 'fa-user-circle'],
        ],
    };
    $tenantCurrentSection = match ($tenantRole) {
        'student' => request()->query('section', 'applications'),
        'supervisor' => request()->query('section', 'students'),
        default => request()->query('section', 'companies'),
    };
    $hasExplicitTenantNav = collect($tenantNavigation)->contains(fn ($item) => (bool) ($item['active'] ?? false));
    $activeTenantNav = collect($tenantNavigation)->first(function ($item) use ($tenantCurrentSection, $hasExplicitTenantNav) {
        if ($item['active'] ?? false) {
            return true;
        }

        return ! $hasExplicitTenantNav && (($item['key'] ?? null) === $tenantCurrentSection);
    }) ?? $tenantNavigation[0];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $pageTitle ?? ($tenant->name.' | '.$tenantPortalTitle) }}</title>
        @include('layouts.partials.app-theme')
    </head>
    <body class="app-shell-body theme-{{ $layoutMode }}">
        @include('layouts.partials.app-feedback')

        @if ($hideTenantHeader)
            <main class="auth-shell">
                @yield('content')
            </main>
        @else
            <main class="app-shell" data-app-shell>
                <div class="app-overlay" data-app-overlay></div>

                <div class="app-frame">
                    <aside class="app-sidebar">
                        <div class="app-sidebar-header">
                            <div class="app-brand-mark">
                                @if ($tenantLogoPath)
                                    <img src="{{ $systemLogo }}" alt="{{ $tenantPortalTitle }} Logo">
                                @else
                                    <span>{{ strtoupper(substr((string) ($tenant->code ?: $tenant->name), 0, 1)) }}</span>
                                @endif
                            </div>
                            <div class="app-brand-copy">
                                <span class="app-brand-kicker">Tenant Portal</span>
                                <strong class="app-brand-title">{{ $tenant->name }}</strong>
                                <p class="app-brand-subtitle">{{ $tenantRoleLabel }}</p>
                            </div>
                        </div>

                        <div class="app-nav-group">
                            <span class="app-nav-label">Main</span>
                            <nav class="app-nav" aria-label="Tenant navigation">
                                @foreach ($tenantNavigation as $item)
                                    @php
                                        $isActiveTenantLink = ($item['active'] ?? false)
                                            || (! $hasExplicitTenantNav && (($item['key'] ?? null) === $tenantCurrentSection));
                                    @endphp
                                    <a class="app-nav-link{{ $isActiveTenantLink ? ' active' : '' }}" href="{{ $item['href'] }}" title="{{ $item['meta'] }}">
                                        <span class="app-nav-icon"><i class="fa-solid {{ $item['icon'] }}"></i></span>
                                        <span class="app-nav-copy">
                                            <span>{{ $item['label'] }}</span>
                                            <small>{{ $item['meta'] }}</small>
                                        </span>
                                    </a>
                                @endforeach
                            </nav>
                        </div>

                        <div class="app-sidebar-footer">
                            <div class="app-meta-card app-version-card">
                                <strong>Current Version</strong>
                                <span>{{ $tenantCurrentVersion }}</span>
                            </div>
                            <span class="app-nav-label">Account</span>
                            <form method="POST" action="{{ $tenantLogoutAction }}">
                                @csrf
                                <button type="submit" class="app-nav-link app-nav-link-button">
                                    <span class="app-nav-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></span>
                                    <span class="app-nav-copy">
                                        <span>Sign Out</span>
                                        <small>{{ $tenantPortalTitle }}</small>
                                    </span>
                                </button>
                            </form>
                        </div>
                    </aside>

                    <div class="app-main">
                        <header class="app-topbar">
                            <div class="app-topbar-main">
                                <button type="button" class="app-icon-button desktop-only" data-sidebar-toggle aria-label="Toggle sidebar">
                                    <span class="app-icon-lines" aria-hidden="true">
                                        <span></span>
                                        <span></span>
                                    </span>
                                </button>
                                <button type="button" class="app-icon-button mobile-only" data-mobile-sidebar-toggle aria-label="Open sidebar">
                                    <span class="app-icon-lines app-icon-lines-menu" aria-hidden="true">
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                    </span>
                                </button>
                                <div class="app-topbar-copy">
                                    <strong>{{ $activeTenantNav['label'] ?? $tenantRoleLabel }}</strong>
                                    <span>{{ $activeTenantNav['meta'] ?? $tenant->name }}</span>
                                </div>
                            </div>

                            <div class="app-topbar-actions">
                                <a class="panel-link" href="{{ $tenantProfileUrl }}">Open Profile</a>
                                <div class="app-user-chip">
                                    <span class="app-user-avatar">{{ strtoupper(substr((string) ($tenantActorName ?: $tenantRoleLabel), 0, 1)) }}</span>
                                    <div>
                                        <strong>{{ $tenantActorName ?: $tenantRoleLabel }}</strong>
                                        <span>{{ $tenantRoleLabel }}</span>
                                    </div>
                                </div>
                            </div>
                        </header>

                        <section class="app-content">
                            @yield('content')
                        </section>
                    </div>
                </div>
            </main>
        @endif

    </body>
</html>
