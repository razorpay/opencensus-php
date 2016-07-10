<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testGetPaymentMethodsRoute' => [
        'request' => [
            'url' => '/methods',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'methods',
                'card' => true,
                'netbanking' => [
                    'UTIB' => 'Axis Bank',
                    'YESB' => 'Yes Bank',
                ],
                'wallet' => [
//                    'paytm' => false,
                ],
            ],
        ],
    ],

    'testGetPaymentMethodsRouteWithNetbankingFalse' => [
        'request' => [
            'url' => '/methods',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'methods',
                'card' => true,
                'netbanking' => [],
                'wallet' => [
//                    'paytm' => false,
                ],
            ],
        ],
    ]
];