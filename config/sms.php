<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default SMS driver
    |--------------------------------------------------------------------------
    */
    'driver' => env('SMS_DRIVER', 'textlk'),

    /*
    |--------------------------------------------------------------------------
    | PrintWorks TextLK gateway
    |--------------------------------------------------------------------------
    */
    'printworks' => [
        'api_key' => env('TEXTLK_API_KEY'),
        'sender_id' => env('TEXTLK_SENDER_ID', 'PrintWorks'),
        'url' => env('TEXTLK_URL', 'https://app.text.lk/api/v3/sms/send'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Safety Sign TextLK gateway
    |--------------------------------------------------------------------------
    */
    'safetysign' => [
        'api_key' => env('SAFETYSIGN_TEXTLK_API_KEY'),
        'sender_id' => env('SAFETYSIGN_TEXTLK_SENDER_ID', 'Safety sign'),
        'url' => env('SAFETYSIGN_TEXTLK_URL', env('TEXTLK_URL', 'https://app.text.lk/api/v3/sms/send')),
    ],
];
