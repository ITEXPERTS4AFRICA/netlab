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

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
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

    'cml' => [
        // Note: Les valeurs par défaut sont dans .env
        // La configuration réelle est chargée dynamiquement depuis la base de données
        // via BaseCiscoApiService::getCmlBaseUrl() et Setting::get()
        'base_url' => env('CML_API_BASE_URL'),
        'username' => env('CML_USERNAME'),
        'password' => env('CML_PASSWORD'),
    ],

    'cinetpay' => [
        'api_key' => env('CINETPAY_API_KEY'),
        'site_id' => env('CINETPAY_SITE_ID'),
        'api_url' => env('CINETPAY_API_URL', 'https://api.cinetpay.com'),
        'notify_url' => env('CINETPAY_NOTIFY_URL'),
        'return_url' => env('CINETPAY_RETURN_URL'),
        'cancel_url' => env('CINETPAY_CANCEL_URL'),
        'mode' => env('CINETPAY_MODE', 'sandbox'), // sandbox ou production
    ],

    'infobip'=>[
        'base_url' => env('INFOBIP_BASE_URL'),
        'api_key' => env('INFOBIP_API_KEY'),
        'whatsapp_sender' => env('INFOBIP_WHATSAPP_SENDER'),
    ]

];
