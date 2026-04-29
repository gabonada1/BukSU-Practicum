<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Tenant extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'name',
        'code',
        'plan',
        'subscription_starts_at',
        'subscription_expires_at',
        'database',
        'db_host',
        'db_port',
        'db_username',
        'db_password',
        'is_active',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
            'subscription_starts_at' => 'date',
            'subscription_expires_at' => 'date',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function getAuditLabel(): string
    {
        return $this->name;
    }

    public function domains(): HasMany
    {
        return $this->hasMany(TenantDomain::class)->orderByDesc('is_primary')->orderBy('host');
    }

    public function primaryDomain(): HasOne
    {
        $domain = new TenantDomain();

        return $this->hasOne(TenantDomain::class)
            ->where($domain->qualifyColumn('is_primary'), true)
            ->where($domain->qualifyColumn('is_active'), true);
    }

    public function primaryHost(): ?string
    {
        $domain = $this->relationLoaded('primaryDomain')
            ? $this->primaryDomain
            : $this->primaryDomain()->first();

        if ($domain?->host) {
            return $domain->host;
        }

        if ($this->relationLoaded('domains')) {
            return optional($this->domains->firstWhere('is_active', true))->host;
        }

        $domain = new TenantDomain();

        return optional(
            $this->domains()
                ->where($domain->qualifyColumn('is_active'), true)
                ->orderByDesc($domain->qualifyColumn('is_primary'))
                ->orderBy($domain->qualifyColumn('host'))
                ->first()
        )->host;
    }

    public function subscriptionHasStarted(): bool
    {
        return ! $this->subscription_starts_at instanceof Carbon
            || $this->subscription_starts_at->startOfDay()->lte(now()->startOfDay());
    }

    public function subscriptionIsExpired(): bool
    {
        return $this->subscription_expires_at instanceof Carbon
            && $this->subscription_expires_at->endOfDay()->isPast();
    }

    public function canAccessTenantApp(): bool
    {
        return $this->is_active
            && $this->subscriptionHasStarted()
            && ! $this->subscriptionIsExpired();
    }

    public function canCustomizeBranding(): bool
    {
        return in_array(strtolower((string) $this->plan), ['pro', 'premium'], true);
    }

    public function subscriptionStatus(): string
    {
        if (! $this->is_active) {
            return 'suspended';
        }

        if (! $this->subscriptionHasStarted()) {
            return 'scheduled';
        }

        if ($this->subscriptionIsExpired()) {
            return 'expired';
        }

        return 'active';
    }

    public function subscriptionBlockMessage(): string
    {
        return match ($this->subscriptionStatus()) {
            'suspended' => 'This university portal\'s access to the practicum platform has been suspended. Please contact University Practicum Administration.',
            'scheduled' => 'Access to this university portal has not been activated yet.',
            'expired' => 'This university portal\'s access has expired. Please contact University Administration for renewal.',
            default => 'This university portal is currently unavailable.',
        };
    }
}
