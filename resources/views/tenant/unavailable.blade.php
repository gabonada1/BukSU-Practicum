@php
    $appUrl = config('app.url', request()->getSchemeAndHttpHost());
    $appParts = parse_url($appUrl);
    $centralDomains = config('tenancy.central_domains', []);
    $centralHost = collect($centralDomains)->first(fn ($domain) => $domain === 'localhost')
        ?? ($centralDomains[0] ?? ($appParts['host'] ?? request()->getHost()));
    $centralScheme = $appParts['scheme'] ?? request()->getScheme();
    $centralPort = isset($appParts['port']) ? ':'.$appParts['port'] : '';
    $centralPath = rtrim($appParts['path'] ?? '', '/');
    $renewUrl = $centralScheme.'://'.$centralHost.$centralPort.$centralPath.'/central/login';

    $plans = \App\Support\Billing\PlanCatalog::all();

    $currentPlan = strtolower($tenant->plan ?? 'basic');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>University Portal Unavailable</title>
        @include('layouts.partials.app-theme')
        <style>
            :root {
                --page: var(--app-page);
                --page-alt: var(--app-page-alt);
                --shell: var(--app-panel);
                --panel: var(--app-surface);
                --panel-soft: var(--app-panel-soft);
                --card-ink: var(--app-text);
                --card-muted: var(--app-text-muted);
                --accent: var(--app-primary);
                --accent-strong: var(--app-primary-strong);
                --warm: var(--app-warning);
                --danger: var(--app-danger);
                --success: var(--app-success);
                --shadow: var(--app-shadow);
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                padding: 32px 24px;
                font-family: "Segoe UI", "Trebuchet MS", sans-serif;
                background:
                    radial-gradient(circle at top left, var(--app-primary-glow), transparent 30%),
                    radial-gradient(circle at bottom right, rgba(115, 199, 182, 0.12), transparent 28%),
                    linear-gradient(180deg, var(--page), var(--page-alt));
                color: var(--card-ink);
            }

            .wrap {
                width: min(1180px, 100%);
                margin: 0 auto;
                display: grid;
                gap: 22px;
            }

            .panel {
                padding: 34px;
                border-radius: var(--app-radius-xl);
                background:
                    radial-gradient(circle at top right, var(--app-primary-glow), transparent 36%),
                    linear-gradient(180deg, var(--app-surface), var(--app-page-alt));
                border: 1px solid var(--app-border);
                box-shadow: var(--shadow);
            }

            .eyebrow {
                display: inline-flex;
                align-items: center;
                padding: 7px 12px;
                border-radius: 999px;
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 0.14em;
                text-transform: uppercase;
                color: var(--app-text);
                background: var(--app-primary-soft);
                border: 1px solid var(--app-border-strong);
            }

            h1,
            h2,
            h3,
            p {
                margin-top: 0;
            }

            h1 {
                margin: 16px 0 10px;
                font-size: 40px;
                letter-spacing: -0.04em;
                color: var(--card-ink);
            }

            p {
                margin: 0;
                line-height: 1.7;
                color: var(--card-muted);
            }

            .top {
                display: grid;
                grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.8fr);
                gap: 18px;
                align-items: stretch;
            }

            .meta {
                display: grid;
                gap: 12px;
            }

            .meta div {
                padding: 14px 16px;
                border-radius: var(--app-radius-md);
                background: var(--panel-soft);
                border: 1px solid var(--app-border);
                color: var(--card-muted);
            }

            strong {
                display: block;
                margin-bottom: 4px;
                color: var(--card-ink);
            }

            .status {
                color: var(--app-text);
                background: rgba(217, 107, 122, 0.12);
                border: 1px solid rgba(217, 107, 122, 0.28);
            }

            .cta-card {
                display: grid;
                gap: 16px;
                align-content: start;
            }

            .button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                min-height: 48px;
                padding: 12px 18px;
                border-radius: var(--app-radius-sm);
                border: 1px solid transparent;
                background: linear-gradient(135deg, var(--accent), var(--accent-strong));
                color: #fff;
                font: inherit;
                font-weight: 700;
                letter-spacing: 0.02em;
                text-decoration: none;
                box-shadow: 0 12px 24px var(--app-primary-glow);
            }

            .button.secondary {
                background: var(--app-panel-soft);
                border-color: var(--app-border);
                color: var(--card-ink);
                box-shadow: none;
            }

            .helper {
                padding: 16px 18px;
                border-radius: var(--app-radius-md);
                background: var(--app-panel-soft);
                border: 1px solid var(--app-border);
            }

            .license-wordmark {
                width: 58px;
                height: 58px;
                border-radius: var(--app-radius-md);
                display: grid;
                place-items: center;
                margin-bottom: 16px;
                background: linear-gradient(145deg, rgba(217, 107, 122, 0.18), var(--app-primary-soft));
                color: var(--app-danger);
                font-size: 24px;
            }

            .plans-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 18px;
            }

            .plan-card {
                padding: 24px 22px;
                border-radius: var(--app-radius-lg);
                background:
                    radial-gradient(circle at top right, var(--app-primary-glow), transparent 34%),
                    linear-gradient(180deg, var(--app-surface), var(--app-page-alt));
                border: 1px solid var(--app-border);
                box-shadow: var(--shadow);
                display: grid;
                gap: 16px;
            }

            .plan-card.active {
                border-color: var(--app-border-strong);
                box-shadow: 0 22px 44px var(--app-primary-glow);
            }

            .plan-top {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 12px;
            }

            .plan-top h2 {
                margin: 0;
                font-size: 24px;
                letter-spacing: -0.03em;
                color: var(--card-ink);
            }

            .plan-badge {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 7px 12px;
                border-radius: 999px;
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 0.14em;
                text-transform: uppercase;
                background: var(--app-primary-soft);
                border: 1px solid var(--app-border-strong);
                color: var(--app-text);
            }

            .plan-card.active .plan-badge {
                background: rgba(217, 107, 122, 0.14);
                border-color: rgba(217, 107, 122, 0.28);
                color: var(--app-text);
            }

            .plan-summary {
                color: var(--card-muted);
                min-height: 50px;
            }

            .plan-fit {
                padding: 12px 14px;
                border-radius: var(--app-radius-sm);
                background: var(--panel-soft);
                color: var(--card-muted);
                line-height: 1.5;
            }

            .plan-fit strong {
                margin-bottom: 2px;
            }

            .plan-card ul {
                margin: 0;
                padding: 0;
                list-style: none;
                display: grid;
                gap: 10px;
            }

            .plan-card li {
                position: relative;
                padding-left: 18px;
                color: var(--card-muted);
                line-height: 1.6;
            }

            .plan-card li::before {
                content: "";
                position: absolute;
                left: 0;
                top: 10px;
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: var(--warm);
            }

            .plan-card.active li::before {
                background: var(--accent);
            }

            @media (max-width: 980px) {
                .top,
                .plans-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    </head>
    <body>
        <main class="wrap">
            <section class="top">
                <article class="panel">
                    <div class="license-wordmark" aria-hidden="true">!</div>
                    <span class="eyebrow">University Portal Access Paused</span>
                    <h1>{{ $tenant->name }}</h1>
                    <p>{{ $message }}</p>

                    <div class="meta" >
                        <div class="status">
                            <strong>Status</strong>
                            <span>{{ ucfirst($tenant->subscriptionStatus()) }}</span>
                        </div>
                        <div>
                            <strong>License Starts</strong>
                            <span>{{ $tenant->subscription_starts_at?->format('M d, Y') ?: 'Not set' }}</span>
                        </div>
                        <div>
                            <strong>License Expires</strong>
                            <span>{{ $tenant->subscription_expires_at?->format('M d, Y') ?: 'Open-ended' }}</span>
                        </div>
                    </div>
                </article>

                <aside class="panel cta-card">
                    <div>
                        <span class="eyebrow">College License</span>
                        <h2>Restore Access</h2>
                        <p>Open University Administration to renew this college license tier and reactivate the portal.</p>
                    </div>

                    <a class="button" href="{{ $renewUrl }}">Open University Administration</a>

                    <div class="helper">
                        <strong>Current License</strong>
                        <span>{{ strtoupper($tenant->plan) }}</span>
                    </div>
                </aside>
            </section>

            <section class="plans-grid">
                @foreach ($plans as $key => $plan)
                    <article class="plan-card{{ $currentPlan === $key ? ' active' : '' }}">
                        <div class="plan-top">
                            <div>
                                <h2>{{ $plan['label'] }}</h2>
                                <p class="plan-summary">{{ $plan['summary'] }}</p>
                            </div>
                            <span class="plan-badge">{{ $currentPlan === $key ? 'Current' : 'Plan' }}</span>
                        </div>

                        <div class="plan-fit">
                            <strong>{{ $plan['student_limit'] ?? 'Flexible capacity' }}</strong>
                            <span>{{ $plan['best_for'] ?? 'Designed for practicum teams.' }}</span>
                        </div>

                        <ul>
                            @foreach ($plan['features'] as $feature)
                                <li>{{ $feature }}</li>
                            @endforeach
                        </ul>
                    </article>
                @endforeach
            </section>
        </main>
    </body>
</html>
