<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testFailedAuthPayment' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_MISSING_DATA,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_MISSING_DATA,
        ],
    ],

    'testGatewayError' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => 'There is a problem with the gateway causing the payment to fail',
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_TIMED_OUT,
        ],
    ],

    'testGatewayWithSavedCard' => [
        'response' => [
            'content' => [
                'razorpay_payment_id'
            ],
        ]
    ],

    'testPayment' => [
        'merchant_id' => '10000000000000',
        'amount' => 50000,
        'method' => 'card',
        'status' => 'captured',
        'amount_authorized' => 50000,
        'amount_refunded' => 0,
        'refund_status' => null,
        'currency' => 'INR',
        'description' => 'random description',
        'error_code' => null,
        'error_description' => null,
        'email' => 'a@b.com',
        'contact' => '+919918899029',
        'notes' => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway' => 'cybersource',
        'terminal_id' => '1000CybrsTrmnl',
        'signed' => false,
        'verified' => null,
        'fee' => 1150,
        'service_tax' => 150,
        'entity' => 'payment',
    ],

    'testTransactionAfterCapture' => [
        'type' => 'payment',
        'merchant_id' => '10000000000000',
        'amount' => 50000,
        'fee' => 1150,
        'debit' => 0,
        'credit' => 48850,
        'currency' => 'INR',
        'balance' => 1048850,
        'gateway_fee' => 0,
        'api_fee' => 0,
        'escrow_balance' => 1048850,
        'channel' => 'kotak',
        'settled' => false,
        'settlement_id' => null,
        'reconciled_at' => null,
        'entity' => 'transaction',
        'admin' => true,
    ],

    'testPaymentCybersourceEntity' => [
        'amount' => 500,
        'pares_status' => 'Y',
        'reason_code' => 475,
        'action' => 'capture',
        'received' => true,
        'refund_id' => null,
        'auth_data' => null,
        'commerce_indicator' => 'Internet',
        'eci' => '05',
        'cavv' => '1',
        'status' => 'captured',
        'entity' => 'cybersource',
    ],

    'testNotEnrolledCSEntity' => [
        'amount' => 500,
        'pares_status' => null,
        'status' => 'captured',
        'entity' => 'cybersource',
    ],

    'testPaymentRefund' => [
        'reason_code' => 475,
        'received' => true,
        'amount' => 500,
        'commerce_indicator' => "Internet",
        'pares_status' => 'Y',
        'action' => 'refund',
        'status' => 'refunded',
        'entity' => 'cybersource',
        'admin' => true,
    ],

    'testAuthPaymentRefund' => [
        'amount' => 50000,
        'currency' => 'INR',
        'entity' => 'refund',
        'admin' => true,
    ]
];