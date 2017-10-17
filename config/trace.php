<?php

return array(

    /*
    |--------------------------------------------------------------------------
    | Path for trace logs
    |--------------------------------------------------------------------------
    */
    'channel' => 'Razorpay API',

    'fallbackEmail' => 'developers@razorpay.com',

    'cloud' => env('CLOUD'),

    'sensitive_urls' => [
        'payments/create/jsonp',
        'v1/payments/create/jsonp',
        'v1/payments',
        'v1/payments/create',
        'v1/payments/create/recurring',
        'v1/payments/create/redirect',
        'v1/payments/create/checkout',
        'v1/payments/create/jsonp',
        'v1/payments/create/ajax',
        'v1/payments/create/fees',
        'v1/payments/create/wallet',
        'v1/payments/create/upi'
    ],

    'alerts' => [
        'email' => [
            'driver' => 'email',
            'from' => 'errors@razorpay.com',
            'to' => 'developers@razorpay.com',
        ]
    ],

    'rotate'  => true,

    'log_max_files' => 5,

    'trace_code_class' => RZP\Trace\TraceCode::class,
);
