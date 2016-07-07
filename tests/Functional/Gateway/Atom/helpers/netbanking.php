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
        'fee' => 1438,
        'pricing_rule_id' => '1zD0BXpeOyaqpB',
        'debit' => 0,
        'credit' => 48562,
        'currency' => 'INR',
        'balance' => 48562,
        'gateway_fee' => 1006,
        'api_fee' => 432,
        'escrow_balance' => 1048562,
        'channel' => 'kotak',
        'settled' => false,
        'settlement_id' => null,
        'entity' => 'transaction',
    ],

    'testCardPaymentOnSharedTerminal' => [
        'entity' => 'transaction',
        'type' => 'payment',
        'amount' => 50000,
        'fee' => 1150,
        'pricing_rule_id' => '1nvp2XPMmaRLxb',
        'debit' => 0,
        'credit' => 48850,
        'currency' => 'INR',
        'balance' => 48850,
        'gateway_fee' => 489,
        'api_fee' => 661,
        'escrow_balance' => 1048850,
        'channel' => 'kotak',
        'settled' => false,
        'settlement_id' => null,
        'entity' => 'transaction',
    ],


    'testDebitCardPaymentOnSharedTerminal' => [
        'entity' => 'transaction',
        'type' => 'payment',
        'amount' => 200000,
        'fee' => 4600,
        'pricing_rule_id' => '1nvp2XPMmaRLxb',
        'debit' => 0,
        'credit' => 195400,
        'currency' => 'INR',
        'balance' => 195400,
        'gateway_fee' => 1955,
        'api_fee' => 2645,
        'escrow_balance' => 1195400,
        'channel' => 'kotak',
        'settled' => false,
        'settlement_id' => null,
        'entity' => 'transaction',
    ],

    'testDebitCardPaymentOnSharedTerminalWithGreaterThan2000Amount' => [
        'entity' => 'transaction',
        'type' => 'payment',
        'amount' => 200001,
        'fee' => 4602,
        'pricing_rule_id' => '1nvp2XPMmaRLxb',
        'debit' => 0,
        'credit' => 195399,
        'currency' => 'INR',
        'balance' => 195399,
        'gateway_fee' => 2530,
        'api_fee' => 2072,
        'escrow_balance' => 1195399,
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
