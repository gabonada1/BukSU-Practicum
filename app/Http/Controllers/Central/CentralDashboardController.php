<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantPlanApplication;
use App\Support\Billing\PlanCatalog;
use App\Support\Security\AuditLogReader;
use App\Support\Tenancy\TenantAdminContactResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class CentralDashboardController extends Controller
{
    public function __construct(
        protected TenantAdminContactResolver $contactResolver,
        protected AuditLogReader $auditLogReader,
    ) {}

    public function __invoke(): View
    {
        $tenants = Tenant::query()->with(['domains', 'primaryDomain'])->latest()->paginate(5, ['*'], 'tenants_page')->withQueryString();
        $applications = TenantPlanApplication::query()->with(['tenant.domains', 'tenant.primaryDomain'])->latest()->paginate(5, ['*'], 'applications_page')->withQueryString();

        // Get all records for statistics
        $allTenants = Tenant::query()->with(['domains', 'primaryDomain'])->latest()->get();
        $allApplications = TenantPlanApplication::query()->with(['tenant.domains', 'tenant.primaryDomain'])->latest()->get();

        $tenantBandwidthProfiles = $allTenants
            ->map(fn (Tenant $tenant) => $this->bandwidthProfile($tenant))
            ->keyBy('id');
        $tenantContacts = $allTenants->mapWithKeys(function (Tenant $tenant): array {
            $contact = rescue(fn () => $this->contactResolver->contacts($tenant)->first(), report: false);

            return [
                $tenant->getKey() => [
                    'name' => $contact->name ?? null,
                    'email' => $contact->email ?? null,
                ],
            ];
        });

        return view('central.dashboard', [
            'pageTitle' => 'University Practicum Administration | '.config('app.name', 'University Practicum'),
            'tenants' => $tenants,
            'applications' => $applications,
            'plans' => PlanCatalog::all(),
            'centralResponsibilities' => [
                'Review incoming university practicum applications and verify Stripe payments.',
                'Approve applications to provision a university portal database and coordinator account.',
                'Manage university registry metadata, subscriptions, and portal access from the central layer.',
            ],
            'tenantResponsibilities' => [
                'Authenticate internship coordinators, company supervisors, and students inside each university portal.',
                'Run practicum workflows using the university database, records, and dashboards.',
                'Store university-specific partner organizations, student applications, forms and requirements, progress reports, and evaluations.',
            ],
            'bandwidthTotals' => $this->bandwidthTotals($tenantBandwidthProfiles),
            'stats' => [
                'active_tenants' => $allTenants->filter(fn (Tenant $tenant) => $tenant->canAccessTenantApp())->count(),
                'suspended_tenants' => $allTenants->filter(fn (Tenant $tenant) => $tenant->subscriptionStatus() === 'suspended')->count(),
                'expiring_tenants' => $allTenants->filter(fn (Tenant $tenant) => $tenant->subscription_expires_at?->isBetween(now()->startOfDay(), now()->addDays(30)->endOfDay()))->count(),
                'pending_applications' => $allApplications->whereIn('status', ['submitted', 'pending_approval'])->count(),
                'paid_applications' => $allApplications->where('payment_status', 'paid')->count(),
                'premium_plans' => $allTenants->where('plan', 'premium')->count(),
            ],
            'planBandwidthDefaults' => $this->bandwidthDefaults(),
            'tenantContacts' => $tenantContacts,
            'tenantBandwidthProfiles' => $tenantBandwidthProfiles,
            'auditLogs' => $this->auditLogReader->all(),
            'applicationReviewBaseUrl' => route('central.dashboard').'?section=applications',
            'logoutAction' => route('central.logout'),
        ]);
    }

    protected function bandwidthProfile(Tenant $tenant): array
    {
        $settings = is_array($tenant->settings) ? $tenant->settings : [];
        $configuredLimit = data_get($settings, 'bandwidth.limit_gb');
        $configuredUsed = data_get($settings, 'bandwidth.used_gb', 0);
        $limit = max((float) ($configuredLimit ?? $this->defaultBandwidthForPlan($tenant->plan)), 1);
        $used = max(min((float) $configuredUsed, $limit), 0);
        $available = max($limit - $used, 0);
        $utilization = $limit > 0 ? round(($used / $limit) * 100, 1) : 0.0;

        return [
            'id' => $tenant->getKey(),
            'tenant' => $tenant,
            'limit_gb' => $limit,
            'used_gb' => $used,
            'available_gb' => $available,
            'utilization_pct' => $utilization,
        ];
    }

    protected function bandwidthTotals(Collection $profiles): array
    {
        $allocated = (float) $profiles->sum('limit_gb');
        $used = (float) $profiles->sum('used_gb');
        $available = max($allocated - $used, 0);

        return [
            'allocated_gb' => $allocated,
            'used_gb' => $used,
            'available_gb' => $available,
            'utilization_pct' => $allocated > 0 ? round(($used / $allocated) * 100, 1) : 0.0,
        ];
    }

    protected function bandwidthDefaults(): array
    {
        return [
            'basic' => $this->defaultBandwidthForPlan('basic'),
            'pro' => $this->defaultBandwidthForPlan('pro'),
            'premium' => $this->defaultBandwidthForPlan('premium'),
        ];
    }

    protected function defaultBandwidthForPlan(?string $plan): float
    {
        return match ($plan) {
            'basic' => 150,
            'pro' => 400,
            default => 1000,
        };
    }
}
