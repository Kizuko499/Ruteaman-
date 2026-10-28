<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'dapodik' => [
        'base_url' => env('DAPODIK_BASE_URL', 'https://dapo.kemendikdasmen.go.id'),
        'fallback_url' => env('SCHOOL_DIRECTORY_FALLBACK_URL', 'https://sekolah.devapi.id'),
        'region_url' => env('INDONESIA_REGION_API_URL', 'https://wilayah.id/api'),
        'token' => env('DAPODIK_API_TOKEN'),
        'timeout' => env('DAPODIK_TIMEOUT', 12),
        'verify_ssl' => env('DAPODIK_VERIFY_SSL', env('APP_ENV', 'production') !== 'local'),
    ],

    'turnstile' => ['site_key'=>env('TURNSTILE_SITE_KEY'),'secret'=>env('TURNSTILE_SECRET_KEY'),],
];