<?php

return [
    'defaults' => [
        'guard'     => 'user',
        'passwords' => 'user',
    ],

    //Authenticating guards
    'guards' => [
        'user' =>[
            'driver'    => 'session',
            'provider'  => 'api_user',
        ],
        'api' => [
            'driver'      => 'api',
            'provider'    => 'api',
            'session_key' => 'api_admin',
        ]
    ],

    //User Providers
    'providers' => [
        'api' => [
            'driver' => 'api',
            'model'  => Illuminate\Auth\GenericUser::class
        ],
        'api_user' => [
            'driver' => 'api_user',
            'model'  => Illuminate\Auth\GenericUser::class
        ]
    ],

    'service_provider' => [
        'thirdwatch' => [
            'signing_secret' => env('THIRDWATCH_JWT_SIGNING_KEY'),
            'redirect_url'   => env('THIRDWATCH_REDIRECT_URL'),
        ],
        'opfin'      => [
            'signing_secret' => env('OPFIN_JWT_SIGNING_KEY'),
            'redirect_url'   => env('OPFIN_REDIRECT_URL'),
        ],
        'billme'      => [
            'signing_secret' => env('BILLME_JWT_SIGNING_KEY'),
            'redirect_url'   => env('BILLME_REDIRECT_URL'),
        ],
    ],
];
