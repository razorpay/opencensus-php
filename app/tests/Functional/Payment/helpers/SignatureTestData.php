<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'testValidSignature' =>[
        'request' => [
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'amount' => 50000,
                'currency' => 'INR',
                'merchant_order_id' => 'random order id'
            ]
        ],
    ],

    'testPaymentStatusAfterSignedRequest' => [
        'request' => [
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
                'amount' => 50000,
                'currency' => 'INR',
                'notes' => [
                    'merchant_order_id' => 'random order id'
                ]
            ],
        ],
    ],
];