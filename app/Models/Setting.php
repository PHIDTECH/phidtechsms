<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'is_encrypted',
        'admin_sms_balance',
        'balance_last_synced'
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
        'value' => 'string',
        'admin_sms_balance' => 'integer',
        'balance_last_synced' => 'datetime'
    ];

    /**
     * Get a setting value by key
     */
    public static function get($key, $default = null)
    {
        $cacheKey = 'setting_' . $key;
        
        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            
            if (!$setting) {
                return $default;
            }
            
            $value = $setting->value;
            
            // Decrypt if encrypted
            if ($setting->is_encrypted && $value) {
                try {
                    $value = decrypt($value);
                } catch (\Exception $e) {
                    \Log::error('Failed to decrypt setting: ' . $key);
                    return $default;
                }
            }
            
            // Cast to appropriate type
            switch ($setting->type) {
                case 'boolean':
                    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
                case 'integer':
                    return (int) $value;
                case 'float':
                    return (float) $value;
                case 'array':
                case 'json':
                    return json_decode($value, true);
                default:
                    return $value;
            }
        });
    }

    /**
     * Set a setting value
     */
    public static function set($key, $value, $type = 'string', $description = null, $encrypt = false)
    {
        // Convert value to string for storage
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
            $type = 'json';
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
            $type = 'boolean';
        } else {
            $value = (string) $value;
        }

        // Encrypt if needed
        if ($encrypt && $value) {
            $value = encrypt($value);
        }

        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'description' => $description,
                'is_encrypted' => $encrypt
            ]
        );

        // Clear cache
        Cache::forget('setting_' . $key);

        return $setting;
    }

    /**
     * Delete a setting
     */
    public static function remove($key)
    {
        $deleted = self::where('key', $key)->delete();
        Cache::forget('setting_' . $key);
        return $deleted;
    }

    /**
     * Get all settings as key-value pairs
     */
    public static function getAllSettings()
    {
        $settings = self::get();
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting->key] = self::get($setting->key);
        }

        return $result;
    }

    /**
     * Get Beem SMS settings
     */
    public static function getBeemSettings()
    {
        return [
            'api_key' => self::get('beem_api_key'),
            'secret_key' => self::get('beem_secret_key'),
            // Align default with official docs (no /public)
            'base_url' => self::get('beem_base_url', 'https://apisms.beem.africa/v1'),
            'default_sender_id' => self::get('beem_default_sender_id', 'Phidtech SMS'),
        ];
    }

    /**
     * Set Beem SMS settings
     */
    public static function setBeemSettings($apiKey, $secretKey, $baseUrl = null, $defaultSenderId = null)
    {
        self::set('beem_api_key', $apiKey, 'string', 'Beem SMS API Key', true);
        self::set('beem_secret_key', $secretKey, 'string', 'Beem SMS Secret Key', true);
        
        if ($baseUrl) {
            self::set('beem_base_url', $baseUrl, 'string', 'Beem SMS Base URL');
        }
        
        if ($defaultSenderId) {
            self::set('beem_default_sender_id', $defaultSenderId, 'string', 'Default Sender ID');
        }
    }

    /**
     * Get Selcom payment settings
     */
    public static function getSelcomSettings()
    {
        return [
            'vendor_id' => self::get('selcom_vendor_id'),
            'api_key' => self::get('selcom_api_key'),
            'secret_key' => self::get('selcom_secret_key'),
            'base_url' => self::get('selcom_base_url', 'https://apigw.selcommobile.com'),
        ];
    }

    /**
     * Set Selcom payment settings
     */
    public static function setSelcomSettings($vendorId, $apiKey, $secretKey, $baseUrl = null)
    {
        self::set('selcom_vendor_id', $vendorId, 'string', 'Selcom Vendor ID');
        self::set('selcom_api_key', $apiKey, 'string', 'Selcom API Key', true);
        self::set('selcom_secret_key', $secretKey, 'string', 'Selcom Secret Key', true);
        
        if ($baseUrl) {
            self::set('selcom_base_url', $baseUrl, 'string', 'Selcom Base URL');
        }
    }
}
