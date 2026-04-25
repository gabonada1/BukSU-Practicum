<?php

namespace App\Support\Tenancy;

use App\Mail\TenantAdminCredentialsMail;
use App\Models\SystemRelease;
use App\Models\Tenant;
use App\Models\TenantAdmin;
use App\Models\TenantDomain;
use App\Support\Security\PasswordGenerator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

class TenantProvisioner
{
    public function __construct(
        protected TenantDatabaseManager $tenantDatabaseManager,
        protected PasswordGenerator $passwordGenerator,
        protected TenantUrlGenerator $urlGenerator,
    ) {
    }

    public function provision(array $data): Tenant
    {
        $tenantCode = $data['code'] ?? $this->urlGenerator->acronymCode((string) $data['name']);
        $adminName = $data['admin_name'] ?? $data['name'].' Internship Coordinator';
        $adminPassword = filled($data['admin_password'] ?? null)
            ? $data['admin_password']
            : $this->passwordGenerator->generate();
        $tenant = null;
        $databaseCreated = false;

        try {
            $tenant = Tenant::query()->create([
                'name' => $data['name'],
                'code' => $tenantCode,
                'plan' => $data['plan'],
                'subscription_starts_at' => $data['subscription_starts_at'],
                'subscription_expires_at' => $data['subscription_expires_at'] ?? null,
                'database' => $data['database'],
                'db_host' => $data['db_host'] ?? env('TENANT_DB_HOST', '127.0.0.1'),
                'db_port' => $data['db_port'] ?? env('TENANT_DB_PORT', '3306'),
                'db_username' => $data['db_username'] ?? env('TENANT_DB_USERNAME', 'root'),
                'db_password' => $data['db_password'] ?? env('TENANT_DB_PASSWORD', ''),
                'is_active' => $data['is_active'] ?? true,
                'settings' => $this->tenantSettings($data['settings'] ?? null),
            ]);

            $this->storeDomains($tenant, $this->domainHostsFromData($data));
            $this->createTenantDatabase($tenant->database);
            $databaseCreated = true;
            $this->tenantDatabaseManager->connect($tenant);

            Artisan::call('migrate', [
                '--database' => config('tenancy.tenant_connection', 'tenant'),
                '--path' => 'database/migrations/tenant',
                '--realpath' => false,
                '--force' => true,
            ]);

            $admin = TenantAdmin::query()->create([
                'name' => $adminName,
                'email' => $data['admin_email'],
                'password' => $adminPassword,
                'must_change_password' => true,
                'is_active' => true,
            ]);

            // Guard against any unexpected password drift so the emailed
            // temporary password always matches the stored coordinator login.
            if (! Hash::check($adminPassword, (string) $admin->getRawOriginal('password'))) {
                $admin->forceFill([
                    'password' => $adminPassword,
                ])->save();
                $admin->refresh();
            }

            rescue(function () use ($tenant, $adminName, $data, $adminPassword) {
                Mail::to($data['admin_email'])->send(
                    new TenantAdminCredentialsMail(
                        $tenant,
                        $adminName,
                        $data['admin_email'],
                        $adminPassword,
                        $this->urlGenerator,
                    )
                );
            }, report: true);

            return $tenant;
        } catch (Throwable $exception) {
            if ($tenant?->exists) {
                rescue(fn () => $tenant->delete(), report: false);
            }

            if ($databaseCreated && filled($data['database'] ?? null)) {
                rescue(fn () => $this->dropTenantDatabase($data['database']), report: false);
            }

            throw $exception;
        }
    }

    protected function createTenantDatabase(string $database): void
    {
        $databaseName = str_replace('`', '', $database);

        DB::connection(config('tenancy.central_connection', 'central'))
            ->statement("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    protected function tenantSettings(?array $settings): array
    {
        $settings ??= [
            'provisioned_by' => 'central_superadmin',
            'branding' => [
                'portal_title' => 'University Practicum',
                'accent' => '#7B1C2E',
                'secondary' => '#F5A623',
                'logo_path' => null,
            ],
        ];

        if (! array_key_exists('release_preferences', $settings)) {
            $settings['release_preferences'] = $this->defaultReleasePreferences();
        }

        return $settings;
    }

    protected function defaultReleasePreferences(): array
    {
        $latestRelease = SystemRelease::latestPublished();

        if ($latestRelease) {
            return $latestRelease->releasePreferenceSettings();
        }

        $version = (string) config('app.version', '1.0.0');

        return [
            'preferred_release_id' => null,
            'preferred_release_version' => $version,
            'preferred_release_tag' => $version,
        ];
    }

    protected function dropTenantDatabase(string $database): void
    {
        $databaseName = str_replace('`', '', $database);

        DB::connection(config('tenancy.central_connection', 'central'))
            ->statement("DROP DATABASE IF EXISTS `{$databaseName}`");
    }

    protected function domainHostsFromData(array $data): Collection
    {
        $hosts = collect($data['domain_hosts'] ?? []);

        if ($hosts->isEmpty()) {
            if (filled($data['domain'] ?? null)) {
                $hosts->push($data['domain']);
            }

            if (filled($data['subdomain'] ?? null)) {
                $hosts->push($this->urlGenerator->subdomainHost((string) $data['subdomain']));
            }
        }

        $hosts = $hosts->merge(
            $this->urlGenerator->localAliasHosts(
                $data['subdomain'] ?? null,
                $data['name'] ?? null,
                $data['code'] ?? null,
            )
        );

        return $hosts
            ->filter(fn ($host) => filled($host))
            ->map(fn ($host) => strtolower(trim((string) $host)))
            ->unique()
            ->values();
    }

    protected function storeDomains(Tenant $tenant, Collection $hosts): void
    {
        foreach ($hosts->values() as $index => $host) {
            TenantDomain::query()->updateOrCreate(
                ['host' => $host],
                [
                    'tenant_id' => $tenant->getKey(),
                    'is_primary' => $index === 0,
                    'is_active' => true,
                ]
            );
        }
    }
}
