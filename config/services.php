<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'beem' => [
        'api_key' => env('BEEM_API_KEY'),
        'secret_key' => env('BEEM_SECRET_KEY'),
        'base_url' => env('BEEM_BASE_URL', 'https://apisms.beem.africa/v1'),
        'default_sender_id' => env('BEEM_DEFAULT_SENDER_ID', 'Phidtech'),
    ],

    'beem_contacts' => [
        'base' => env('BEEM_CONTACTS_BASE_URL', 'https://apicontacts.beem.africa/v1'),
        'key' => env('BEEM_API_KEY'),
        'secret' => env('BEEM_SECRET_KEY'),
    ],

    'beem_otp' => [
        'api_key' => env('BEEM_OTP_API_KEY'),
        'secret_key' => env('BEEM_OTP_SECRET_KEY'),
        'base_url' => env('BEEM_OTP_BASE_URL', 'https://apiotp.beem.africa/v1'),
        'app_id' => env('BEEM_OTP_APP_ID'),
        'sender_id' => env('BEEM_OTP_SENDER_ID', 'Phidtech SMS'),
    ],

    'sms' => [
        'cost_per_part' => env('SMS_COST_PER_PART', 30), // TZS per SMS part
    ],

];
