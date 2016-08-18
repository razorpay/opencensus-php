<?php

return [
    'defaults' => [
        'guard' => 'user',
        'passwords' => 'user',
    ],

    //Authenticating guards
    'guards' => [
        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],
        'user' =>[
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    //User Providers
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\User\Entity::class,
        ],
        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Admin\Entity::class,
        ]
    ],

    //Resetting Password
    'passwords' => [
        'user' => [
            'provider' => 'users',
            'email' => 'emails.auth.reminder',
            'table' => 'password_reminders',
            'expire' => 1440,
        ],
        'admins' => [
            'provider' => 'admins',
            'expire' => 1440,
        ]
    ],
];
