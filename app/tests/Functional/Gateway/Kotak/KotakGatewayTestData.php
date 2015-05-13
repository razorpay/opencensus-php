<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'testPayment' => [
        'merchant_id' => '10000000000000',
        'amount' => 50000,
        'method' => 'card',
        'status' => 'captured',
        'amount_authorized' => 50000,
        'amount_refunded' => 0,
        'refund_status' => NULL,
        'currency' => 'INR',
        'description' => 'random description',
        'bank' => null,
        'error_code' => NULL,
        'error_description' => NULL,
        'email' => 'a@b.com',
        'contact' => '9918899029',
        'notes' => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway' => 'kotak',
        'terminal_id' => '1000KotakTrmnl',
        'signed' => false,
        'verified' => NULL,
        'entity' => 'payment',
    ],

    'testTransactionDeclinedPayment' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '6075000000000015',
                    'expiry_month' => '05',
                    'expiry_year' => '2017',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            'gateway_error_code'  => null
        ],
    ],
];
