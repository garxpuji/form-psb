<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS, Telegram, and MikroTik provisioning defaults.
    | Registering all custom env variables here ensures `php artisan config:cache`
    | works safely in production.
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

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN', ''),
        'timeout' => (int) env('TELEGRAM_API_TIMEOUT', 15),
    ],

    'mikrotik' => [
        'pppoe_default_password' => env('PPPOE_DEFAULT_PASSWORD', '123456'),
        'timeout' => (int) env('MIKROTIK_API_TIMEOUT', 10),
        'attempts' => (int) env('MIKROTIK_API_ATTEMPTS', 2),
    ],

];
