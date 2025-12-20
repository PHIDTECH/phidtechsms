<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\NumberParseException;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'date_of_birth',
        'user_id',
        'contact_group_id',
        'beem_contact_id',
        'tags',
        'custom_1',
        'custom_2',
        'custom_3',
        'custom_4',
        'custom_5',
        'custom_6',
        'custom_7',
        'custom_8',
        'custom_9',
        'custom_10',
        'is_opted_out',
        'opt_out_source',
        'opted_out_at',
        'is_active',
        'metadata'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_opted_out' => 'boolean',
        'opted_out_at' => 'datetime',
        'date_of_birth' => 'date',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the contact.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the contact group that the contact belongs to.
     */
    public function contactGroup()
    {
        return $this->belongsTo(ContactGroup::class);
    }

    /**
     * Scope a query to only include active contacts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Format the phone number for display.
     */
    public function getFormattedPhoneAttribute()
    {
        return $this->phone;
    }

    /**
     * Normalize phone number to E.164 format
     */
    public static function normalizePhoneNumber($phone, $defaultCountry = 'TZ')
    {
        $raw = is_string($phone) ? trim($phone) : (string) $phone;
        $digits = preg_replace('/[^0-9]/', '', $raw);
        if ($digits) {
            if (str_starts_with($digits, '255')) {
                $raw = '+' . $digits;
            } elseif (str_starts_with($digits, '0') && strlen($digits) >= 10) {
                $raw = '+255' . substr($digits, 1);
            }
        }

        try {
            $phoneUtil = PhoneNumberUtil::getInstance();
            $numberProto = $phoneUtil->parse($raw, $defaultCountry);
            if ($phoneUtil->isValidNumber($numberProto)) {
                return $phoneUtil->format($numberProto, PhoneNumberFormat::E164);
            }
        } catch (NumberParseException $e) {
        }
        return null;
    }

    /**
     * Validate contact data for import
     */
    public static function validateImportData($data)
    {
        $validator = Validator::make($data, [
            'name' => 'nullable|string|max:255',
            'phone' => 'required|string',
            'email' => 'nullable|email|max:255',
            'date_of_birth' => 'nullable|date|before:today',
        ]);

        $errors = [];
        
        if ($validator->fails()) {
            $errors = array_merge($errors, $validator->errors()->all());
        }

        // Validate and normalize phone number
        $normalizedPhone = self::normalizePhoneNumber($data['phone']);
        if (!$normalizedPhone) {
            $errors[] = 'Invalid phone number format';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'normalized_phone' => $normalizedPhone
        ];
    }

    /**
     * Check if contact already exists for user
     */
    public static function existsForUser($userId, $phone)
    {
        $normalizedPhone = self::normalizePhoneNumber($phone);
        if (!$normalizedPhone) {
            return false;
        }

        return self::where('user_id', $userId)
                   ->where('phone', $normalizedPhone)
                   ->exists();
    }
}
