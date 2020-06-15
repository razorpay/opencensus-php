<?php

namespace RZP\Tests\Functional\Request;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testNonexistentRoute' => [
        'request' => [
            'method' => 'get',
            'url'    => '/invalid_route',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The requested URL was not found on the server.',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testFetchOrdersWhenNotThrottled' => [
        'request' => [
            'method' => 'get',
            'url'    => '/orders',
        ],
        'response' => [
            'content' => [
                'count' => 0,
                'items' => [],
            ],
        ],
    ],

    'testGetOrderWhenThrottled' => [
        'request' => [
            'method' => 'get',
            'url'    => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Request failed. Please try after sometime.',
                ],
            ],
            'status_code' => 429,
        ],
    ],

    'testGetOrderWhenThrottledWithoutMockForSpecificMerchant1' => [
        'request' => [
            'method' => 'get',
            'url'    => '/orders',
        ],
        'response' => [
            'content' => [
                'count' => 0,
                'items' => [],
            ],
        ],
    ],

    'testGetOrderWhenThrottledWithoutMockForSpecificMerchant2' => [
        'request' => [
            'method' => 'get',
            'url'    => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Request failed. Please try after sometime.',
                ],
            ],
            'status_code' => 429,
        ],
    ],

    'testGetOrderWhenRedisSettingsMissing' => [
        'request' => [
            'method' => 'get',
            'url'    => '/orders',
        ],
        'response' => [
            'content' => [
                'count' => 0,
                'items' => [],
            ],
        ],
    ],

    'testGetOrderWhenBlockedForTestMerchant1' => [
        'request' => [
            'method' => 'get',
            'url'    => '/orders',
        ],
        'response' => [
            'content' => [
                'count' => 0,
                'items' => [],
            ],
        ],
    ],

    'testGetOrderWhenBlockedForTestMerchant2' => [
        'request' => [
            'method' => 'get',
            'url'    => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Access forbidden for requested resource',
                ],
            ],
            'status_code' => 403,
        ],
    ],

    'testGetOrderWhenIPBlockedSuccess' => [
        'request' => [
            'method' => 'get',
            'url'    => '/orders',
        ],
        'response' => [
            'content' => [
                'count' => 0,
                'items' => [],
            ],
        ],
    ],

    'testGetOrderWhenIPBlockedFailure' => [
        'request' => [
            'method' => 'get',
            'url'    => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Access forbidden for requested resource',
                ],
            ],
            'status_code' => 403,
        ],
    ],

    'testGetOrderWhenUABlockedSuccess' => [
        'request' => [
            'method' => 'get',
            'url'    => '/orders',
            'server' => [
                'HTTP_USER_AGENT' => 'WhiteList UA'
            ],
        ],
        'response' => [
            'content' => [
                'count' => 0,
                'items' => [],
            ],
        ],
    ],

    'testGetOrderWhenUABlockedFailure1' => [
        'request' => [
            'method' => 'get',
            'url'    => '/orders',
            'server' => [
                'HTTP_USER_AGENT' => 'Razorpay UA'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Access forbidden for requested resource',
                ],
            ],
            'status_code' => 403,
        ],
    ],

    'testGetOrderWhenUABlockedFailure2' => [
        'request' => [
            'method' => 'get',
            'url'    => '/orders',
            'server' => [
                'HTTP_USER_AGENT' => 'Razorpay'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Access forbidden for requested resource',
                ],
            ],
            'status_code' => 403,
        ],
    ],

    'testCreateThrottleSettings' => [
        'request' => [
            'method'  => 'put',
            'url'     => '/throttle/settings',
            'content' => [
                'id'    => '10000000000000',
                'mode'  => 'live',
                'auth'  => 'public',
                'proxy' => 0,
                'route' => 'order_fetch',
                'rules' => [
                    'mock'        => 0,
                    'lrv'         => 60,
                    'lrd'         => 60,
                    'mbs'         => 10,
                    'blocked_ips' => '12.22.43.123||342.23.12.32',
                    'block'       => true,
                ],
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testFetchThrottleSettings' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/throttle/settings',
            'content' => [
                'id'    => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [
                'live:public:0:order_fetch:mock'        => '0',
                'live:public:0:order_fetch:lrv'         => '60',
                'live:public:0:order_fetch:lrd'         => '60',
                'live:public:0:order_fetch:mbs'         => '10',
                'live:public:0:order_fetch:blocked_ips' => '12.22.43.123||342.23.12.32',
                'live:public:0:order_fetch:block'       => '1',
            ],
        ],
    ],

    'testThrottleCreateSettings1' => [
        'request' => [
            'method'  => 'put',
            'url'     => '/throttle/settings',
            'content' => [
                'rules' => [
                    'blocked_ips' => '10.0.123.124||10.0.123.125',
                ],
            ],
        ],
    ],

    'testThrottleCreateSettings2' => [

        'request' => [
            'method'  => 'put',
            'url'     => '/throttle/settings',
            'content' => [
                'mode'  => 'test',
                'auth'  => 'private',
                'proxy' => 0,
                'route' => 'invoice_fetch_multiple',
                'rules' => [
                    'block' => 1,
                ],
            ],
        ],
    ],

     'testThrottleConfigCreateMerchant1' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/throttle/config',
            'content' => [
                'type'   => 'merchant',
                'merchant_id'           => '10000000000000',
                'route'                 => 'order_create',
                'request_count'         => 120,
                'request_count_window'  => 60,

            ],
        ],
    ],

    'testMigrateThrottleKeysFromRedisLabs' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/throttle/throttle_migrate_redis',
            'content' => [
            ],
        ],
    ],


    'testThrottleConfigCreateMerchant2' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/throttle/config',
            'content' => [
                'type'   => 'merchant',
                'merchant_id'           => '10000000000000',
                'route'                 => 'payment_create',
                'request_count'         => 100,
                'request_count_window'  => 60,

            ],
        ],
    ],


    'testThrottleConfigCreateMerchant3' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/throttle/config',
            'content' => [
                'type'   => 'merchant',
                'merchant_id'           => '10000000000000',
                'route'                 => 'order_create',
                'request_count'         => 180,
                'request_count_window'  => 60,

            ],
        ],
    ],

    'testThrottleConfigCreateMerchantFetch' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/throttle/config?merchant_id=10000000000000',
            'content' => [
            ],
        ],
    ],


    'testThrottleConfigCreateMerchantDelete1' => [
        'request' => [
            'method'  => 'delete',
            'url'     => '/throttle/config?merchant_id=10000000000000&&route=order_create',
            'content' => [
            ],
        ],
    ],


    'testThrottleConfigCreateRoute1' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/throttle/config',
            'content' => [
                'type'   => 'route',
                'route'                 => 'order_create',
                'request_count'         => 120,
                'request_count_window'  => 60,
                'throttle_type'       => 'merchant',
            ],
        ],
    ],

     'testThrottleConfigCreateRoute2' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/throttle/config',
            'content' => [
                'type'   => 'route',
                'route'                 => 'order_create',
                'request_count'         => 120,
                'request_count_window'  => 60,
                'throttle_type'       => 'org',
            ],
        ],
    ],

     'testThrottleConfigCreateRoute3' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/throttle/config',
            'content' => [
                'type'   => 'route',
                'route'                 => 'order_create',
                'request_count'         => 120,
                'request_count_window'  => 60,
                'throttle_type'       => 'ip',
            ],
        ],
    ],

    'testThrottleConfigCreateRoute4' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/throttle/config',
            'content' => [
                'type'   => 'route',
                'route'                 => 'order_create',
                'request_count'         => 180,
                'request_count_window'  => 60,
                'throttle_type'       => 'ip',
            ],
        ],
    ],

    'testThrottleConfigCreateRouteDelete1' => [
        'request' => [
            'method'  => 'delete',
            'url'     => '/throttle/config?&route=order_create',
            'content' => [
            ],
        ],
    ],

    'testThrottleConfigCreateRouteFetch' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/throttle/config?route=order_create',
            'content' => [
            ],
        ],
    ],

    'testThrottleFetchAll' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/throttle/config',
            'content' => [
            ],
        ],
    ],
];
