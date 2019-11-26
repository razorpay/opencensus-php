<?php

return [
    'api_hosts'  => [
        'production' =>  env('APP_HOST'),
        'beta'       =>  env('BETA_APP_HOST'),
        'alpha'      =>  env('ALPHA_APP_HOST'),
    ],
    'api' => [
        'production' =>  env('APP_URL'),
        'beta'       =>  env('BETA_APP_URL'),
        'alpha'      =>  env('ALPHA_APP_URL'),
    ],
    'checkout' => [
        'production' =>  env('CHECKOUT_URL'),
        'beta'       =>  env('BETA_CHECKOUT_URL'),
        'canary'     =>  env('CANARY_CHECKOUT_URL'),
    ],

    'cdn' => [
        'production' => env('AWS_CF_CDN_URL'),
        'beta'       => env('BETA_AWS_CF_CDN_URL'),
        'testing'    => env('TEST_AWS_CF_CDN_URL'),
        'dev'        => env('TEST_AWS_CF_CDN_URL'),
    ],
];
