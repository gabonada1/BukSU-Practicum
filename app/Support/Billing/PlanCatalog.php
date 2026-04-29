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
                'summary' => 'Includes the core practicum portal features available to every tenant workspace.',
                'best_for' => 'Colleges that need the essential OJT records, submissions, reviews, and role portals in one workspace.',
                'student_limit' => '150 GB dashboard allocation',
                'features' => [
                    'Separate tenant database and access domain for one college portal',
                    'Coordinator, student, and company supervisor login areas',
                    'Partner company, supervisor, student, and user management records',
                    'Student registration, email verification, and password reset flows',
                    'Internship application tracking with resume, MOA, endorsement, and clearance uploads',
                    'Requirement submissions, review statuses, OJT hour logs, and supervisor hour validation',
                    'Coordinator dashboard, audit logs, RBAC controls, support tickets, courses, OJT settings, and update controls',
                ],
            ],
            'pro' => [
                'key' => 'pro',
                'label' => 'Pro',
                'amount' => self::amountFromEnv('PLAN_PRO_AMOUNT', 59900),
                'currency' => self::currencyFromEnv(),
                'stripe_price_id' => self::stripePriceIdFromEnv('STRIPE_PRICE_PRO'),
                'summary' => 'Adds portal branding access and a larger allocation shown in the central dashboard.',
                'best_for' => 'Colleges that want the full core workflow plus their own logo, portal title, and theme colors.',
                'student_limit' => '400 GB dashboard allocation',
                'features' => [
                    'Everything in Basic',
                    'Custom portal title and college logo',
                    'Custom accent, secondary, page, surface, text, and border colors',
                    'Branded tenant login, dashboard, profile, and portal pages through saved theme settings',
                    'Larger bandwidth allocation used by the central tenant usage dashboard',
                    'Same application, requirement, OJT hour, course, RBAC, support, audit, and update tools as Basic',
                    'Best fit for colleges with heavier upload activity and a branded portal requirement',
                ],
            ],
            'premium' => [
                'key' => 'premium',
                'label' => 'Premium',
                'amount' => self::amountFromEnv('PLAN_PREMIUM_AMOUNT', 99900),
                'currency' => self::currencyFromEnv(),
                'stripe_price_id' => self::stripePriceIdFromEnv('STRIPE_PRICE_PREMIUM'),
                'summary' => 'Provides the largest allocation for the full branded tenant portal experience.',
                'best_for' => 'Colleges with the heaviest document upload volume and the strongest need for a polished branded workspace.',
                'student_limit' => '1,000 GB dashboard allocation',
                'features' => [
                    'Everything in Pro',
                    'Largest bandwidth allocation used by the central tenant usage dashboard',
                    'Best fit for high-volume application and requirement document uploads',
                    'Best fit for larger rosters of students, supervisors, partner companies, and tenant users',
                    'Full branding controls for the college-specific portal experience',
                    'Same coordinator tools as Basic and Pro, including RBAC, audit logs, support, courses, OJT settings, and update controls',
                    'Central dashboard can track this plan alongside tenant status, subscription dates, and usage allocation',
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
