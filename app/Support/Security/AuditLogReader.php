<?php

namespace App\Support\Security;

use App\Models\Tenant;
use App\Models\TenantDomain;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class AuditLogReader
{
    public function all(int $limit = 60): Collection
    {
        return $this->entries()
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values();
    }

    public function forTenant(Tenant $tenant, int $limit = 60): Collection
    {
        $tenantId = (string) $tenant->getKey();

        return $this->entries()
            ->filter(fn (array $entry) => (string) ($entry['tenant_id'] ?? '') === $tenantId)
            ->reject(fn (array $entry) => ($entry['raw_actor_type'] ?? null) === 'central_superadmin')
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values();
    }

    protected function entries(): Collection
    {
        $entries = collect();
        $tenantNames = Tenant::query()->pluck('name', 'id')->mapWithKeys(
            fn (string $name, int|string $id) => [(string) $id => $name]
        );
        $tenantIdsByHost = TenantDomain::query()->pluck('tenant_id', 'host')->mapWithKeys(
            fn (int|string $tenantId, string $host) => [strtolower($host) => (string) $tenantId]
        );

        foreach (File::glob(storage_path('logs/laravel*.log')) ?: [] as $path) {
            $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

            foreach (array_slice($lines, -1200) as $line) {
                if (! preg_match('/^\[(?<timestamp>[^\]]+)\].*?tenant_audit\s+(?<payload>\{.*\})\s*$/', $line, $matches)) {
                    continue;
                }

                $payload = json_decode($matches['payload'], true);

                if (! is_array($payload)) {
                    continue;
                }

                $occurredAt = rescue(
                    fn () => Carbon::parse($matches['timestamp'])->timezone(config('app.timezone')),
                    fn () => now(),
                    report: false,
                );
                $tenantId = $this->resolveTenantId($payload, $tenantIdsByHost);
                $subjectType = $payload['subject_type'] ?? null;
                $subjectName = $payload['subject_label']
                    ?? ($subjectType ? class_basename($subjectType) : 'System record');

                $entries->push([
                    'timestamp' => $occurredAt->getTimestamp(),
                    'occurred_at' => $occurredAt->format('M d, Y h:i A'),
                    'tenant_id' => $tenantId,
                    'tenant' => $payload['tenant_name'] ?? ($tenantId ? $tenantNames->get((string) $tenantId, 'Tenant #'.$tenantId) : 'Central'),
                    'actor' => $payload['actor_name'] ?: str($payload['actor_type'] ?? 'system')->replace('_', ' ')->title()->toString(),
                    'raw_actor_type' => $payload['actor_type'] ?? 'system',
                    'actor_type' => str($payload['actor_type'] ?? 'system')->replace('_', ' ')->title()->toString(),
                    'action' => str($payload['action'] ?? 'activity')->replace('_', ' ')->title()->toString(),
                    'subject' => $subjectName,
                    'ip_address' => $payload['ip_address'] ?? 'N/A',
                    'url' => $payload['url'] ?? 'N/A',
                ]);
            }
        }

        return $entries;
    }

    protected function resolveTenantId(array $payload, Collection $tenantIdsByHost): ?string
    {
        $tenantId = $payload['tenant_id']
            ?? data_get($payload, 'new_values.tenant_id')
            ?? data_get($payload, 'old_values.tenant_id');

        if ($tenantId) {
            return (string) $tenantId;
        }

        if (($payload['subject_type'] ?? null) === Tenant::class && filled($payload['subject_id'] ?? null)) {
            return (string) $payload['subject_id'];
        }

        $host = parse_url((string) ($payload['url'] ?? ''), PHP_URL_HOST);

        return $host ? $tenantIdsByHost->get(strtolower($host)) : null;
    }
}
