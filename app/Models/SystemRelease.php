<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemRelease extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'version',
        'github_tag',
        'github_sha',
        'archive_url',
        'notes',
        'status',
        'created_by',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public static function latestPublished(): ?self
    {
        return static::query()
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(CentralSuperadmin::class, 'created_by');
    }

    public function releasePreferenceSettings(): array
    {
        return [
            'preferred_release_id' => $this->getKey(),
            'preferred_release_version' => $this->version,
            'preferred_release_tag' => $this->github_tag,
        ];
    }
}
