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

    'testThreeDSAuthFailedPayment' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
            'two_fa_error' => true,
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

    'testGatewayTimeoutError' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR_REQUEST_TIMEOUT,
                ],
            ],
            'status_code' => 504,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayTimeoutException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
    ],

    'testGatewayWithSavedCard' => [
        'response' => [
            'content' => [
                'razorpay_payment_id'
            ],
        ]
    ],

    'testAuthenticationFailurePayment' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => 'Payment failed due to processing error on gateway',
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED,
        ],
    ],

    'testPayment' => [
        'merchant_id' => '10000000000000',
        'amount' => 50000,
        'method' => 'card',
        'status' => 'captured',
        'two_fa_status' => 'passed',
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

    'testCybersourceAuthEntity' => [
        'amount' => 50000,
        'pares_status' => 'Y',
        'reason_code' => 475,
        'action' => 'authorize',
        'received' => true,
        'refund_id' => null,
        'auth_data' => null,
        'commerce_indicator' => 'Internet',
        'eci' => '05',
        'cavv' => '1',
        'status' => 'authorized',
        'entity' => 'cybersource',
    ],

     'testCybersourceCaptureEntity' => [
        'amount' => 50000,
        'pares_status' => null,
        'reason_code' => 100,
        'action' => 'capture',
        'received' => true,
        'refund_id' => null,
        'auth_data' => null,
        'commerce_indicator' => null,
        'eci' => null,
        'cavv' => null,
        'status' => 'captured',
        'entity' => 'cybersource',
    ],

    'testNotEnrolledCSEntity' => [
        'amount' => 50000,
        'pares_status' => null,
        'status' => 'captured',
        'entity' => 'cybersource',
    ],

    'testPaymentRefund' => [
        'reason_code' => 100,
        'received' => true,
        'amount' => 50000,
        'commerce_indicator' => null,
        'pares_status' => null,
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
    ],

    'testAuthorizeFailedPayment' => [
        'action' => 'authorize',
        'received' => true,
        'refund_id' => null,
        'auth_data' => null,
        'amount' => 50000,
        'pares_status' => 'Y',
        'status' => 'authorized',
        'xid' => 'eW5DZTVGTkVaRWF3VnowSXYzNzA=',
        'eci' => '05',
        'cavv' => 'AAABAWFlmQAAAABjRWWZEEFgFz+=',
        'ref' => '4661468455476856801012',
        'capture_ref' => null,
        'reason_code' => 100
    ]
];