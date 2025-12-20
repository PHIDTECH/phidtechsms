<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'sms_credits',
        'phone_verified',
        'phone_verified_at',
        'otp_code',
        'otp_expires_at',
        'two_factor_enabled',
        'two_factor_method',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'phone_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'last_login_at' => 'datetime',
            'phone_verified' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'is_active' => 'boolean',
            'sms_credits' => 'integer',
        ];
    }

    // Relationships
    public function senderIds()
    {
        return $this->hasMany(SenderID::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function smsMessages()
    {
        return $this->hasMany(SmsMessage::class);
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function smsTemplates()
    {
        return $this->hasMany(SmsTemplate::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Helper methods
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isReseller()
    {
        return $this->role === 'reseller';
    }

    public function hasVerifiedPhone()
    {
        return $this->phone_verified;
    }

    public function canSendSms()
    {
        return $this->is_active && $this->sms_credits > 0;
    }

    public function hasSufficientCredits($requiredCredits)
    {
        return $this->sms_credits >= $requiredCredits;
    }

    public function deductCredits($credits)
    {
        if ($this->sms_credits >= $credits) {
            $this->sms_credits -= $credits;
            $this->save();
            return true;
        }
        return false;
    }

    public function addCredits($credits)
    {
        $this->sms_credits += $credits;
        $this->save();
        return $this->sms_credits;
    }

    // Contact Permission Methods
    public function canViewContacts()
    {
        return $this->is_active && ($this->isAdmin() || $this->isReseller());
    }

    public function canManageContacts()
    {
        return $this->is_active && ($this->isAdmin() || $this->isReseller());
    }

    public function canImportContacts()
    {
        return $this->is_active && ($this->isAdmin() || $this->isReseller());
    }

    public function canExportContacts()
    {
        return $this->is_active && ($this->isAdmin() || $this->isReseller());
    }

    public function canManageContactGroups()
    {
        return $this->is_active && ($this->isAdmin() || $this->isReseller());
    }

    public function canViewAllContacts()
    {
        return $this->isAdmin();
    }

    public function canManageUserContacts(User $targetUser)
    {
        return $this->isAdmin() || $this->id === $targetUser->id;
    }

    public function hasApiAccess()
    {
        return $this->is_active;
    }
}
