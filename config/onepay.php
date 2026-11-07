<?php

return [
    'enabled' => env('ONEPAY_ENABLED', true),
    'base_url' => env('ONEPAY_BASE_URL', 'https://api.onepay.lk'),
    'app_name' => env('ONEPAY_APP_NAME', 'Printworks web'),
    'app_id' => env('ONEPAY_APP_ID', ''),
    'app_token' => env('ONEPAY_APP_TOKEN', ''),
    'hash_salt' => env('ONEPAY_HASH_SALT', ''),
    'callback_token' => env('ONEPAY_CALLBACK_TOKEN', ''),
    'currency' => env('ONEPAY_CURRENCY', 'LKR'),
    'redirect_path' => env('ONEPAY_REDIRECT_PATH', '/pay/{token}/onepay/return'),
];
