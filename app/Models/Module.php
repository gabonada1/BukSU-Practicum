<?php

namespace App\Models;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory, UsesTenantConnection;

    protected $fillable = [
        'name',
        'plan_id',
    ];

    protected function casts(): array
    {
        return [
            'plan_id' => 'integer',
        ];
    }
}
