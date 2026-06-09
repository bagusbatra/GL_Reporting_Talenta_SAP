<?php

return [
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

    'python' => [
        'semarang_url' => env('PYTHON_SEMARANG_URL', 'http://127.0.0.1:8091'),
        'surabaya_url' => env('PYTHON_SURABAYA_URL', 'http://127.0.0.1:8092'),
        'timeout' => env('PYTHON_TIMEOUT', 120),
    ],
    'talenta' => [
    'client_id' => env('TALENTA_CLIENT_ID'),
    'client_secret' => env('TALENTA_CLIENT_SECRET'),
    'user_id' => env('TALENTA_USER_ID'),
    ],
];