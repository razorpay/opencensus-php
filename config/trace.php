<?php

use RZP\Trace\MetricsHandler;

return [

    /*
    |--------------------------------------------------------------------------
    | Path for trace logs
    |--------------------------------------------------------------------------
    */
    'channel' => 'Razorpay API',

    'fallback_email' => 'developers@razorpay.com',

    'cloud' => ! env('APP_DEBUG', false),

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

    'log_max_files' => 3,

    'log_rotation_policy' => env('LOG_ROTATION_POLICY', 'daily'),

    'logpath' => storage_path() . '/logs/' . env('HOSTNAME', 'localhost') . '-trace.log',

    'trace_code_class' => RZP\Trace\TraceCode::class,

    'metrics' => require __DIR__ . '/metrics.php',

    'regex' => [
        'card_regex'            => env('CREDIT_CARD_REGEX_FOR_REDACTING'),
        'email_regex'           => env('EMAIL_REGEX_FOR_REDACTING'),
        'cvv_regex'             => env('CVV_REGEX_FOR_REDACTING'),
        'phone_number_regex'    => env('PHONE_NUMBER_REGEX_FOR_REDACTING'),
    ],

    'pii_fields' => [
        'aadhar_number'
]

];
