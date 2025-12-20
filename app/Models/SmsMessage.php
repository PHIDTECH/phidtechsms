<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsMessage extends Model
{
    protected $fillable = [
        'campaign_id',
        'user_id',
        'phone',
        'message_content',
        'sender_id',
        'parts_count',
        'cost',
        'status',
        'beem_message_id',
        'failure_reason',
        'sent_at',
        'delivered_at',
        'failed_at',
        'dlr_payload'
    ];

    protected $casts = [
        'parts_count' => 'integer',
        'cost' => 'decimal:2',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'dlr_payload' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($smsMessage) {
            // Normalize status to allowed enum values: queued, sent, delivered, failed, expired
            $allowedStatuses = ['queued', 'sent', 'delivered', 'failed', 'expired'];
            if (!in_array($smsMessage->status, $allowedStatuses, true)) {
                if ($smsMessage->status === 'pending') {
                    $smsMessage->status = 'queued';
                }
            }
        });
    }

    /**
     * Get the campaign this message belongs to
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the user that owns this message
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if message is queued (pending)
     */
    public function isQueued(): bool
    {
        return $this->status === 'queued';
    }

    /**
     * Check if message is sent
     */
    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    /**
     * Check if message is delivered
     */
    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    /**
     * Check if message failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Mark message as sent
     */
    public function markAsSent(string $beemMessageId = null): void
    {
        $this->update([
            'status' => 'sent',
            'beem_message_id' => $beemMessageId,
            'sent_at' => now()
        ]);
    }

    /**
     * Mark message as delivered
     */
    public function markAsDelivered(array $dlrPayload = null): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
            'dlr_payload' => $dlrPayload
        ]);
    }

    /**
     * Mark message as failed
     */
    public function markAsFailed(string $reason, array $dlrPayload = null): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
            'failed_at' => now(),
            'dlr_payload' => $dlrPayload
        ]);
    }
}
