<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;

return [
    'testNetBankingPaymentAuthorize' => [
        'request' => [
            'content' => []
        ],
        'response' => [
            'content' => [
            ]
        ],
    ],

    'testNetBankingPaymentCapture' => [
        'entity' => 'payment',
        'status' => 'captured',
        'notes' => [],
        'currency' => 'INR',
        'amount_refunded' => 0,
        'amount' => 5000,
        'status' => 'captured',
        'refund_status' => null,
    ],

    'testNetBankingPaymentRefund' => [
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
            'class' => 'EE\Exception\GatewayErrorException',
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
        'fee' => 1405,
        'pricing_rule_id' => '1zD0BXpeOyaqpB',
        'debit' => 0,
        'credit' => 48595,
        'currency' => 'INR',
        'balance' => 48595,
        'gateway_fee' => 984,
        'api_fee' => 421,
        'escrow_balance' => 1048595,
        'channel' => 'kotak',
        'settled' => false,
        'settlement_id' => null,
        'entity' => 'transaction',
    ],

    'testCardPaymentOnSharedTerminal' => [
        'entity' => 'transaction',
        'type' => 'payment',
        'amount' => 50000,
        'fee' => 1124,
        'pricing_rule_id' => '1nvp2XPMmaRLxb',
        'debit' => 0,
        'credit' => 48876,
        'currency' => 'INR',
        'balance' => 48876,
        'gateway_fee' => 478,
        'api_fee' => 646,
        'escrow_balance' => 1048876,
        'channel' => 'kotak',
        'settled' => false,
        'settlement_id' => null,
        'entity' => 'transaction',
    ],


    'testDebitCardPaymentOnSharedTerminal' => [
        'entity' => 'transaction',
        'type' => 'payment',
        'amount' => 200000,
        'fee' => 4495,
        'pricing_rule_id' => '1nvp2XPMmaRLxb',
        'debit' => 0,
        'credit' => 195505,
        'currency' => 'INR',
        'balance' => 195505,
        'gateway_fee' => 1911,
        'api_fee' => 2584,
        'escrow_balance' => 1195505,
        'channel' => 'kotak',
        'settled' => false,
        'settlement_id' => null,
        'entity' => 'transaction',
    ],

    'testDebitCardPaymentOnSharedTerminalWithGreaterThan2000Amount' => [
        'entity' => 'transaction',
        'type' => 'payment',
        'amount' => 200001,
        'fee' => 4497,
        'pricing_rule_id' => '1nvp2XPMmaRLxb',
        'debit' => 0,
        'credit' => 195504,
        'currency' => 'INR',
        'balance' => 195504,
        'gateway_fee' => 2474,
        'api_fee' => 2023,
        'escrow_balance' => 1195504,
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
            'class' => 'EE\Exception\LogicException',
            'internal_error_code' => ErrorCode::SERVER_ERROR_LOGICAL_ERROR,
        ],
    ],
];
