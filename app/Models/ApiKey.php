<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'key',
        'secret_hash',
        'permissions',
        'rate_limit_per_min',
        'ip_allowlist',
        'active',
        'last_used_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'active' => 'boolean',
        'last_used_at' => 'datetime',
        'ip_allowlist' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
