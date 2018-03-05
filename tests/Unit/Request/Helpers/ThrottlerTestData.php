<?php

namespace RZP\Tests\Unit\Request\Helpers;

return [

    // Set of sample settings
    'settings' => [
        'none' => [
            [],
            [],
        ],
    ],

    //
    // Per available request case, lists:
    // - what id settings key is expected to be used
    // - what throttle key is expected to be used
    // - what throttle values is expected to be used, per settings available(above ^)
    //

    'publicRouteWhenKeyInHeaders' => [
        'id'       => '10000000000000',
        'key'      => 'invoice_get_status:test:public:0::10000000000000:1.1.1.1',
        'settings' => [
            'none' => [2, 1, 30],
        ],
    ],

    'publicRouteWhenKeyInQuery' => [
        'id'       => '10000000000000',
        'key'      => 'invoice_get_status:test:public:0::10000000000000:1.1.1.1',
        'settings' => [
            'none' => [2, 1, 30],
        ],
    ],

    'publicRouteWhenKeyInInput' => [
        'id'       => '10000000000000',
        'key'      => 'payment_create:test:public:0::10000000000000:1.1.1.1',
        'settings' => [
            'none' => [2, 1, 30],
        ],
    ],

    'publicCallbackRoute' => [
        'id'       => '10000000000000',
        'key'      => 'payment_callback_with_key_get:test:public:0::10000000000000:1.1.1.1',
        'settings' => [
            'none' => [2, 1, 30],
        ],
    ],

    'privateRoute' => [
        'id'       => '10000000000000',
        'key'      => 'invoice_fetch_multiple:test:private:0::10000000000000:',
        'settings' => [
            'none' => [2, 1, 30],
        ],
    ],

    'privateRouteWhenLiveMode' => [
        'id'       => '10000000000000',
        'key'      => 'invoice_fetch_multiple:live:private:0::10000000000000:',
        'settings' => [
            'none' => [2, 1, 30],
        ],
    ],

    'privateRouteWithProxyAuth' => [
        'id'       => '10000000000000',
        'key'      => 'invoice_create:test:private:1::10000000000000:',
        'settings' => [
            'none' => [2, 1, 30],
        ],
    ],

    'proxyRoute' => [
        'id'       => '10000000000000',
        'key'      => 'batch_create:test:private:1::10000000000000:',
        'settings' => [
            'none' => [2, 1, 30],
        ],
    ],

    'privilegeRouteWhenInternalAppAuth' => [
        'id'       => 'dashboard',
        'key'      => 'invoice_expire_bulk:test:privilege:0::dashboard:',
        'settings' => [
            'none' => [2, 1, 30],
        ],
    ],

    'privilegeRouteWhenAdminAuth' => [
        'id'       => 'test@test.com',
        'key'      => 'dummy_route:test:privilege:0::test@test.com:',
        'settings' => [
            'none' => [2, 1, 30],
        ],
    ],

    'directRoute' => [
        'id'       => '',
        'key'      => 'checkout_public::direct:0:::1.1.1.1',
        'settings' => [
            'none' => [2, 1, 30],
        ],
    ],

    'deviceRoute' => [
        'id'       => '10000000000000',
        'key'      => 'vpa_create:test:device:0::10000000000000:',
        'settings' => [
            'none' => [2, 1, 30],
        ],
    ],
];
