<?php

//Authenticating guards
'guards' => [
    'user' =>[
        'driver' => 'session',
        'provider' => 'users',
    ],
    'admin' => [
        'driver' => 'session',
        'provider' => 'admins',
    ],
    'merchant' => [
        'driver' => 'session',
        'provider' => 'merchants',
    ],
],

//User Providers
'providers' => [
    'user' => [
        'driver' => 'eloquent',
        'model' => 'App\User\Entity',
    ],
    'admin' => [
        'driver' => 'eloquent',
        'model' => 'App\Admin\Entity',
    ],
    'merchant' => [
        'driver' => 'eloquent',
        'model' => 'App\Merchant\Entity',
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
        'email' => 'emails.auth.reminder',
        'table' => 'password_reminders',
        'expire' => 1440,
    ],
    'merchants' => [
        'provider' => 'merchant',
        'email' => 'emails.auth.reminder',
        'table' => 'password_reminders',
        'expire' => 1440,
    ],
],