<?php

return [
    'enabled' => env('PAYZY_ENABLED', true),
    'test_mode' => env('PAYZY_TEST_MODE', false), // false = production (x_test_mode off)
    'shop_id' => env('PAYZY_SHOP_ID', ''),
    'secret_key' => env('PAYZY_SECRET_KEY', ''),
    'checkout_url' => env('PAYZY_CHECKOUT_URL', 'https://api.payzypay.xyz/checkout/custom-checkout'),
    'bnpl_tax_percent' => (float) env('PAYZY_BNPL_TAX_PERCENT', 12),
    'installments' => (int) env('PAYZY_INSTALLMENTS', 4),
    'company_name' => env('PAYZY_COMPANY_NAME', 'PrintWorks'),
    'version' => '1.0',
    'platform' => 'custom',
];
