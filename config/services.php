<?php

use RZP\Services\Geolocation\Service as GeoLocation;

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, Mandrill, and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN', 'razorpay.com'),
        'secret' => env('MAILGUN_SECRET'),
    ],

    'mandrill' => [
        'secret' => env('MANDRILL_SECRET'),
    ],

    //
    // AWS_KEY_ID, AWS_KEY_SECRET are blank,
    // they are getting filled by IAM roles in production
    //
    'ses' => [
        'key'    => env('AWS_KEY_ID'),
        'secret' => env('AWS_KEY_SECRET'),
        'region' => 'us-east-1',
    ],

    'mutex' => [
        'mock' => env('MUTEX_MOCK', false)
    ],

    'geolocation' => [
        'provider'  => GeoLocation::EUREKA,
        'mocked'    => env('GEOLOCATION_MOCKED', false),
        'providers' => [
            GeoLocation::EUREKA => [
                'url'  => 'http://api.eurekapi.com/iplocation/v1.8/locateip',
                'keys' => [
                    env('GEOLOCATION_EUREKA_KEY_0', 'SAK2YE37KH4JT7AZ345Z'),
                    env('GEOLOCATION_EUKEKA_KEY_1', 'SAKU22KQ93GX2M89VSMZ'),
                    env('GEOLOCATION_EUKEKA_KEY_2', 'SAKC39222CXM3D43472Z'),
                    env('GEOLOCATION_EUKEKA_KEY_3', 'SAKB868673766J745Q8Z'),
                    env('GEOLOCATION_EUKEKA_KEY_4', 'SAK39637TMH8PY64M46Z'),
                    env('GEOLOCATION_EUKEKA_KEY_5', 'SAK2MT34DD6WXDV3DP4Z'),
                ],
            ],
        ],
    ]
];
