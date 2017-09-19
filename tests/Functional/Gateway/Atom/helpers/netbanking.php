<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testNetbankingPaymentAuthorize' => [
        'request' => [
            'content' => []
        ],
        'response' => [
            'content' => [
            ]
        ],
    ],

    'testNetbankingPaymentCapture' => [
        'entity' => 'payment',
        'status' => 'captured',
        'notes' => [],
        'currency' => 'INR',
        'amount_refunded' => 0,
        'amount' => 5000,
        'status' => 'captured',
        'refund_status' => null,
    ],

    'testNetbankingPaymentRefund' => [
        'entity' => 'refund',
        'amount' => 1000000,
        'currency' => 'INR',
    ],

    'testAtomCardPayment' => [
        'entity' => 'payment',
        'amount' => 50000,
        'status' => 'captured',
        'refund_status' => null,
    ],

    'testNBPaymentFailureAtBank' => [
        'request' => [
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED
        ],
        'success' => false,
    ],

    'testNBPaymentFailureAtBankEntity' => [
        'amount' => 5000,
        'entity' => 'payment',
        'status' => 'failed',
        'gateway' => 'atom',
        'captured_at' => null,
        'error_code' => PublicErrorCode::BAD_REQUEST_ERROR,
        'error_description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED,
    ],

    'testNBPaymentOnSharedTerminal' => [
        'entity' => 'transaction',
        'type' => 'payment',
        'amount' => 50000,
        'fee' => 1476,
        'pricing_rule_id' => null,
        'debit' => 0,
        'credit' => 48524,
        'currency' => 'INR',
        'balance' => 48524,
        'gateway_fee' => 1033,
        'api_fee' => 443,
//        'escrow_balance' => 1048562,
        'channel' => 'kotak',
        'settled' => false,
        'settlement_id' => null,
        'entity' => 'transaction',
    ],

    'testCardPaymentOnSharedTerminal' => [
        'entity' => 'transaction',
        'type' => 'payment',
        'amount' => 50000,
        'fee' => 1000,
        'pricing_rule_id' => null,
        'debit' => 0,
        'credit' => 49000,
        'currency' => 'INR',
        'balance' => 49000,
        'gateway_fee' => 502,
        'api_fee' => 498,
//        'escrow_balance' => 1048850,
        'channel' => 'kotak',
        'settled' => false,
        'settlement_id' => null,
        'entity' => 'transaction',
    ],


    'testDebitCardPaymentOnSharedTerminal' => [
        'entity' => 'transaction',
        'type' => 'payment',
        'amount' => 200000,
        'fee' => 4000,
        'pricing_rule_id' => null,
        'debit' => 0,
        'credit' => 196000,
        'currency' => 'INR',
        'balance' => 196000,
        'gateway_fee' => 2006,
        'api_fee' => 1994,
//        'escrow_balance' => 1195400,
        'channel' => 'kotak',
        'settled' => false,
        'settlement_id' => null,
        'entity' => 'transaction',
    ],

    'testDebitCardPaymentOnSharedTerminalWithGreaterThan2000Amount' => [
        'entity' => 'transaction',
        'type' => 'payment',
        'amount' => 200001,
        'fee' => 4721,
        'pricing_rule_id' => null,
        'debit' => 0,
        'credit' => 195280,
        'currency' => 'INR',
        'balance' => 195280,
        'gateway_fee' => 2596,
        'api_fee' => 2125,
//        'escrow_balance' => 1195399,
        'channel' => 'kotak',
        'settled' => false,
        'settlement_id' => null,
        'entity' => 'transaction',
    ],

    'testMockOnLiveMode' => [
        'request' => [
            'content' => []
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::SERVER_ERROR,
                    'description' => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class' => 'RZP\Exception\LogicException',
            'internal_error_code' => ErrorCode::SERVER_ERROR_LOGICAL_ERROR,
        ],
    ],
];
