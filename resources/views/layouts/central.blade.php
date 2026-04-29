@php
    $layoutMode = $layoutMode ?? 'dashboard';
    $hideCentralHeader = $hideCentralHeader ?? ($layoutMode === 'login');
    $centralActor = auth('central_superadmin')->user();
    $systemLogo = asset('images/logos/logo.jpeg');
    $centralCurrentSection = request()->query('section', 'overview');
    $centralNavigation = [
        ['label' => 'Overview', 'href' => route('central.dashboard').'?section=overview', 'active' => request()->routeIs('central.dashboard') && $centralCurrentSection === 'overview', 'meta' => 'System pulse', 'icon' => 'fa-chart-line'],
        ['label' => 'Applications', 'href' => route('central.dashboard').'?section=applications', 'active' => request()->routeIs('central.dashboard') && $centralCurrentSection === 'applications', 'meta' => 'Plan reviews', 'icon' => 'fa-inbox'],
        ['label' => 'Directory', 'href' => route('central.dashboard').'?section=directory', 'active' => request()->routeIs('central.dashboard') && $centralCurrentSection === 'directory', 'meta' => 'Tenant records', 'icon' => 'fa-building'],
        ['label' => 'Updates', 'href' => route('central.updates.index'), 'active' => request()->routeIs('central.updates.*'), 'meta' => 'GitHub releases', 'icon' => 'fa-code-branch'],
        ['label' => 'Support', 'href' => route('central.support.index'), 'active' => request()->routeIs('central.support.*'), 'meta' => 'Tenant tickets', 'icon' => 'fa-headset'],
    ];
    $activeCentralNav = collect($centralNavigation)->first(fn ($item) => $item['active'] ?? false) ?? $centralNavigation[0];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $pageTitle ?? config('app.name', 'University Practicum') }}</title>
        @include('layouts.partials.app-theme')
    </head>
    <body class="app-shell-body theme-{{ $layoutMode }}">
        @include('layouts.partials.app-feedback')

        @if ($hideCentralHeader)
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
                                <img src="{{ $systemLogo }}" alt="University Practicum Logo">
                            </div>
                            <div class="app-brand-copy">
                                <span class="app-brand-kicker">Central Console</span>
                                <strong class="app-brand-title">University Practicum</strong>
                                <p class="app-brand-subtitle">University-level practicum operations</p>
                            </div>
                        </div>

                        <div class="app-nav-group">
                            <span class="app-nav-label">Main</span>
                            <nav class="app-nav" aria-label="Central navigation">
                                @foreach ($centralNavigation as $item)
                                    <a class="app-nav-link{{ ($item['active'] ?? false) ? ' active' : '' }}" href="{{ $item['href'] }}" title="{{ $item['meta'] }}">
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
                            <span class="app-nav-label">Account</span>
                            <form method="POST" action="{{ route('central.logout') }}">
                                @csrf
                                <button type="submit" class="app-nav-link app-nav-link-button">
                                    <span class="app-nav-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></span>
                                    <span class="app-nav-copy">
                                        <span>Sign Out</span>
                                        <small>{{ $centralActor?->name ?: 'System Administrator' }}</small>
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
                                    <strong>{{ $activeCentralNav['label'] }}</strong>
                                    <span>{{ $activeCentralNav['meta'] }}</span>
                                </div>
                            </div>

                            <div class="app-topbar-actions">
                                <a class="panel-link" href="{{ route('app.entry') }}">Open Landing Page</a>
                                <div class="app-user-chip">
                                    <span class="app-user-avatar">{{ strtoupper(substr((string) ($centralActor?->name ?: 'SA'), 0, 1)) }}</span>
                                    <div>
                                        <strong>{{ $centralActor?->name ?: 'System Administrator' }}</strong>
                                        <span>Central superadmin</span>
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
