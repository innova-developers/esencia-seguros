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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'ssn' => [
        'environment' => env('SSN_ENVIRONMENT', 'testing'),
        'base_url_testing' => env('SSN_BASE_URL_TESTING', 'https://testri.ssn.gob.ar/api'),
        'base_url_production' => env('SSN_BASE_URL_PRODUCTION', 'https://ri.ssn.gob.ar/api'),
        'auth_endpoint' => env('SSN_AUTH_ENDPOINT', '/login'),
        'username' => env('SSN_USERNAME'),
        'cia' => env('SSN_CIA'),
        'password' => env('SSN_PASSWORD'),
        'mock_enabled' => env('SSN_MOCK_ENABLED', false),
        'mock_token' => env('SSN_MOCK_TOKEN'),
        'mock_expiration' => env('SSN_MOCK_EXPIRATION', '31/12/2025 23:59:59'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
