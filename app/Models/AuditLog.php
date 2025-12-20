<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'description',
        'ip_address',
        'user_agent',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user that performed this action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a new audit log entry
     */
    public static function logAction(
        int $userId,
        string $action,
        string $description,
        array $metadata = [],
        string $ipAddress = null,
        string $userAgent = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
        ]);
    }

    /**
     * Log authentication action
     */
    public static function logAuth(int $userId, string $action, array $metadata = []): self
    {
        return self::logAction($userId, $action, "User {$action}", $metadata);
    }

    /**
     * Log campaign action
     */
    public static function logCampaign(int $userId, string $action, int $campaignId, array $metadata = []): self
    {
        return self::logAction(
            $userId,
            $action,
            "Campaign {$action} (ID: {$campaignId})",
            array_merge(['campaign_id' => $campaignId], $metadata)
        );
    }

    /**
     * Log wallet action
     */
    public static function logWallet(int $userId, string $action, float $amount, array $metadata = []): self
    {
        return self::logAction(
            $userId,
            $action,
            "Wallet {$action}: TZS " . number_format($amount, 2),
            array_merge(['amount' => $amount], $metadata)
        );
    }

    /**
     * Log SMS action
     */
    public static function logSms(int $userId, string $action, int $messageCount, array $metadata = []): self
    {
        return self::logAction(
            $userId,
            $action,
            "SMS {$action}: {$messageCount} message(s)",
            array_merge(['message_count' => $messageCount], $metadata)
        );
    }
}
