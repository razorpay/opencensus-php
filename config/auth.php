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
];
