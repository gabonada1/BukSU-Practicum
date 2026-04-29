<?php

namespace App\Support\Billing;

class PlanCatalog
{
    public static function all(): array
    {
        return [
            'basic' => [
                'key' => 'basic',
                'label' => 'Basic',
                'amount' => self::amountFromEnv('PLAN_BASIC_AMOUNT', 29900),
                'currency' => self::currencyFromEnv(),
                'stripe_price_id' => self::stripePriceIdFromEnv('STRIPE_PRICE_BASIC'),
                'summary' => 'Core OJT portal for managing practicum records, submissions, and reviews.',
                'best_for' => 'Essential practicum operations in one tenant workspace.',
                'student_limit' => '150 GB allocation',
                'features' => [
                    'Separate tenant portal and database',
                    'Coordinator, student, and supervisor logins',
                    'Partner company, student, supervisor, and user records',
                    'Internship applications with document uploads',
                    'Requirements, OJT hour logs, and supervisor validation',
                    'Dashboard, audit logs, RBAC, support, courses, and update controls',
                ],
            ],
            'pro' => [
                'key' => 'pro',
                'label' => 'Pro',
                'amount' => self::amountFromEnv('PLAN_PRO_AMOUNT', 59900),
                'currency' => self::currencyFromEnv(),
                'stripe_price_id' => self::stripePriceIdFromEnv('STRIPE_PRICE_PRO'),
                'summary' => 'Core OJT portal plus branding controls and a larger tenant allocation.',
                'best_for' => 'Branded practicum portals with heavier upload activity.',
                'student_limit' => '400 GB allocation',
                'features' => [
                    'Everything in Basic',
                    'Custom portal title and logo',
                    'Custom theme colors',
                    'Branded login, dashboard, profile, and portal pages',
                    '400 GB tenant allocation',
                ],
            ],
            'premium' => [
                'key' => 'premium',
                'label' => 'Premium',
                'amount' => self::amountFromEnv('PLAN_PREMIUM_AMOUNT', 99900),
                'currency' => self::currencyFromEnv(),
                'stripe_price_id' => self::stripePriceIdFromEnv('STRIPE_PRICE_PREMIUM'),
                'summary' => 'Highest-capacity plan for large branded practicum portals.',
                'best_for' => 'Large colleges with the heaviest document upload volume.',
                'student_limit' => '1,000 GB allocation',
                'features' => [
                    'Everything in Pro',
                    '1,000 GB tenant allocation',
                    'Built for high-volume application and requirement uploads',
                    'Supports larger student, supervisor, company, and user records',
                    'Full branding controls',
                ],
            ],
        ];
    }

    public static function find(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    protected static function currencyFromEnv(): string
    {
        return strtolower((string) env('BILLING_CURRENCY', 'PHP'));
    }

    protected static function amountFromEnv(string $key, int $fallback): int
    {
        $value = trim((string) env($key, ''));

        if ($value === '' || ! is_numeric($value)) {
            return $fallback;
        }

        return (int) round(((float) $value) * 100);
    }

    protected static function stripePriceIdFromEnv(string $key): ?string
    {
        $value = trim((string) env($key, ''));

        return $value !== '' ? $value : null;
    }
}
