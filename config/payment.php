<?php

return [
    'vnpay' => [
        'url' => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
        'tmn_code' => env('VNPAY_TMN_CODE'),
        'hash_secret' => env('VNPAY_HASH_SECRET'),
    ],

    'momo' => [
        'environment' => env('MOMO_ENVIRONMENT', 'sandbox'),
        'partner_code' => env('MOMO_PARTNER_CODE'),
        'access_key' => env('MOMO_ACCESS_KEY'),
        'secret_key' => env('MOMO_SECRET_KEY'),
    ],

    'paypal' => [
        'environment' => env('PAYPAL_ENVIRONMENT', 'sandbox'),
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'secret' => env('PAYPAL_SECRET'),
    ],
];
