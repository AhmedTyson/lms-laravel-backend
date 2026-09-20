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

    'typesafe' => [
        'api_key' => env('TYPESAFE_API_KEY', 'apikey_22128b9f981ddf2d4f5eabdacf282d282945_fcbf7e361dd2f89d399a913e50bd8e05bc1508e85f7f4e858baeb164bc16d3fc'),
        'base_url' => env('TYPESAFE_BASE_URL', 'https://api.typesafe.ai/v1/systemone'),
        'model' => env('TYPESAFE_MODEL', 'jev-latest'),
    ],
];
