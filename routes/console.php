<?php

use App\Support\Documentation\ProjectDocumentationWriter;
use App\Models\Tenant;
use App\Support\Tenancy\TenantDatabaseManager;
use App\Support\Tenancy\TenantSubscriptionNotifier;
use Database\Seeders\TenantDatabaseSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schedule;

$detectLocalLiveCodebasePath = function (): ?string {
    $configuredTarget = env('LOCAL_LIVE_CODEBASE_PATH');

    if (filled($configuredTarget)) {
        return rtrim(str_replace('\\', '/', $configuredTarget), '/');
    }

    $apacheConfigPath = 'C:/xampp/apache/conf/extra/buksu-practicum.conf';

    if (! File::exists($apacheConfigPath)) {
        return null;
    }

    $contents = File::get($apacheConfigPath);

    if (! preg_match('/DocumentRoot\s+"([^"]+)"/i', $contents, $matches)) {
        return null;
    }

    $documentRoot = rtrim(str_replace('\\', '/', $matches[1]), '/');

    return basename($documentRoot) === 'public'
        ? dirname($documentRoot)
        : $documentRoot;
};

$syncPath = function (string $source, string $target, array $excludedNames = []) use (&$syncPath): void {
    if (File::isDirectory($source)) {
        File::ensureDirectoryExists($target);

        foreach (File::allFiles($source, true) as $file) {
            $relativePath = str_replace('\\', '/', $file->getRelativePathname());
            $segments = explode('/', $relativePath);

            if (collect($segments)->contains(fn (string $segment) => in_array($segment, $excludedNames, true))) {
                continue;
            }

            $targetPath = $target.'/'.$relativePath;
            File::ensureDirectoryExists(dirname($targetPath));
            File::copy($file->getPathname(), $targetPath);
        }

        return;
    }

    if (! File::exists($source)) {
        return;
    }

    File::ensureDirectoryExists(dirname($target));
    File::copy($source, $target);
};

Artisan::command('local:deploy-live {--target=} {--tenant=*} {--skip-migrate} {--skip-build}', function () use ($detectLocalLiveCodebasePath, $syncPath) {
    $sourceBasePath = rtrim(str_replace('\\', '/', base_path()), '/');
    $targetOption = $this->option('target');
    $targetBasePath = $targetOption
        ? rtrim(str_replace('\\', '/', $targetOption), '/')
        : $detectLocalLiveCodebasePath();

    if (! filled($targetBasePath)) {
        $this->error('No live codebase path was detected. Pass --target=PATH or set LOCAL_LIVE_CODEBASE_PATH in .env.');

        return self::FAILURE;
    }

    if (basename($targetBasePath) === 'public') {
        $targetBasePath = dirname($targetBasePath);
    }

    if (! File::isDirectory($targetBasePath)) {
        $this->error("Target path does not exist: {$targetBasePath}");

        return self::FAILURE;
    }

    if (realpath($sourceBasePath) === realpath($targetBasePath)) {
        $this->error('The target path is the same as the current codebase. Nothing to deploy.');

        return self::FAILURE;
    }

    $itemsToSync = [
        ['path' => 'app', 'exclude' => []],
        ['path' => 'bootstrap', 'exclude' => ['cache']],
        ['path' => 'config', 'exclude' => []],
        ['path' => 'database', 'exclude' => []],
        ['path' => 'docs', 'exclude' => []],
        ['path' => 'public', 'exclude' => ['uploads', 'storage']],
        ['path' => 'resources', 'exclude' => []],
        ['path' => 'routes', 'exclude' => []],
        ['path' => 'scripts', 'exclude' => []],
        ['path' => 'tests', 'exclude' => []],
        ['path' => 'artisan', 'exclude' => []],
        ['path' => 'composer.json', 'exclude' => []],
        ['path' => 'composer.lock', 'exclude' => []],
        ['path' => 'package.json', 'exclude' => []],
        ['path' => 'package-lock.json', 'exclude' => []],
        ['path' => 'vite.config.js', 'exclude' => []],
        ['path' => 'phpunit.xml', 'exclude' => []],
        ['path' => '.editorconfig', 'exclude' => []],
        ['path' => '.env.example', 'exclude' => []],
        ['path' => '.gitattributes', 'exclude' => []],
        ['path' => '.gitignore', 'exclude' => []],
        ['path' => 'README.md', 'exclude' => []],
    ];

    $this->components->info("Deploying from [{$sourceBasePath}]");
    $this->components->info("Deploying to   [{$targetBasePath}]");

    foreach ($itemsToSync as $item) {
        $sourcePath = $sourceBasePath.'/'.$item['path'];
        $targetPath = $targetBasePath.'/'.$item['path'];

        $syncPath($sourcePath, $targetPath, $item['exclude']);
        $this->line("Synced {$item['path']}");
    }

    File::delete($targetBasePath.'/bootstrap/cache/config.php');
    File::delete($targetBasePath.'/bootstrap/cache/packages.php');
    File::delete($targetBasePath.'/bootstrap/cache/routes-v7.php');
    File::delete($targetBasePath.'/bootstrap/cache/services.php');

    $phpBinary = PHP_BINARY;
    $npmBinary = PHP_OS_FAMILY === 'Windows' ? 'npm.cmd' : 'npm';
    $tenantSlugs = collect($this->option('tenant'))
        ->filter(fn ($value) => filled($value))
        ->values();

    $runInTarget = function (array $command, string $label) use ($targetBasePath) {
        $result = Process::path($targetBasePath)->timeout(1800)->run($command);

        if ($result->failed()) {
            $this->error("{$label} failed.");
            $this->line(trim($result->errorOutput()) !== '' ? trim($result->errorOutput()) : trim($result->output()));

            return false;
        }

        $this->components->info($label.' completed.');

        return true;
    };

    if (! $runInTarget([$phpBinary, 'artisan', 'optimize:clear'], 'Laravel cache clear')) {
        return self::FAILURE;
    }

    if (! $runInTarget([$phpBinary, 'artisan', 'view:clear'], 'Laravel view cache clear')) {
        return self::FAILURE;
    }

    if (! $this->option('skip-migrate')) {
        if (! $runInTarget([$phpBinary, 'artisan', 'migrate', '--force'], 'Central migration')) {
            return self::FAILURE;
        }

        if ($tenantSlugs->isEmpty()) {
            if (! $runInTarget([$phpBinary, 'artisan', 'tenants:migrate'], 'Tenant migrations for all active tenants')) {
                return self::FAILURE;
            }
        } else {
            foreach ($tenantSlugs as $tenantSlug) {
                if (! $runInTarget([$phpBinary, 'artisan', 'tenants:migrate', $tenantSlug], "Tenant migration for {$tenantSlug}")) {
                    return self::FAILURE;
                }
            }
        }
    }

    if (! $this->option('skip-build')) {
        if (! $runInTarget([$npmBinary, 'run', 'build'], 'Frontend build')) {
            return self::FAILURE;
        }
    }

    $this->newLine();
    $this->components->info('Live codebase updated successfully.');
    $this->line('Open: http://localhost:8000/ or your tenant localhost login URL.');

    return self::SUCCESS;
})->purpose('Sync this codebase into the Apache-served local BukSU Practicum folder');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('tenants:migrate {tenant?}', function (TenantDatabaseManager $databaseManager) {
    $identifier = $this->argument('tenant');

    $tenants = Tenant::query()
        ->when($identifier, function ($query) use ($identifier) {
            $query->where(function ($tenantQuery) use ($identifier) {
                $tenantQuery->whereKey($identifier)
                    ->orWhere('code', $identifier);
            });
        })
        ->where('is_active', true)
        ->get();

    if ($tenants->isEmpty()) {
        $this->error('No active tenants found for migration.');

        return self::FAILURE;
    }

    foreach ($tenants as $tenant) {
        $databaseManager->connect($tenant);

        $this->components->info("Migrating tenant [{$tenant->name}] using database [{$tenant->database}]");

        $this->call('migrate', [
            '--database' => config('tenancy.tenant_connection', 'tenant'),
            '--path' => 'database/migrations/tenant',
            '--realpath' => false,
            '--force' => true,
        ]);
    }

    return self::SUCCESS;
})->purpose('Run tenant migrations against each tenant database');

Artisan::command('tenants:seed {tenant?}', function (TenantDatabaseManager $databaseManager) {
    $identifier = $this->argument('tenant');

    $tenants = Tenant::query()
        ->when($identifier, function ($query) use ($identifier) {
            $query->where(function ($tenantQuery) use ($identifier) {
                $tenantQuery->whereKey($identifier)
                    ->orWhere('code', $identifier);
            });
        })
        ->where('is_active', true)
        ->get();

    if ($tenants->isEmpty()) {
        $this->error('No active tenants found for seeding.');

        return self::FAILURE;
    }

    foreach ($tenants as $tenant) {
        $databaseManager->connect($tenant);

        $this->components->info("Seeding tenant [{$tenant->name}]");

        $this->call('db:seed', [
            '--class' => TenantDatabaseSeeder::class,
            '--database' => config('tenancy.tenant_connection', 'tenant'),
            '--force' => true,
        ]);
    }

    return self::SUCCESS;
})->purpose('Seed tenant role accounts and starter data');

Artisan::command('docs:generate-project', function (ProjectDocumentationWriter $writer) {
    $path = $writer->write();

    $this->components->info("Project documentation generated at [{$path}]");

    return self::SUCCESS;
})->purpose('Generate the full project documentation markdown file');

Artisan::command('tenants:notify-subscriptions {--days=7}', function (TenantSubscriptionNotifier $notifier) {
    $days = max(1, (int) $this->option('days'));
    $warningCount = 0;
    $suspensionCount = 0;
    $errorCount = 0;

    foreach (Tenant::query()->orderBy('name')->get() as $tenant) {
        try {
            if (! $tenant->is_active) {
                if ($notifier->sendSuspensionNotice($tenant)) {
                    $suspensionCount++;
                }

                continue;
            }

            if (! $notifier->shouldWarnForExpiry($tenant, $days)) {
                continue;
            }

            if ($notifier->sendExpiryWarning($tenant, $notifier->daysRemaining($tenant))) {
                $warningCount++;
            }
        } catch (Throwable $exception) {
            $errorCount++;
            report($exception);
            $this->components->warn("Skipped [{$tenant->name}] because notification delivery failed.");
        }
    }

    $this->components->info("Subscription warnings sent: {$warningCount}");
    $this->components->info("Suspension notices sent: {$suspensionCount}");
    $this->components->info("Notification errors: {$errorCount}");

    return self::SUCCESS;
})->purpose('Send tenant suspension notices and upcoming subscription expiry reminders');

Schedule::command('tenants:notify-subscriptions --days=7')->dailyAt('08:00');
