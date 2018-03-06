<?php

namespace RZP\Tests\Unit\Request\Helpers;

use RZP\Http\Throttle\Constant as K;

return [

    // Set of sample settings
    // It doesn't need to be full setting hash, just enough to assert cases
    'settings' => [
        // Empty/missing settings case: Picks defaults hard-coded value in code
        'none' => [
            [],
            [],
        ],
        // Global settings for live mode for private auth
        'global_1' => [
            [
                'live:private:0:mbs' => 50,
                'live:private:0:lrv' => 5,
                'live:private:0:lrd' => 1,
            ],
            [],
        ],
        // Global settings for test mode for proxy auth
        'global_2' => [
            [
                'test:private:1:mbs' => 50,
                'test:private:1:lrv' => 5,
                'test:private:1:lrd' => 1,
            ],
            [],
        ],
        // Global settings for test mode for private auth for specific route
        'global_3' => [
            [
                'test:private:0:invoice_fetch_multiple:mbs' => 100,
                'test:private:0:invoice_fetch_multiple:lrv' => 10,
                'test:private:0:invoice_fetch_multiple:lrd' => 2,
            ],
            [],
        ],
        // Id level settings for live mode for private auth
        'id_level_1' => [
            [],
            [
                'live:private:0:mbs' => 50,
                'live:private:0:lrv' => 5,
                'live:private:0:lrd' => 1,
            ],
        ],
        // Id level settings for test mode for proxy auth
        'id_level_2' => [
            [],
            [
                'test:private:1:mbs' => 50,
                'test:private:1:lrv' => 5,
                'test:private:1:lrd' => 1,
            ],
        ],
        // Id level settings for test mode for private auth for specific route
        'id_level_3' => [
            [],
            [
                'test:private:0:invoice_fetch_multiple:mbs' => 100,
                'test:private:0:invoice_fetch_multiple:lrv' => 10,
                'test:private:0:invoice_fetch_multiple:lrd' => 2,
            ],
        ],
        // Global and id level settings for test mode for private auth
        'global_id_1' => [
            [
                'live:private:0:mbs'                        => 50,
                'live:private:0:lrv'                        => 5,
                'live:private:0:lrd'                        => 1,
                'test:private:1:mbs'                        => 75,
                'test:private:1:lrv'                        => 10,
                'test:private:1:lrd'                        => 5,
                'test:privilege:0:mbs'                      => 80,
                'test:privilege:0:lrv'                      => 10,
                'test:privilege:0:lrd'                      => 2,
                'test:private:0:invoice_fetch_multiple:mbs' => 85,
                'test:private:0:invoice_fetch_multiple:lrv' => 10,
                'test:private:0:invoice_fetch_multiple:lrd' => 5,
            ],
            [
                'test:private:0:invoice_fetch_multiple:mbs' => 100,
                'test:private:0:invoice_fetch_multiple:lrv' => 10,
                'test:private:0:invoice_fetch_multiple:lrd' => 2,
                'test:privilege:0:invoice_expire_bulk:mbs'  => 200,
                'test:privilege:0:invoice_expire_bulk:lrv'  => 10,
                'test:privilege:0:invoice_expire_bulk:lrd'  => 2,
            ],
        ],
    ],

    //
    // Per available request case, lists:
    // - what id settings key is expected to be used
    // - what throttle key is expected to be used
    // - what throttle values is expected to be used, per settings
    //

    'publicRouteWhenKeyInHeaders' => [
        'id_settings_key' => '10000000000000',
        'throttle_key'    => 'invoice_get_status:test:public:0::10000000000000:1.1.1.1',
        'settings'        => [
            'none'        => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_1'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_2'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_3'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_1'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_2'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_3'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_id_1' => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
        ],
    ],

    'publicRouteWhenKeyInQuery' => [
        'id_settings_key' => '10000000000000',
        'throttle_key'    => 'invoice_get_status:test:public:0::10000000000000:1.1.1.1',
        'settings'        => [
            'none'        => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_1'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_2'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_3'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_1'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_2'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_3'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_id_1' => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
        ],
    ],

    'publicRouteWhenKeyInInput' => [
        'id_settings_key' => '10000000000000',
        'throttle_key'    => 'payment_create:test:public:0::10000000000000:1.1.1.1',
        'settings'        => [
            'none'        => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_1'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_2'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_3'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_1'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_2'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_3'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_id_1' => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
        ],
    ],

    'publicCallbackRoute' => [
        'id_settings_key' => '10000000000000',
        'throttle_key'    => 'payment_callback_with_key_get:test:public:0::10000000000000:1.1.1.1',
        'settings'        => [
            'none'        => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_1'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_2'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_3'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_1'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_2'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_3'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_id_1' => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
        ],
    ],

    'privateRoute' => [
        'id_settings_key' => '10000000000000',
        'throttle_key'    => 'invoice_fetch_multiple:test:private:0::10000000000000:',
        'settings'        => [
            'none'        => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_1'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_2'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_3'    => [
                K::MAX_BUCKET_SIZE    => 100,
                K::LEAK_RATE_VALUE    => 10,
                K::LEAK_RATE_DURATION => 2,
            ],
            'id_level_1'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_2'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_3'  => [
                K::MAX_BUCKET_SIZE    => 100,
                K::LEAK_RATE_VALUE    => 10,
                K::LEAK_RATE_DURATION => 2,
            ],
            'global_id_1' => [
                K::MAX_BUCKET_SIZE    => 100,
                K::LEAK_RATE_VALUE    => 10,
                K::LEAK_RATE_DURATION => 2,
            ],
        ],
    ],

    'privateRouteWhenLiveMode' => [
        'id_settings_key' => '10000000000000',
        'throttle_key'    => 'invoice_fetch_multiple:live:private:0::10000000000000:',
        'settings'        => [
            'none'        => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_1'    => [
                K::MAX_BUCKET_SIZE    => 50,
                K::LEAK_RATE_VALUE    => 5,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_2'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_3'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_1'  => [
                K::MAX_BUCKET_SIZE    => 50,
                K::LEAK_RATE_VALUE    => 5,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_2'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_3'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_id_1' => [
                K::MAX_BUCKET_SIZE    => 50,
                K::LEAK_RATE_VALUE    => 5,
                K::LEAK_RATE_DURATION => 1,
            ],
        ],
    ],

    'privateRouteWithProxyAuth' => [
        'id_settings_key' => '10000000000000',
        'throttle_key'    => 'invoice_create:test:private:1::10000000000000:',
        'settings'        => [
            'none'        => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_1'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_2'    => [
                K::MAX_BUCKET_SIZE    => 50,
                K::LEAK_RATE_VALUE    => 5,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_3'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_1'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_2'  => [
                K::MAX_BUCKET_SIZE    => 50,
                K::LEAK_RATE_VALUE    => 5,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_3'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_id_1' => [
                K::MAX_BUCKET_SIZE    => 75,
                K::LEAK_RATE_VALUE    => 10,
                K::LEAK_RATE_DURATION => 5,
            ],
        ],
    ],

    'proxyRoute' => [
        'id_settings_key' => '10000000000000',
        'throttle_key'    => 'batch_create:test:private:1::10000000000000:',
        'settings'        => [
            'none'        => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_1'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_2'    => [
                K::MAX_BUCKET_SIZE    => 50,
                K::LEAK_RATE_VALUE    => 5,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_3'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_1'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_2'  => [
                K::MAX_BUCKET_SIZE    => 50,
                K::LEAK_RATE_VALUE    => 5,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_3'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_id_1' => [
                K::MAX_BUCKET_SIZE    => 75,
                K::LEAK_RATE_VALUE    => 10,
                K::LEAK_RATE_DURATION => 5,
            ],
        ],
    ],

    'privilegeRouteWhenInternalAppAuth' => [
        'id_settings_key' => 'dashboard',
        'throttle_key'    => 'invoice_expire_bulk:test:privilege:0::dashboard:',
        'settings'        => [
            'none'        => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_1'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_2'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_3'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_1'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_2'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_3'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_id_1' => [
                K::MAX_BUCKET_SIZE    => 200,
                K::LEAK_RATE_VALUE    => 10,
                K::LEAK_RATE_DURATION => 2,
            ],
        ],
    ],

    'privilegeRouteWhenAdminAuth' => [
        'id_settings_key' => 'test@test.com',
        'throttle_key'    => 'dummy_route:test:privilege:0::test@test.com:',
        'settings'        => [
            'none'        => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_1'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_2'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_3'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_1'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_2'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_3'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_id_1' => [
                K::MAX_BUCKET_SIZE    => 80,
                K::LEAK_RATE_VALUE    => 10,
                K::LEAK_RATE_DURATION => 2,
            ],
        ],
    ],

    'directRoute' => [
        'id_settings_key' => '',
        'throttle_key'    => 'checkout_public::direct:0:::1.1.1.1',
        'settings'        => [
            'none'        => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_1'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_2'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_3'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_1'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_2'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_3'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_id_1' => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
        ],
    ],

    'deviceRoute' => [
        'id_settings_key' => '10000000000000',
        'throttle_key'    => 'vpa_create:test:device:0::10000000000000:',
        'settings'        => [
            'none'        => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_1'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_2'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_3'    => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_1'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_2'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_3'  => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_id_1' => [
                K::MAX_BUCKET_SIZE    => 30,
                K::LEAK_RATE_VALUE    => 2,
                K::LEAK_RATE_DURATION => 1,
            ],
        ],
    ],
];
