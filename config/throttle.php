<?php
/*
 * This file is part of Laravel Throttle.
 *
 * (c) Graham Campbell <graham@alt-three.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Cache Driver
    |--------------------------------------------------------------------------
    |
    | This defines the cache driver to be used. It may be the name of any
    | driver set in config/cache.php. Setting it to null will use the driver
    | you have set as default in config/cache.php.
    |
    | Default: null
    |
    */
    'driver' => null,

    'skip' => env('SKIP_THROTTLE', false),

    // Time interval for key expiry in redis in minutes
    'time_interval' => 2,

    'limits' => [
        'live' => [
            'default'                             => 100,
            RZP\Http\BasicAuth\Type::ADMIN_AUTH   => 1000,
            RZP\Http\BasicAuth\Type::DIRECT_AUTH  => 1000,
            // TODO: Change it to 150 after testing
            RZP\Http\BasicAuth\Type::PRIVATE_AUTH => 50,
            RZP\Http\BasicAuth\Type::DEVICE_AUTH  => 100,

            // Highest we have seen is 50
            // Making it 4x as this involves callback as well.
            RZP\Http\BasicAuth\Type::PUBLIC_AUTH  => 500,

            // Shared between all users of a merchant
            RZP\Http\BasicAuth\Type::PROXY_AUTH   => 200,
        ],

        'test' => [
            'default'                             => 20,
            RZP\Http\BasicAuth\Type::ADMIN_AUTH   => 100,
            RZP\Http\BasicAuth\Type::DIRECT_AUTH  => 100,
            // TODO: Change it to 150 after testing
            RZP\Http\BasicAuth\Type::PRIVATE_AUTH => 50,
            RZP\Http\BasicAuth\Type::DEVICE_AUTH  => 100,
            RZP\Http\BasicAuth\Type::PUBLIC_AUTH  => 100,
            RZP\Http\BasicAuth\Type::PROXY_AUTH   => 500,
        ],
    ]
];
