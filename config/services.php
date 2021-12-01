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
        'region' => 'ap-south-1',
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
                    env('GEOLOCATION_EUREKA_KEY_0', 'SAK9BD4R8U3DD6VK97CZ'),
                    env('GEOLOCATION_EUKEKA_KEY_1', 'SAK2PW593FSH7785LWLZ'),
                    env('GEOLOCATION_EUKEKA_KEY_2', 'SAKCJ832KVHFUWT77CTZ'),
                    env('GEOLOCATION_EUKEKA_KEY_3', 'SAK9R4UG463D972CX3QZ'),
                    env('GEOLOCATION_EUKEKA_KEY_4', 'SAK6SFZCQPX97465546Z'),
                    env('GEOLOCATION_EUKEKA_KEY_5', 'SAKRZU8Y4QLP5M27TQ4Z'),
                ],
            ],
        ],
    ],

    'apspdcl' => [
        'base_url' => env('APSPDCL_BASE_URL'),
    ],

    'excel_store' => [
        'base_url'              => env('EXCEL_STORE_BASE_URL'),
        'secret'                => env('EXCEL_STORE_SECRET'),
    ],

    'de_personalisation' => [
        'url'                   => env('DE_PERSONALISATION_URL'),
        'username'              => env('DE_PERSONALISATION_KEY'),
        'password'              => env('DE_PERSONALISATION_SECRET'),
        'mock'                  => env('DE_PERSONALISATION_MOCK', true),
    ],

    // See razorpay/credcase.
    'credcase' => [
        'dual_write_enabled' => env('CREDCASE_DUAL_WRITE_ENABLED', true),
        'host'               => env('CREDCASE_HOST', 'https://credcase.razorpay.com'),
        'user'               => env('CREDCASE_USER'),
        'password'           => env('CREDCASE_PASSWORD'),
    ],

    // Ref \RZP\Services\CredcaseSigner.php.
    'credcase_signer' => [
        'mock'        => env('CREDCASE_SIGNER_MOCK'),
        'private_key' => env('CREDCASE_SIGNER_PRIVATE_KEY'),
    ],

    'throttler' => [
        'host'   => env('EDGE_THROTTLER_HOST'),
        'apikey' => env('EDGE_THROTTLER_API_KEY'),
    ],

    //check razorpay/business_verification_service
    'business_verification_service' => [
        'mock'         => env('BVS_MOCK', false),
        'host'         => env('BVS_HOST', 'https://bvs.razorpay.com'),
        'user'         => env('BVS_USER'),
        'password'     => env('BVS_PASSWORD'),
        'client_id'    => env('BVS_CLIENT_ID', 'API')
    ],

    'merchants_risk' => [
        // Api url for merchant risks service.
        'url'                => env('MERCHANT_RISKS_URL'),
        'mock'               => env('MERCHANT_RISK_SERVICE_MOCK', false),
        'request_timeout'    => env('MERCHANT_RISK_SERVICE_REQUEST_TIMEOUT', 500),
        'connection_timeout' => env('MERCHANT_RISK_SERVICE_CONNECTION_TIMEOUT', 500),
        'auth' => [
            'key'       => env('MERCHANT_RISKS_CLIENT_KEY'),
            'secret'    => env('MERCHANT_RISKS_CLIENT_SECRET')
        ],
    ],
    'segment_analytics' => [
        'url'                => env('SEGMENT_ANALYTICS_URL'),
        'mock'               => env('SEGMENT_ANALYTICS_MOCK', false),
        'request_timeout'    => env('SEGMENT_ANALYTICS_TIMEOUT', 500),
        'connection_timeout' => env('SEGMENT_ANALYTICS_CONNECTION_TIMEOUT', 500),
        'auth' => [
            'write_key'       => env('SEGMENT_ANALYTICS_WRITE_KEY'),
        ],
    ],

    'merchant_risk_alerts' => [
        'url'  => env('MERCHANT_RISK_ALERTS_URL'),
        'mock' => env('MERCHANT_RISK_ALERTS_MOCK', false),
        'auth' => [
            'key'       => env('MERCHANT_RISK_ALERTS_CLIENT_KEY'),
            'secret'    => env('MERCHANT_RISK_ALERTS_CLIENT_SECRET')
        ],
        'new' => [
            'url'  => env('NEW_MERCHANT_RISK_ALERTS_URL'),
            'auth' => [
                'key'       => env('NEW_MERCHANT_RISK_ALERTS_CLIENT_KEY'),
                'secret'    => env('NEW_MERCHANT_RISK_ALERTS_CLIENT_SECRET'),
            ],
        ],
    ],

    'druid' => [
        'mock'   => env('DRUID_MOCK', true),
        'url'    => env('DRUID_BASE_URL'),
        'auth'   => [
            'key'    => env('DRUID_CLIENT_KEY'),
            'secret' => env('DRUID_CLIENT_SECRET'),
        ],
    ],

    'presto' => [
        'host'   => env('DATALAKE_PRESTO_HOST'),
        'port'   => env('DATALAKE_PRESTO_PORT'),
        'scheme' => env('DATALAKE_PRESTO_SCHEME', 'https'),
        'user'   => env('DATALAKE_PRESTO_USER'),
        'mock'   => env('DATALAKE_PRESTO_MOCK', true),
    ],
];
