<?php

namespace RZP\Tests\Unit\Request\Helpers;

/**
 * For various request cases, lists expected request context vars set
 * by the application.
 */
return [
    'publicRouteWhenKeyInHeaders' => [
        'expected' => [
            'route'            => 'invoice_get_status',
            'key'              => 'rzp_test_TheTestAuthKey',
            'secret'           => null,
            'bearerToken'      => null,
            'mode'             => 'test',
            'auth'             => 'public',
            'keyWithoutPrefix' => 'TheTestAuthKey',
            'keyId'            => 'TheTestAuthKey',
            'mid'              => null,
            'oauthAppId'       => null,
            'oauthPublicToken' => null,
            'internalAppName'  => null,
            'adminEmail'       => null,
            'device'           => null,
            'proxy'            => false,
        ],
    ],

    'privateRoute' => [
        'expected' => [
            'route'            => 'invoice_fetch_multiple',
            'key'              => 'rzp_test_TheTestAuthKey',
            'secret'           => 'TheKeySecretForTests',
            'bearerToken'      => null,
            'mode'             => 'test',
            'auth'             => 'private',
            'keyWithoutPrefix' => 'TheTestAuthKey',
            'keyId'            => 'TheTestAuthKey',
            'mid'              => null,
            'oauthAppId'       => null,
            'oauthPublicToken' => null,
            'internalAppName'  => null,
            'adminEmail'       => null,
            'device'           => null,
            'proxy'            => false,
        ],
    ],
];
