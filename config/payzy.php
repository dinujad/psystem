<?php

$testMode = filter_var(env('PAYZY_TEST_MODE', false), FILTER_VALIDATE_BOOLEAN);
// Live: api.payzy.lk | Sandbox: api.payzypay.xyz  (api.payzypay.lk does NOT resolve in DNS)
$defaultCheckout = $testMode
    ? 'https://api.payzypay.xyz/checkout/custom-checkout'
    : 'https://api.payzy.lk/checkout/custom-checkout';

return [
    'enabled' => env('PAYZY_ENABLED', true),
    'test_mode' => $testMode, // false = live (.lk) | true = sandbox (.xyz)
    'shop_id' => env('PAYZY_SHOP_ID', ''),
    'secret_key' => env('PAYZY_SECRET_KEY', ''),
    'checkout_url' => env('PAYZY_CHECKOUT_URL', $defaultCheckout),
    'bnpl_tax_percent' => (float) env('PAYZY_BNPL_TAX_PERCENT', 12),
    'installments' => (int) env('PAYZY_INSTALLMENTS', 4),
    'company_name' => env('PAYZY_COMPANY_NAME', 'PrintWorks'),
    'version' => '1.0',
    'platform' => 'custom',
];
