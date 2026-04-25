<?php

namespace App\Services;

use App\Models\SystemUpdate;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

class SystemUpdateService
{
    protected const SNAPSHOT_PATHS = [
        'app',
        'bootstrap',
        'config',
        'database',
        'public',
        'resources',
        'routes',
        'artisan',
        'composer.json',
        'composer.lock',
        'package.json',
        'package-lock.json',
        'vite.config.js',
    ];

    protected const EXCLUDED_RELEASE_PATHS = [
        '.env',
        '.git',
        'bootstrap/cache',
        'node_modules',
        'storage',
        'vendor',
    ];

    public function run(SystemUpdate $update): void
    {
        $this->prepareLongRunningExecution($update);

        $workingRoot = storage_path('app/system-updates/'.$update->getKey().'-'.Str::uuid());
        $downloadPath = $workingRoot.DIRECTORY_SEPARATOR.'release.zip';
        $extractPath = $workingRoot.DIRECTORY_SEPARATOR.'release';
        $backupPath = $workingRoot.DIRECTORY_SEPARATOR.'backup';
        $options = $this->resolveOptions($update->options ?? []);

        File::ensureDirectoryExists($workingRoot);

        $update->forceFill([
            'status' => 'running',
            'started_at' => now(),
            'finished_at' => null,
            'error_message' => null,
            'logs' => [],
        ])->save();

        try {
            if ($options['backup_current_release']) {
                $this->snapshotCurrentRelease($backupPath);
                $update->forceFill(['backup_path' => $backupPath])->save();
                $this->log($update, 'Current release snapshot created.');
            }

            $this->downloadReleaseArchive($update, $downloadPath);
            $sourcePath = $this->extractReleaseArchive($update, $downloadPath, $extractPath);
            $this->applyRelease($update, $sourcePath, base_path());

            if ($options['run_migrations']) {
                Artisan::call('migrate', ['--force' => true]);
                $this->log($update, 'Database migrations completed.');
            }

            if ($options['run_seeders']) {
                Artisan::call('db:seed', ['--force' => true]);
                $this->log($update, 'Database seeders completed.');
            }

            if ($options['run_npm_build']) {
                $this->runProcess($update, [$this->binary('npm'), 'run', 'build'], 'Frontend assets built.');
            }

            $this->cleanup($workingRoot);

            $update->forceFill([
                'status' => 'completed',
                'finished_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            $this->handleFailure($update, $workingRoot, $backupPath, $options, $exception);

            throw $exception;
        }
    }

    protected function prepareLongRunningExecution(SystemUpdate $update): void
    {
        if (function_exists('ignore_user_abort')) {
            ignore_user_abort(true);
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        @ini_set('max_execution_time', '0');

        $this->log($update, 'Extended PHP execution time for update run.');
    }

    protected function handleFailure(SystemUpdate $update, string $workingRoot, string $backupPath, array $options, Throwable $exception): void
    {
        $this->log($update, 'Update failed: '.$exception->getMessage());

        if ($options['backup_current_release'] && File::exists($backupPath)) {
            $this->restoreSnapshot($backupPath);
            $this->log($update, 'Project files restored from snapshot.');
        }

        $this->cleanup($workingRoot);

        $update->forceFill([
            'status' => 'failed',
            'finished_at' => now(),
            'error_message' => $exception->getMessage(),
        ])->save();
    }

    protected function resolveOptions(array $options): array
    {
        return [
            'backup_current_release' => (bool) Arr::get($options, 'backup_current_release', true),
            'run_npm_build' => (bool) Arr::get($options, 'run_npm_build', true),
            'run_migrations' => (bool) Arr::get($options, 'run_migrations', true),
            'run_seeders' => (bool) Arr::get($options, 'run_seeders', true),
        ];
    }

    protected function downloadReleaseArchive(SystemUpdate $update, string $downloadPath): void
    {
        $this->log($update, 'Downloading release archive.');

        $client = $this->isGitHubUrl($update->release_url)
            ? $this->githubClient()
            : Http::timeout(300);

        $response = $client
            ->withOptions(['sink' => $downloadPath])
            ->get($update->release_url);

        if (! $response->successful()) {
            throw new RuntimeException('Unable to download the release archive.');
        }

        $this->log($update, 'Release archive downloaded.');
    }

    protected function extractReleaseArchive(SystemUpdate $update, string $downloadPath, string $extractPath): string
    {
        File::ensureDirectoryExists($extractPath);

        $archive = new ZipArchive();

        if ($archive->open($downloadPath) !== true) {
            throw new RuntimeException('The downloaded release archive could not be opened.');
        }

        $archive->extractTo($extractPath);
        $archive->close();

        $directories = File::directories($extractPath);
        $sourcePath = count($directories) === 1 && empty(File::files($extractPath))
            ? $directories[0]
            : $extractPath;

        $this->log($update, 'Release archive extracted.');

        return $sourcePath;
    }

    protected function snapshotCurrentRelease(string $backupPath): void
    {
        foreach (self::SNAPSHOT_PATHS as $relativePath) {
            $sourcePath = base_path($relativePath);

            if (! File::exists($sourcePath)) {
                continue;
            }

            $targetPath = $backupPath.DIRECTORY_SEPARATOR.$relativePath;
            File::ensureDirectoryExists(dirname($targetPath));

            if (File::isDirectory($sourcePath)) {
                File::copyDirectory($sourcePath, $targetPath);
                continue;
            }

            File::copy($sourcePath, $targetPath);
        }
    }

    protected function restoreSnapshot(string $backupPath): void
    {
        foreach (self::SNAPSHOT_PATHS as $relativePath) {
            $sourcePath = $backupPath.DIRECTORY_SEPARATOR.$relativePath;
            $targetPath = base_path($relativePath);

            if (! File::exists($sourcePath)) {
                continue;
            }

            File::ensureDirectoryExists(dirname($targetPath));

            if (File::isDirectory($sourcePath)) {
                File::copyDirectory($sourcePath, $targetPath);
                continue;
            }

            File::copy($sourcePath, $targetPath);
        }
    }

    protected function applyRelease(SystemUpdate $update, string $sourceRoot, string $targetRoot): void
    {
        $this->log($update, 'Applying extracted release files.');

        foreach (scandir($sourceRoot) ?: [] as $entry) {
            if (in_array($entry, ['.', '..'], true)) {
                continue;
            }

            $this->copyReleasePath(
                $sourceRoot.DIRECTORY_SEPARATOR.$entry,
                $targetRoot.DIRECTORY_SEPARATOR.$entry,
                $entry
            );
        }

        $this->log($update, 'Release files copied into the application.');
    }

    protected function copyReleasePath(string $sourcePath, string $targetPath, string $relativePath): void
    {
        if ($this->shouldSkipRelativePath($relativePath)) {
            return;
        }

        if (is_dir($sourcePath)) {
            File::ensureDirectoryExists($targetPath);

            foreach (scandir($sourcePath) ?: [] as $entry) {
                if (in_array($entry, ['.', '..'], true)) {
                    continue;
                }

                $this->copyReleasePath(
                    $sourcePath.DIRECTORY_SEPARATOR.$entry,
                    $targetPath.DIRECTORY_SEPARATOR.$entry,
                    $relativePath.DIRECTORY_SEPARATOR.$entry
                );
            }

            return;
        }

        File::ensureDirectoryExists(dirname($targetPath));
        File::copy($sourcePath, $targetPath);
    }

    protected function shouldSkipRelativePath(string $relativePath): bool
    {
        $normalized = str_replace('\\', '/', ltrim($relativePath, '/\\'));

        foreach (self::EXCLUDED_RELEASE_PATHS as $excludedPath) {
            if ($normalized === $excludedPath || str_starts_with($normalized, $excludedPath.'/')) {
                return true;
            }
        }

        return false;
    }

    protected function runProcess(SystemUpdate $update, array $command, string $successMessage, array $environment = []): void
    {
        $this->log($update, 'Running command: '.implode(' ', $command));

        $process = Process::path(base_path())
            ->timeout(1800);

        if ($environment !== []) {
            $process = $process->env($environment);
        }

        $result = $process->run($command);

        if (! $result->successful()) {
            $message = trim($result->errorOutput() ?: $result->output());

            throw new RuntimeException($message !== '' ? $message : 'A system command failed during the update.');
        }

        $this->log($update, $successMessage);
    }

    protected function binary(string $tool): string
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return $tool;
        }

        return match ($tool) {
            'npm' => 'npm.cmd',
            default => $tool,
        };
    }

    protected function cleanup(string $path): void
    {
        if (File::exists($path)) {
            File::deleteDirectory($path);
        }
    }

    protected function githubClient()
    {
        $client = Http::timeout(300)
            ->withHeaders([
                'Accept' => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => config('services.github.api_version', '2022-11-28'),
                'User-Agent' => config('app.name', 'Laravel'),
            ])
            ->withOptions($this->githubRequestOptions());

        if (filled(config('services.github.token'))) {
            $client = $client->withToken((string) config('services.github.token'));
        }

        return $client;
    }

    protected function githubRequestOptions(): array
    {
        $caBundle = config('services.github.ca_bundle');

        if ($caBundle) {
            return ['verify' => $caBundle];
        }

        return [
            'verify' => $this->normalizeVerifySetting(config('services.github.verify_ssl', true)),
        ];
    }

    protected function normalizeVerifySetting(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true;
    }

    protected function isGitHubUrl(?string $url): bool
    {
        if (blank($url)) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return in_array($host, ['api.github.com', 'github.com'], true);
    }

    protected function log(SystemUpdate $update, string $message): void
    {
        $logs = $update->logs ?? [];
        $logs[] = '['.now()->format('Y-m-d H:i:s').'] '.$message;

        $update->forceFill(['logs' => $logs])->save();
    }
}
