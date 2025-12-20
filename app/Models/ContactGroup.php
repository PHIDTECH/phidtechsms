<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'user_id',
        'color',
        'beem_address_book_id',
        'is_default',
        'is_active'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the contact group.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the contacts for the contact group.
     */
    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Get the count of contacts in this group.
     */
    public function getContactsCountAttribute()
    {
        return $this->contacts()->count();
    }

    /**
     * Scope a query to only include active groups.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to exclude default groups.
     */
    public function scopeNonDefault($query)
    {
        return $query->where('is_default', false);
    }

    /**
     * Get the default contact group for a user.
     */
    public static function getDefaultForUser($userId)
    {
        return self::where('user_id', $userId)
                   ->where('is_default', true)
                   ->first();
    }

    /**
     * Create default contact group for user if it doesn't exist.
     */
    public static function createDefaultForUser($userId)
    {
        return self::firstOrCreate(
            ['user_id' => $userId, 'is_default' => true],
            [
                'name' => 'Default',
                'description' => 'Default contact group',
                'color' => '#3B82F6',
                'is_active' => true
            ]
        );
    }
}