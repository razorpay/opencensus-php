<?php

namespace RZP\Tests\Functional\Merchant\Bvs;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testGetGstDetailsSuccess' => [
        'request'     => [
            'method'  => 'GET',
            'url'     => '/merchant/activation/gst_details',
        ],
        'response'    => [
            'content' => ["results"=> [
                    "13AAACR5055K1ZG",
                    "26AAACR5055K1Z9"
            ]],
        ],
        'status_code' => 200,
    ],
    'testGetGstDetailsSuccessFromStore' => [
        'request'     => [
            'method'  => 'GET',
            'url'     => '/merchant/activation/gst_details',
        ],
        'response'    => [
            'content' => ["results"=> [
                "22AAACR5055K1ZH",
                "03AAACR5055K2ZG"
            ]],
        ],
        'status_code' => 200,
    ],
    'testGetGstDetailsFailure' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/merchant/activation/gst_details',
        ],
        'response' => [
            'content' => ["results"=> []],
        ],
        'status_code' => 200,
    ],

    'testGetGstDetailsRateLimitExhausted' => [
        'request'   => [
            'method'  => 'GET',
            'url'     => '/merchant/activation/gst_details',
        ],
        'response' => [
            'content' => ["results"=> []]
        ],
        'status_code' => 200,
    ],

    'testGetGstDetailsInvalidBusinessType' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/merchant/activation/gst_details',
        ],
        'response' => [
            'content' => ["results"=> []]
        ],
        'status_code' => 200,
    ]
];
