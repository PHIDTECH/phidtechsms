<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'mode',
        'sender_id',
        'message',
        'schedule_at',
        'audience_config',
        'status',
        'estimated_recipients',
        'estimated_parts',
        'hold_parts',
        'estimated_cost',
        'sent_count',
        'delivered_count',
        'failed_count',
        'started_at',
        'completed_at',
        'failure_reason'
    ];

    protected $casts = [
        'schedule_at' => 'datetime',
        'audience_config' => 'array',
        'estimated_recipients' => 'integer',
        'estimated_parts' => 'integer',
        'hold_parts' => 'integer',
        'estimated_cost' => 'decimal:2',
        'sent_count' => 'integer',
        'delivered_count' => 'integer',
        'failed_count' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($campaign) {
            if (!is_null($campaign->sender_id) && !is_numeric($campaign->sender_id)) {
                $sid = \App\Models\SenderID::where('sender_id', $campaign->sender_id)->first();
                if ($sid) {
                    $campaign->sender_id = $sid->id;
                }
            }

            $allowedModes = ['file','group','quick','standard'];
            if (!in_array($campaign->mode, $allowedModes, true)) {
                $campaign->mode = 'quick';
            }

            // Convert statuses that may not exist in database enum
            if ($campaign->status === 'pending') {
                $campaign->status = 'queued';
            } elseif ($campaign->status === 'sending') {
                $campaign->status = 'processing';
            }
        });
    }

    /**
     * Get the user that owns the campaign
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get SMS messages for this campaign
     */
    public function smsMessages(): HasMany
    {
        return $this->hasMany(SmsMessage::class);
    }

    /**
     * Get wallet transactions for this campaign
     */
    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Check if campaign is draft
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if campaign is scheduled
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    /**
     * Check if campaign is currently sending
     */
    public function isSending(): bool
    {
        return $this->status === 'sending';
    }

    /**
     * Check if campaign is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if campaign failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Calculate delivery rate percentage
     */
    public function getDeliveryRateAttribute(): float
    {
        if ($this->sent_count == 0) {
            return 0;
        }
        
        return ($this->delivered_count / $this->sent_count) * 100;
    }

    /**
     * Calculate failure rate percentage
     */
    public function getFailureRateAttribute(): float
    {
        if ($this->sent_count == 0) {
            return 0;
        }
        
        return ($this->failed_count / $this->sent_count) * 100;
    }

    /**
     * Get progress percentage
     */
    public function getProgressAttribute(): float
    {
        if ($this->estimated_recipients == 0) {
            return 0;
        }
        
        return ($this->sent_count / $this->estimated_recipients) * 100;
    }
}
