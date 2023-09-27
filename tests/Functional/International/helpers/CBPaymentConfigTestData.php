<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateLRSConfigFromAdminAuth' => [
        'request' => [
            'content' => [
                'name'       => 'lrs',
                'type'       => 'lrs',
                'config'     => [
                    'lrs_markup_percentage' => '5.43',
                ],
            ],
            'method'    => 'POST',
            'url'       => '/admin/pxb/payment/config',
            'server' => [
                'HTTP_X_RAZORPAY_ACCOUNT' => '10000000000000',
            ],
        ],
        'response' => [
            'content' => []
        ],
    ],
    'testFetchLRSConfigFromAdminAuth' => [
        'request' => [
            'method'    => 'GET',
            'url'       => '/admin/pxb/payment/config',
            'server' => [
                'HTTP_X_RAZORPAY_ACCOUNT' => '10000000000000',
            ],
        ],
        'response' => [
            'content' => []
        ],
    ],
];
