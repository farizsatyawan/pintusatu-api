<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Midtrans Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi kredensial Payment Gateway Midtrans Snap untuk aplikasi Pintu Satu.
    | Mendukung mode Sandbox dan Production.
    |
    */

    'merchant_id' => env('MIDTRANS_MERCHANT_ID', ''),
    'client_key' => env('MIDTRANS_CLIENT_KEY', ''),
    'server_key' => env('MIDTRANS_SERVER_KEY', ''),
    'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
    'snap_url' => env('MIDTRANS_IS_PRODUCTION', false)
        ? 'https://app.midtrans.com/snap/v1/transactions'
        : 'https://app.sandbox.midtrans.com/snap/v1/transactions',
];
