<?php

return [
    'base_url'          => env('MES_BASE_URL'),
    'request_timeout'   => env('MES_REQUEST_TIME_OUT', 600), // 10 minutes
    'dashboard'         => [
        'user'  =>  'rzp_dashboard',
        'pass'  =>  env('MES_DASHBOARD_PASS'),
    ],
    'merchant_dashboard'    => [
        'user'  =>  'rzp_merchant_dashboard',
        'pass'  =>  env('MES_MERCHANT_DASHBOARD_PASS'),
    ],
    'admin_dashboard'   => [
        'user'  =>  'rzp_admin_dashboard',
        'pass'  =>  env('MES_ADMIN_DASHBOARD_PASS'),
    ],
];
