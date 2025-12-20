<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use App\Services\SmsService;

class SenderID extends Model
{
    use HasFactory;

    protected $table = 'sender_ids';

    protected $fillable = [
        'user_id',
        'sender_id',
        'sender_name',
        'business_name',
        'business_type',
        'business_registration',
        'business_address',
        'contact_person',
        'contact_phone',
        'use_case',
        'sample_messages',
        'target_countries',
        'business_license_path',
        'id_document_path',
        'attachment_path',
        'additional_documents_paths',
        'status',
        'reference_number',
        'application_date',
        'approved_at',
        'rejected_at',
        'rejection_reason',
        'admin_notes',
        'reviewed_by',
        'beem_sender_id',
    ];

    protected $casts = [
        'application_date' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'target_countries' => 'array',
        'additional_documents_paths' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($senderID) {
            if (empty($senderID->reference_number)) {
                $senderID->reference_number = 'SID-' . strtoupper(Str::random(8));
            }
            if (empty($senderID->application_date)) {
                $senderID->application_date = now();
            }
        });
    }

    /**
     * Get the user that owns the sender ID.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who reviewed the application.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get campaigns that use this sender ID
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'sender_id', 'sender_name');
    }

    /**
     * Get SMS messages sent with this sender ID
     */
    public function smsMessages(): HasMany
    {
        return $this->hasMany(SmsMessage::class, 'sender_id', 'sender_name');
    }

    /**
     * Scope a query to only include pending applications.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved applications.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include rejected applications.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Get the status badge CSS class.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get the human-readable status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Under Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => 'Unknown',
        };
    }

    /**
     * Check if sender ID is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if sender ID is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if sender ID is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Approve the sender ID application.
     */
    public function approve($reviewerId = null, $notes = null): bool
    {
        $updated = $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'rejected_at' => null,
            'rejection_reason' => null,
            'reviewed_by' => $reviewerId,
            'admin_notes' => $notes,
        ]);
        if ($updated) {
            $user = $this->user;
            if ($user) {
                $recipient = $user->phone ?? $user->phone_number ?? null;
                if ($recipient) {
                    try {
                        $service = new SmsService();
                        if ($service->canSend()) {
                            $message = "Your sender ID '" . ($this->sender_name ?? $this->sender_id) . "' is now active. You can start sending campaigns.";
                            $service->sendSms($recipient, $message, $this->sender_name ?? null);
                        }
                    } catch (\Throwable $e) {}
                }
                Cache::put('senderid:approved:' . $user->id, [
                    'sender' => $this->sender_name ?? $this->sender_id,
                    'at' => now()->toDateTimeString(),
                ], now()->addDay());
            }
        }
        return $updated;
    }

    /**
     * Reject the sender ID application.
     */
    public function reject($reason, $reviewerId = null, $notes = null): bool
    {
        return $this->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'approved_at' => null,
            'rejection_reason' => $reason,
            'reviewed_by' => $reviewerId,
            'admin_notes' => $notes,
        ]);
    }

    /**
     * Get all business types.
     */
    public static function getBusinessTypes(): array
    {
        return [
            'retail' => 'Retail',
            'technology' => 'Technology',
            'finance' => 'Finance',
            'healthcare' => 'Healthcare',
            'education' => 'Education',
            'hospitality' => 'Hospitality',
            'logistics' => 'Logistics',
            'manufacturing' => 'Manufacturing',
            'nonprofit' => 'Non-profit',
            'other' => 'Other',
        ];
    }

    /**
     * Search sender IDs by various criteria.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('sender_name', 'like', "%{$search}%")
              ->orWhere('business_name', 'like', "%{$search}%")
              ->orWhere('contact_person', 'like', "%{$search}%")
              ->orWhere('reference_number', 'like', "%{$search}%")
              ->orWhereHas('user', function ($userQuery) use ($search) {
                  $userQuery->where('name', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%")
                           ->orWhere('phone', 'like', "%{$search}%");
              });
        });
    }
}
