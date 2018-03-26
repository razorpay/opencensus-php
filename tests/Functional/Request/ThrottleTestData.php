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

    'testGetOrderWhenThrottledSecondTime1' => [
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

    'testGetOrderWhenThrottledSecondTime2' => [
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
                    'description' => 'Request failed. Please try after sometime.',
                ],
            ],
            'status_code' => 429,
        ],
    ],
];
