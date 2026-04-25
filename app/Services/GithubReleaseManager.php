<?php

namespace App\Services;

use App\Models\SystemRelease;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GithubReleaseManager
{
    public function repository(): string
    {
        $repository = trim((string) config('services.github.repository'));

        if ($repository === '') {
            throw new RuntimeException('The GitHub repository is not configured.');
        }

        return $this->normalizeRepository($repository);
    }

    public function nextVersion(): string
    {
        $versions = $this->tags()
            ->map(fn (array $tag) => $this->sanitizeVersion((string) ($tag['name'] ?? '')))
            ->filter()
            ->push($this->sanitizeVersion((string) config('app.version', '1.0.0')))
            ->filter()
            ->unique()
            ->values();

        if ($versions->isEmpty()) {
            return '1.0.1';
        }

        $latest = $versions->sort(fn (string $left, string $right) => version_compare($left, $right))->last();
        [$major, $minor, $patch] = array_pad(array_map('intval', explode('.', $latest)), 3, 0);

        return implode('.', [$major, $minor, $patch + 1]);
    }

    public function createAutomaticTag(?string $notes = null): array
    {
        $repository = $this->repository();
        $version = $this->nextVersion();
        $repositoryData = $this->repositoryData($repository);
        $defaultBranch = (string) ($repositoryData['default_branch'] ?? 'main');
        $branchData = $this->branchData($repository, $defaultBranch);
        $sha = (string) data_get($branchData, 'commit.sha', '');

        if ($sha === '') {
            throw new RuntimeException('GitHub did not return a commit SHA for the default branch.');
        }

        $response = $this->client()->post($this->repositoryApiPath($repository).'/git/refs', [
            'ref' => 'refs/tags/'.$version,
            'sha' => $sha,
        ]);

        if (! $response->successful()) {
            $message = (string) data_get($response->json(), 'message', 'GitHub could not create the new tag.');

            throw new RuntimeException($message);
        }

        return [
            'version' => $version,
            'github_tag' => $version,
            'github_sha' => $sha,
            'archive_url' => $this->archiveUrl($repository, $version),
            'notes' => $notes,
        ];
    }

    public function syncPublishedTags(): int
    {
        $repository = $this->repository();
        $synced = 0;

        foreach ($this->tags() as $tag) {
            $tagName = (string) ($tag['name'] ?? '');
            $version = $this->sanitizeVersion($tagName);

            if ($version === null) {
                continue;
            }

            $release = SystemRelease::query()
                ->where('github_tag', $tagName)
                ->orWhere('version', $version)
                ->first();

            $attributes = [
                'version' => $version,
                'github_tag' => $tagName,
                'github_sha' => (string) data_get($tag, 'commit.sha', ''),
                'archive_url' => $this->archiveUrl($repository, $tagName),
                'status' => 'published',
                'published_at' => $release?->published_at ?? now(),
            ];

            if ($release) {
                $release->forceFill($attributes)->save();
            } else {
                SystemRelease::query()->create($attributes);
            }

            $synced++;
        }

        return $synced;
    }

    public function archiveUrl(string $repository, string $tag): string
    {
        return rtrim((string) config('services.github.api_url', 'https://api.github.com'), '/')
            .'/repos/'.$this->normalizeRepository($repository).'/zipball/'.$tag;
    }

    public function tags(): Collection
    {
        $repository = $this->repository();
        $response = $this->client()->get($this->repositoryApiPath($repository).'/tags');

        if (! $response->successful()) {
            $message = (string) data_get($response->json(), 'message', 'GitHub tags could not be loaded.');

            throw new RuntimeException($message);
        }

        return collect($response->json());
    }

    protected function repositoryData(string $repository): array
    {
        $response = $this->client()->get($this->repositoryApiPath($repository));

        if (! $response->successful()) {
            $message = (string) data_get($response->json(), 'message', 'GitHub repository data could not be loaded.');

            throw new RuntimeException($message);
        }

        return $response->json();
    }

    protected function branchData(string $repository, string $branch): array
    {
        $response = $this->client()->get($this->repositoryApiPath($repository).'/branches/'.$branch);

        if (! $response->successful()) {
            $message = (string) data_get($response->json(), 'message', 'GitHub branch data could not be loaded.');

            throw new RuntimeException($message);
        }

        return $response->json();
    }

    protected function repositoryApiPath(string $repository): string
    {
        return '/repos/'.$this->normalizeRepository($repository);
    }

    protected function client(): PendingRequest
    {
        $client = Http::baseUrl(rtrim((string) config('services.github.api_url', 'https://api.github.com'), '/'))
            ->timeout(120)
            ->withHeaders([
                'Accept' => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => config('services.github.api_version', '2022-11-28'),
                'User-Agent' => config('app.name', 'Laravel'),
            ])
            ->withOptions($this->requestOptions());

        if (filled(config('services.github.token'))) {
            $client = $client->withToken((string) config('services.github.token'));
        }

        return $client;
    }

    protected function requestOptions(): array
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

    protected function normalizeRepository(string $repository): string
    {
        $trimmed = trim($repository);
        $trimmed = preg_replace('#^https?://github\.com/#i', '', $trimmed) ?: $trimmed;
        $trimmed = preg_replace('#\.git$#i', '', $trimmed) ?: $trimmed;

        return trim($trimmed, '/');
    }

    protected function sanitizeVersion(string $tag): ?string
    {
        $normalized = ltrim(trim($tag), 'vV');

        return preg_match('/^\d+\.\d+\.\d+$/', $normalized) ? $normalized : null;
    }
}
