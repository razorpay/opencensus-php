<?php

return [
    'defaults' => [
        'guard' => 'user',
        'passwords' => 'users',
    ],

    //Authenticating guards
    'guards' => [
        'user' =>[
            'driver' => 'session',
            'provider' => 'users',
        ],
        'admin' => [
            'driver' => 'session',
            'provider' => 'admin',
        ]
    ],

    //User Providers
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => 'App\User\Entity',
        ],
        'admin' => [
            'driver' => 'eloquent',
            'model' => 'App\Admin\Entity',
        ]
    ],

    //Resetting Password
    'passwords' => [
        'users' => [
            'provider' => 'user',
            'email' => 'emails.auth.reminder',
            'table' => 'password_reminders',
            'expire' => 1440,
        ],
        'admins' => [
            'provider' => 'admin',
            'expire' => 1440,
        ]
    ],
];
