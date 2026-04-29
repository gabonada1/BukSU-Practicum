<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Support\Tenancy\TenantUrlGenerator;
use Illuminate\Database\Seeder;

class CollegeSeeder extends Seeder
{
    public function run(): void
    {
        $tenantUrlGenerator = app(TenantUrlGenerator::class);

        $colleges = [
            [
                'name' => 'College of Nursing',
                'code' => 'CON',
                'subdomain' => 'nursing',
                'plan' => 'premium',
                'database' => 'buksu_college_of_nursing',
                'domain' => $tenantUrlGenerator->subdomainHost('nursing'),
            ],
            [
                'name' => 'College of Business',
                'code' => 'COB',
                'subdomain' => 'business',
                'plan' => 'premium',
                'database' => 'buksu_college_of_business',
                'domain' => $tenantUrlGenerator->subdomainHost('business'),
            ],
            [
                'name' => 'College of Technologies',
                'code' => 'COT',
                'subdomain' => 'technology',
                'plan' => 'premium',
                'database' => env('TENANT_DB_DATABASE', 'buksu_college_of_technologies'),
                'domain' => env('TENANT_DOMAIN', $tenantUrlGenerator->subdomainHost('technology')),
            ],
            [
                'name' => 'College of Public Administration',
                'code' => 'CPA',
                'subdomain' => 'public-admin',
                'plan' => 'premium',
                'database' => 'buksu_college_of_public_administration',
                'domain' => $tenantUrlGenerator->subdomainHost('public-admin'),
            ],
            [
                'name' => 'College of Education',
                'code' => 'COED',
                'subdomain' => 'education',
                'plan' => 'premium',
                'database' => 'buksu_college_of_education',
                'domain' => $tenantUrlGenerator->subdomainHost('education'),
            ],
            [
                'name' => 'College of Arts & Sciences',
                'code' => 'CAS',
                'subdomain' => 'arts-sciences',
                'plan' => 'premium',
                'database' => 'buksu_college_of_arts_and_sciences',
                'domain' => $tenantUrlGenerator->subdomainHost('arts-sciences'),
            ],
        ];

        foreach ($colleges as $college) {
            $tenant = Tenant::query()->updateOrCreate(
                ['code' => $college['code']],
                [
                    'name' => $college['name'],
                    'code' => $college['code'],
                    'plan' => $college['plan'],
                    'subscription_starts_at' => now()->toDateString(),
                    'subscription_expires_at' => now()->addYear()->toDateString(),
                    'database' => $college['database'],
                    'db_host' => env('TENANT_DB_HOST', '127.0.0.1'),
                    'db_port' => env('TENANT_DB_PORT', '3306'),
                    'db_username' => env('TENANT_DB_USERNAME', 'root'),
                    'db_password' => env('TENANT_DB_PASSWORD', ''),
                    'is_active' => true,
                    'settings' => [
                        'focus' => $college['name'],
                        'branding' => [
                            'portal_title' => 'University Practicum',
                            'accent' => '#7B1C2E',
                            'secondary' => '#F5A623',
                            'logo_path' => null,
                        ],
                    ],
                ]
            );

            collect(array_merge(
                [$college['domain']],
                $tenantUrlGenerator->localAliasHosts($college['subdomain'], $college['name'], $college['code'])
            ))->unique()->values()->each(function (string $host) use ($tenant, $college): void {
                TenantDomain::query()->updateOrCreate(
                    ['host' => $host],
                    [
                        'tenant_id' => $tenant->getKey(),
                        'is_primary' => $host === $college['domain'],
                        'is_active' => true,
                    ]
                );
            });
        }
    }
}
