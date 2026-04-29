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
        <style>
            :root {
                --page: #f5f7fb;
                --page-alt: #e9eef8;
                --shell: rgba(255, 255, 255, 0.92);
                --panel: #ffffff;
                --panel-soft: #eef3fb;
                --card-ink: #14213d;
                --card-muted: #65728a;
                --accent: #7B1C2E;
                --accent-strong: #5E1423;
                --warm: #e8a328;
                --danger: #d07070;
                --success: #6db88a;
                --shadow: 0 24px 60px rgba(31, 46, 84, 0.14);
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
                    radial-gradient(circle at top left, rgba(123, 28, 46, 0.1), transparent 28%),
                    radial-gradient(circle at bottom right, rgba(44, 91, 180, 0.12), transparent 26%),
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
                border-radius: 28px;
                background: linear-gradient(180deg, var(--shell), var(--panel));
                border: 1px solid rgba(103, 116, 150, 0.18);
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
                color: var(--accent);
                background: rgba(123, 28, 46, 0.08);
                border: 1px solid rgba(123, 28, 46, 0.14);
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
                border-radius: 18px;
                background: var(--panel-soft);
                border: 1px solid rgba(103, 116, 150, 0.14);
                color: var(--card-muted);
            }

            strong {
                display: block;
                margin-bottom: 4px;
                color: var(--card-ink);
            }

            .status {
                color: #7b1c2e;
                background: rgba(123, 28, 46, 0.08);
                border: 1px solid rgba(123, 28, 46, 0.16);
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
                border-radius: 16px;
                border: 1px solid transparent;
                background: linear-gradient(135deg, var(--accent), var(--accent-strong));
                color: #fff;
                font: inherit;
                font-weight: 700;
                letter-spacing: 0.02em;
                text-decoration: none;
                box-shadow: 0 12px 24px rgba(94, 20, 35, 0.24);
            }

            .button.secondary {
                background: #f7f9fd;
                border-color: rgba(103, 116, 150, 0.16);
                color: var(--card-ink);
                box-shadow: none;
            }

            .helper {
                padding: 16px 18px;
                border-radius: 18px;
                background: rgba(44, 91, 180, 0.08);
                border: 1px solid rgba(44, 91, 180, 0.12);
            }

            .license-wordmark {
                width: 58px;
                height: 58px;
                border-radius: 18px;
                display: grid;
                place-items: center;
                margin-bottom: 16px;
                background: linear-gradient(145deg, rgba(123, 28, 46, 0.12), rgba(44, 91, 180, 0.12));
                color: var(--accent);
                font-size: 24px;
            }

            .plans-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 18px;
            }

            .plan-card {
                padding: 24px 22px;
                border-radius: 24px;
                background: linear-gradient(180deg, #ffffff, #f8faff);
                border: 1px solid rgba(103, 116, 150, 0.16);
                box-shadow: var(--shadow);
                display: grid;
                gap: 16px;
            }

            .plan-card.active {
                border-color: rgba(123, 28, 46, 0.34);
                box-shadow: 0 22px 44px rgba(94, 20, 35, 0.13);
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
                background: rgba(44, 91, 180, 0.08);
                border: 1px solid rgba(44, 91, 180, 0.12);
                color: #2c4f90;
            }

            .plan-card.active .plan-badge {
                background: rgba(123, 28, 46, 0.16);
                border-color: rgba(123, 28, 46, 0.24);
                color: #7b1c2e;
            }

            .plan-summary {
                color: var(--card-muted);
                min-height: 50px;
            }

            .plan-fit {
                padding: 12px 14px;
                border-radius: 16px;
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
