<?php

namespace RZP\Tests\Unit\Request\Helpers;

use RZP\Http\Throttle\Constant as K;

return [

    // Set of sample settings
    // It doesn't need to be full setting hash, just enough to assert cases
    'settings' => [
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
        // Case: Blocks an specific route in live mode for all merchants
        'global_4'   => [
            [
                'live:private:0:invoice_fetch_multiple:block' => 1,
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
        // Case: Blocks and specific route in test and live mode for specific mid
        'id_level_4' => [
            [],
            [
                'test:private:0:invoice_fetch_multiple:block' => 1,
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
        // Case: Mock disabled only for specific identifier, on specific route
        'global_id_2' => [
            [
                'mock'                                       => 1,
            ],
            [
                'test:private:0:invoice_fetch_multiple:mock' => 0,
            ],
        ],
    ],

    //
    // Per available request case, lists:
    // - What id settings key is expected to be used?
    // - What throttle key is expected to be used?
    // - What throttle values is expected to be used, per settings above?
    //   (Usage defaults for assertions where expected data is missing.)
    //

    'publicRouteWithKeyInHeaders' => [
        'id_settings_key' => '10000000000000',
        'throttle_key'    => 'invoice_get_status:test:public:0::10000000000000::1.1.1.1',
        'settings'        => [
        ],
    ],

    'publicRouteWithKeyInQuery' => [
        'id_settings_key' => '10000000000000',
        'throttle_key'    => 'invoice_get_status:test:public:0::10000000000000::1.1.1.1',
        'settings'        => [
        ],
    ],

    'publicRouteWithKeyInInput' => [
        'id_settings_key' => '10000000000000',
        'throttle_key'    => 'payment_create:test:public:0::10000000000000::1.1.1.1',
        'settings'        => [
        ],
    ],

    'publicCallbackRoute' => [
        'id_settings_key' => '10000000000000',
        'throttle_key'    => 'payment_callback_with_key_get:test:public:0::10000000000000::1.1.1.1',
        'settings'        => [
        ],
    ],

    'publicRouteWithOAuthPublicToken' => [
        'id_settings_key' => '',
        'throttle_key'    => 'invoice_get_status:test:public:0::100OAuthPublic::1.1.1.1',
        'settings'        => [
        ],
    ],

    'publicCallbackRouteWithOAuthPublicToken' => [
        'id_settings_key' => '',
        'throttle_key'    => 'payment_callback_with_key_get:test:public:0::100OAuthPublic::1.1.1.1',
        'settings'        => [
        ],
    ],

    'privateRoute' => [
        'id_settings_key' => '10000000000000',
        'throttle_key'    => 'invoice_fetch_multiple:test:private:0::10000000000000::',
        'settings'        => [
            'global_3'    => [
                K::MAX_BUCKET_SIZE    => 100,
                K::LEAK_RATE_VALUE    => 10,
                K::LEAK_RATE_DURATION => 2,
            ],
            'id_level_3'  => [
                K::MAX_BUCKET_SIZE    => 100,
                K::LEAK_RATE_VALUE    => 10,
                K::LEAK_RATE_DURATION => 2,
            ],
            'id_level_4'  => [
                K::BLOCK              => true,
            ],
            'global_id_1' => [
                K::MAX_BUCKET_SIZE    => 100,
                K::LEAK_RATE_VALUE    => 10,
                K::LEAK_RATE_DURATION => 2,
            ],
            'global_id_2' => [
                K::MOCK               => false,
            ],
        ],
    ],

    'privateRouteWithLiveMode' => [
        'id_settings_key' => '10000000000000',
        'throttle_key'    => 'invoice_fetch_multiple:live:private:0::10000000000000::',
        'settings'        => [
            'global_1'    => [
                K::MAX_BUCKET_SIZE    => 50,
                K::LEAK_RATE_VALUE    => 5,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_4'    => [
                K::BLOCK              => true,
            ],
            'id_level_1'  => [
                K::MAX_BUCKET_SIZE    => 50,
                K::LEAK_RATE_VALUE    => 5,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_id_1' => [
                K::MAX_BUCKET_SIZE    => 50,
                K::LEAK_RATE_VALUE    => 5,
                K::LEAK_RATE_DURATION => 1,
            ],
        ],
    ],

    'privateRouteWithOAuthBearerToken' => [
        'id_settings_key' => '100OAuthClient',
        'throttle_key'    => 'invoice_fetch_multiple::private:0:100OAuthClient:10000000000000::',
        'settings'        => [
            'global_1'    => [
                K::MAX_BUCKET_SIZE    => 50,
                K::LEAK_RATE_VALUE    => 5,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_4'    => [
                K::BLOCK              => 1,
            ],
            'id_level_1'  => [
                K::MAX_BUCKET_SIZE    => 50,
                K::LEAK_RATE_VALUE    => 5,
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
        'throttle_key'    => 'invoice_create:test:private:1::10000000000000:MerchantUser01:',
        'settings'        => [
            'global_2'    => [
                K::MAX_BUCKET_SIZE    => 50,
                K::LEAK_RATE_VALUE    => 5,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_2'  => [
                K::MAX_BUCKET_SIZE    => 50,
                K::LEAK_RATE_VALUE    => 5,
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
        'throttle_key'    => 'batch_create:test:private:1::10000000000000:MerchantUser01:',
        'settings'        => [
            'global_2'    => [
                K::MAX_BUCKET_SIZE    => 50,
                K::LEAK_RATE_VALUE    => 5,
                K::LEAK_RATE_DURATION => 1,
            ],
            'id_level_2'  => [
                K::MAX_BUCKET_SIZE    => 50,
                K::LEAK_RATE_VALUE    => 5,
                K::LEAK_RATE_DURATION => 1,
            ],
            'global_id_1' => [
                K::MAX_BUCKET_SIZE    => 75,
                K::LEAK_RATE_VALUE    => 10,
                K::LEAK_RATE_DURATION => 5,
            ],
        ],
    ],

    'privilegeRouteWithInternalAppAuth' => [
        'id_settings_key' => 'merchant_dashboard',
        'throttle_key'    => 'invoice_expire_bulk:test:privilege:0::merchant_dashboard::',
        'settings'        => [
            'global_id_1' => [
                K::MAX_BUCKET_SIZE    => 200,
                K::LEAK_RATE_VALUE    => 10,
                K::LEAK_RATE_DURATION => 2,
            ],
        ],
    ],

    'privilegeRouteWithAdminAuth' => [
        'id_settings_key' => 'test@test.com',
        'throttle_key'    => 'dummy_route:test:privilege:0::test@test.com::',
        'settings'        => [
            'global_id_1' => [
                K::MAX_BUCKET_SIZE    => 80,
                K::LEAK_RATE_VALUE    => 10,
                K::LEAK_RATE_DURATION => 2,
            ],
        ],
    ],

    'directRoute' => [
        'id_settings_key' => '',
        'throttle_key'    => 'checkout_public::direct:0::::1.1.1.1',
        'settings'        => [
        ],
    ],

    // 'deviceRoute' => [
    //     'id_settings_key' => '10000000000000',
    //     'throttle_key'    => 'vpa_create:test:device:0::10000000000000::',
    //     'settings'        => [
    //     ],
    // ],
];
