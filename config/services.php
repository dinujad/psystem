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

    'whatsapp' => [
        'url' => env('WHATSAPP_SERVICE_URL', 'http://127.0.0.1:3000'),
        'api_key' => env('WHATSAPP_API_KEY'),
    ],

    'fardar' => [
        'client_id' => env('FARDAR_CLIENT_ID'),
        'api_key' => env('FARDAR_API_KEY'),
        'new_waybill_url' => env('FARDAR_NEW_WAYBILL_URL', 'https://www.fdedomestic.com/api/parcel/new_api_v1.php'),
        'existing_waybill_url' => env('FARDAR_EXISTING_WAYBILL_URL', 'https://www.fdedomestic.com/api/parcel/existing_waybill_api_v1.php'),
        // Packing slip pickup block (Attract)
        'pickup_name' => env('FARDAR_PICKUP_NAME', 'Attract wear & printing solutions'),
        'pickup_address' => env('FARDAR_PICKUP_ADDRESS', '387 7 Sama Mawatha Biyagama'),
        'pickup_phone' => env('FARDAR_PICKUP_PHONE', '706668885'),
    ],

    /*
    | Public customer Tracking Portal (WhatsApp live-track links).
    | Optional absolute base, e.g. https://track.printworks.lk
    */
    'tracking' => [
        'portal_url' => env('TRACKING_PORTAL_URL'), // empty = use APP_URL + /tracking-portal
    ],

];
