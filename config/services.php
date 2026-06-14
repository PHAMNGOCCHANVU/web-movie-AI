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

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'base_url' => env(
            'GEMINI_BASE_URL',
            'https://generativelanguage.googleapis.com/v1beta'
        ),
        'model' => env('GEMINI_MODEL', 'gemini-3.5-flash'),
        'fallback_model' => env('GEMINI_FALLBACK_MODEL', 'gemini-2.5-flash'),
        'reserve_model' => env('GEMINI_RESERVE_MODEL', 'gemini-3.1-flash-lite'),
        'thinking_level' => env('GEMINI_THINKING_LEVEL', 'medium'),
        'google_search' => (bool) env('GEMINI_GOOGLE_SEARCH', true),
        'connect_timeout' => (int) env('GEMINI_CONNECT_TIMEOUT', 5),
        'primary_timeout' => (int) env('GEMINI_PRIMARY_TIMEOUT', 12),
        'fallback_timeout' => (int) env('GEMINI_FALLBACK_TIMEOUT', 15),
        'catalog_limit' => (int) env('GEMINI_CATALOG_LIMIT', 120),
        'chat_limit_per_hour' => (int) env('GEMINI_CHAT_LIMIT_PER_HOUR', 100),
    ],

    'moderation' => [
        'url' => env('MODERATION_URL', 'http://34.203.227.23:8765'),
        'model' => env(
            'MODERATION_MODEL',
            'visolex/phobert-v2-hsd'
        ),
        'connect_timeout' => (int) env('MODERATION_CONNECT_TIMEOUT', 2),
        'timeout' => (int) env('MODERATION_TIMEOUT', 30),
    ],

    'ophim' => [
        'base_url' => env('OPHIM_BASE_URL', 'https://ophim1.com'),
    ],

    'admin_seed' => [
        'name' => env('ADMIN_NAME', 'CineON Admin'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];
