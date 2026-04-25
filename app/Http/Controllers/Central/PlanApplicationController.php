<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Mail\TenantPlanApplicationPendingApprovalMail;
use App\Models\TenantDomain;
use App\Models\TenantPlanApplication;
use App\Support\Billing\PlanCatalog;
use App\Support\Billing\StripeCheckout;
use App\Support\Security\AuditLogger;
use App\Support\Tenancy\TenantProvisioner;
use App\Support\Tenancy\TenantUrlGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class PlanApplicationController extends Controller
{
    public function __construct(
        protected TenantProvisioner $tenantProvisioner,
        protected StripeCheckout $stripeCheckout,
        protected TenantUrlGenerator $tenantUrlGenerator,
    ) {
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'college_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'admin_email' => ['required', 'email', 'max:255'],
            'selected_plan' => ['required', Rule::in(array_keys(PlanCatalog::all()))],
            'preferred_subdomain' => ['nullable', 'alpha_dash', 'max:100'],
            'preferred_domain' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $plan = PlanCatalog::find($validated['selected_plan']);
        abort_unless($plan, 404);

        if ($this->shouldBypassStripeCheckout($plan)) {
            $application = TenantPlanApplication::query()->create([
                ...$validated,
                'preferred_subdomain' => $validated['preferred_subdomain'] ?: null,
                'preferred_domain' => $validated['preferred_domain'] ?: null,
                'payment_status' => 'manual_test',
                'payment_amount' => $plan['amount'],
                'payment_currency' => $plan['currency'],
                'status' => 'pending_approval',
                'paid_at' => now(),
                'stripe_customer_email' => $validated['contact_email'],
            ]);

            $mailFailures = $this->sendPendingApprovalNotifications($application);

            $response = redirect()->route('app.entry')
                ->with('status', 'Local development checkout bypassed. The plan application is marked paid and is now waiting for Bukidnon State University approval.');

            if ($mailFailures !== []) {
                $response->withErrors([
                    'mail' => 'Payment was recorded, but the confirmation email could not be sent. Check your MAIL settings and the application log.',
                ]);
            }

            return $response;
        }

        if (! $this->stripeCheckout->isConfigured()) {
            throw ValidationException::withMessages([
                'selected_plan' => 'Stripe test payment is not configured yet. Add your Stripe test secret key to `.env` first.',
            ]);
        }

        $application = TenantPlanApplication::query()->create([
            ...$validated,
            'preferred_subdomain' => $validated['preferred_subdomain'] ?: null,
            'preferred_domain' => $validated['preferred_domain'] ?: null,
            'payment_status' => 'pending',
            'payment_amount' => $plan['amount'],
            'payment_currency' => $plan['currency'],
            'status' => 'pending_payment',
        ]);

        try {
            $checkout = $this->stripeCheckout->createCheckoutSession($application, $plan);
        } catch (Throwable $exception) {
            report($exception);

            $application->update([
                'status' => 'payment_error',
            ]);

            $message = 'Stripe Checkout could not be started. Verify your Stripe test credentials and try again.';

            if (app()->hasDebugModeEnabled()) {
                $message .= ' '.$exception->getMessage();
            }

            throw ValidationException::withMessages([
                'selected_plan' => $message,
            ]);
        }

        $application->update([
            'stripe_checkout_session_id' => $checkout['session_id'],
            'stripe_payment_intent_id' => $checkout['payment_intent_id'] ?: null,
            'stripe_subscription_id' => $checkout['subscription_id'] ?: null,
        ]);

        return redirect()->away($checkout['url']);
    }

    public function success(Request $request, TenantPlanApplication $application): RedirectResponse
    {
        $sessionId = (string) $request->query('session_id', $application->stripe_checkout_session_id);

        if (! $this->stripeCheckout->isConfigured() || blank($sessionId)) {
            return redirect()->route('app.entry')
                ->withErrors(['payment' => 'Stripe payment confirmation could not be verified.']);
        }

        try {
            $session = $this->stripeCheckout->retrieveCheckoutSession($sessionId);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('app.entry')
                ->withErrors(['payment' => 'Stripe payment confirmation could not be verified.']);
        }

        if (
            ($session['status'] ?? null) !== 'complete'
            || ! in_array((string) ($session['payment_status'] ?? ''), ['paid', 'no_payment_required'], true)
            || blank($session['subscription'] ?? null)
        ) {
            $application->update([
                'payment_status' => 'pending',
                'status' => 'pending_payment',
            ]);

            return redirect()->route('app.entry')
                ->withErrors(['payment' => 'The Stripe subscription checkout is not marked as complete yet.']);
        }

        $application->update([
            'payment_status' => 'paid',
            'status' => 'pending_approval',
            'paid_at' => now(),
            'stripe_checkout_session_id' => $sessionId,
            'stripe_payment_intent_id' => $session['payment_intent'] ?? $application->stripe_payment_intent_id,
            'stripe_subscription_id' => $session['subscription'] ?? $application->stripe_subscription_id,
            'stripe_customer_email' => $session['customer_details']['email'] ?? $application->contact_email,
        ]);

        $mailFailures = $this->sendPendingApprovalNotifications($application);

        $response = redirect()->route('app.entry')
            ->with('status', 'Payment received. Your plan application is now waiting for Bukidnon State University approval. The tenant portal and coordinator credentials will only be created after approval.');

        if ($mailFailures !== []) {
            $response->withErrors([
                'mail' => 'Payment was successful, but the confirmation email could not be sent. Check your MAIL settings and the application log.',
            ]);
        }

        return $response;
    }

    public function cancel(TenantPlanApplication $application): RedirectResponse
    {
        $application->update([
            'status' => 'payment_cancelled',
        ]);

        return redirect()->route('app.entry')
            ->withErrors(['payment' => 'Stripe checkout was cancelled. You can review the form and try again.']);
    }

    public function approve(Request $request, TenantPlanApplication $application): RedirectResponse
    {
        if (! $application->canBeApproved()) {
            return redirect()->route('central.dashboard', ['section' => 'applications'])
                ->withErrors(['approval' => 'Only paid applications that are still waiting for approval can be approved.']);
        }

        $validated = $request->validate([
            'subdomain' => ['nullable', 'alpha_dash', 'max:100'],
            'domain' => ['nullable', 'string', 'max:255'],
            'subscription_starts_at' => ['required', 'date'],
            'subscription_expires_at' => ['nullable', 'date', 'after_or_equal:subscription_starts_at'],
            'bandwidth_limit_gb' => ['required', 'numeric', 'min:1', 'max:100000'],
            'bandwidth_used_gb' => ['nullable', 'numeric', 'min:0', 'lte:bandwidth_limit_gb'],
            'admin_password' => ['required', 'string', 'min:8'],
            'approval_notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $domainHosts = $this->resolvedDomainHosts($validated, $application);
        $this->ensureDomainHostsAreAvailable($domainHosts);
        $databaseName = $this->approvedDatabaseName($application);
        $subscriptionExpiresAt = filled($validated['subscription_expires_at'] ?? null)
            ? $validated['subscription_expires_at']
            : Carbon::parse($validated['subscription_starts_at'])->addMonthNoOverflow()->toDateString();

        try {
            $tenant = $this->tenantProvisioner->provision([
                'name' => $application->college_name,
                'plan' => $application->selected_plan,
                'subscription_starts_at' => $validated['subscription_starts_at'],
                'subscription_expires_at' => $subscriptionExpiresAt,
                'domain_hosts' => $domainHosts,
                'database' => $databaseName,
                'admin_email' => $application->admin_email,
                'admin_password' => $validated['admin_password'],
                'settings' => [
                    'provisioned_by' => 'approved_plan_application',
                    'application_id' => $application->getKey(),
                    'branding' => [
                        'portal_title' => 'University Practicum',
                        'accent' => '#7B1C2E',
                        'secondary' => '#F5A623',
                        'logo_path' => null,
                    ],
                    'bandwidth' => [
                        'limit_gb' => (float) $validated['bandwidth_limit_gb'],
                        'used_gb' => (float) ($validated['bandwidth_used_gb'] ?? 0),
                        'updated_at' => now()->toDateTimeString(),
                    ],
                ],
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('central.dashboard', [
                'section' => 'applications',
                'review' => $application->getKey(),
            ])->withErrors([
                'approval' => $this->provisioningErrorMessage($exception),
            ]);
        }

        $application->update([
            'tenant_id' => $tenant->getKey(),
            'status' => 'approved',
            'reviewed_by' => Auth::guard('central_superadmin')->id(),
            'reviewed_at' => now(),
            'approval_notes' => $validated['approval_notes'] ?? null,
            'rejection_reason' => null,
        ]);
        $this->audit($request, 'approved application', $application, null, [
            'tenant_id' => $tenant->getKey(),
            'status' => 'approved',
        ]);

        return redirect()->route('central.dashboard', ['section' => 'applications'])
            ->with('status', $application->college_name.' was approved and provisioned successfully.');
    }

    public function reject(Request $request, TenantPlanApplication $application): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);
        $oldValues = $application->only(['status', 'rejection_reason']);

        $application->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::guard('central_superadmin')->id(),
            'reviewed_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);
        $this->audit($request, 'rejected application', $application, $oldValues, [
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return redirect()->route('central.dashboard', ['section' => 'applications'])
            ->with('status', $application->college_name.' was marked as rejected.');
    }

    public static function suggestedDatabaseName(
        string $collegeName,
        ?string $adminEmail = null,
        int|string|null $applicationKey = null,
    ): string
    {
        return substr(hash('sha256', implode('|', [
            $applicationKey ?? 'preview',
            Str::lower(trim($collegeName)),
            Str::lower(trim((string) $adminEmail)),
        ])), 0, 24);
    }

    public static function suggestedSubdomain(string $collegeName): string
    {
        return (string) Str::of($collegeName)
            ->lower()
            ->replaceMatches('/^college of /', '')
            ->slug();
    }

    protected function resolvedDomainHosts(array $validated, ?TenantPlanApplication $application = null): array
    {
        $hosts = [];
        $subdomain = $validated['subdomain'] ?? ($application?->preferred_subdomain);
        $customDomain = $validated['domain'] ?? null;

        // Priority order for primary domain:
        // 1. Custom domain if provided (user's explicit choice)
        // 2. Localhost subdomain if subdomain provided (for local development)

        if (filled($customDomain)) {
            $hosts[] = strtolower(trim((string) $customDomain));
        } elseif (filled($subdomain)) {
            $hosts[] = strtolower(trim((string) $subdomain)) . '.localhost';
        }

        // Add localhost aliases for local development.
        $localAliases = $this->tenantUrlGenerator->localAliasHosts(
            $subdomain,
            $application?->college_name,
            null,
        );

        $hosts = array_merge($hosts, $localAliases);

        return array_values(array_unique(array_filter($hosts)));
    }

    protected function ensureDomainHostsAreAvailable(array $hosts): void
    {
        foreach ($hosts as $host) {
            if (TenantDomain::query()->whereRaw('LOWER(host) = ?', [strtolower($host)])->exists()) {
                throw ValidationException::withMessages([
                    'domain' => "The host {$host} is already assigned to another tenant.",
                ]);
            }
        }
    }

    protected function shouldBypassStripeCheckout(array $plan): bool
    {
        if (! app()->isLocal()) {
            return false;
        }

        return (bool) config('services.stripe.local_bypass', false);
    }

    protected function sendPendingApprovalNotifications(TenantPlanApplication $application): array
    {
        $failures = [];

        collect([$application->contact_email, $application->admin_email])
            ->filter()
            ->unique()
            ->each(function (string $email) use ($application, &$failures): void {
                try {
                    Mail::to($email)->send(new TenantPlanApplicationPendingApprovalMail($application));
                } catch (Throwable $exception) {
                    report($exception);

                    $failures[$email] = $exception->getMessage();
                }
            });

        return $failures;
    }

    protected function approvedDatabaseName(TenantPlanApplication $application): string
    {
        $base = self::suggestedDatabaseName(
            $application->college_name,
            $application->admin_email,
            $application->getKey(),
        );

        $candidate = $base;
        $suffix = 0;

        while (Tenant::query()->where('database', $candidate)->exists()) {
            $suffix++;
            $candidate = $base.'_'.str_pad((string) $suffix, 4, '0', STR_PAD_LEFT);
        }

        return $candidate;
    }

    protected function provisioningErrorMessage(Throwable $exception): string
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'SQLSTATE[HY000] [2002]')) {
            return 'The tenant could not be provisioned because the central MySQL server is unreachable at 127.0.0.1:3306. Start MySQL and try approving the application again.';
        }

        if (app()->hasDebugModeEnabled()) {
            return 'The tenant could not be provisioned. '.$message;
        }

        return 'The tenant could not be provisioned. Check your database settings and try again.';
    }

    protected function audit(Request $request, string $action, TenantPlanApplication $application, ?array $oldValues = null, ?array $newValues = null): void
    {
        $actor = Auth::guard('central_superadmin')->user();

        AuditLogger::log(
            'central_superadmin',
            $actor?->getKey(),
            $actor?->name,
            $action,
            $application,
            $oldValues,
            $newValues,
            $request,
        );
    }
}
