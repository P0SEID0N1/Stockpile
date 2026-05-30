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

    'market_data' => [
        'provider' => env('MARKET_DATA_PROVIDER', 'demo'),
    ],

    'alphavantage' => [
        'key' => env('ALPHAVANTAGE_API_KEY'),
        'base_url' => env('ALPHAVANTAGE_BASE_URL', 'https://www.alphavantage.co'),
    ],

    'tiingo' => [
        'token' => env('TIINGO_API_TOKEN'),
        'base_url' => env('TIINGO_BASE_URL', 'https://api.tiingo.com'),
    ],

];
