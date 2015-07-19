<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;

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
        'fee' => 1425,
        'pricing_rule_id' => '1zD0BXpeOyaqpB',
        'debit' => 0,
        'credit' => 48575,
        'currency' => 'INR',
        'balance' => 48575,
        'gateway_fee' => 998,
        'api_fee' => 427,
        'escrow_balance' => 1048575,
        'channel' => 'kotak',
        'settled' => false,
        'settlement_id' => null,
        'entity' => 'transaction',
    ],

    'testCardPaymentOnSharedTerminal' => [
        'entity' => 'transaction',
        'type' => 'payment',
        'amount' => 50000,
        'fee' => 1140,
        'pricing_rule_id' => '1nvp2XPMmaRLxb',
        'debit' => 0,
        'credit' => 48860,
        'currency' => 'INR',
        'balance' => 48860,
        'gateway_fee' => 485,
        'api_fee' => 655,
        'escrow_balance' => 1048860,
        'channel' => 'kotak',
        'settled' => false,
        'settlement_id' => null,
        'entity' => 'transaction',
    ],


    'testDebitCardPaymentOnSharedTerminal' => [
        'entity' => 'transaction',
        'type' => 'payment',
        'amount' => 200000,
        'fee' => 4560,
        'pricing_rule_id' => '1nvp2XPMmaRLxb',
        'debit' => 0,
        'credit' => 195440,
        'currency' => 'INR',
        'balance' => 195440,
        'gateway_fee' => 1938,
        'api_fee' => 2622,
        'escrow_balance' => 1195440,
        'channel' => 'kotak',
        'settled' => false,
        'settlement_id' => null,
        'entity' => 'transaction',
    ],

    'testDebitCardPaymentOnSharedTerminalWithGreaterThan2000Amount' => [
        'entity' => 'transaction',
        'type' => 'payment',
        'amount' => 200001,
        'fee' => 4562,
        'pricing_rule_id' => '1nvp2XPMmaRLxb',
        'debit' => 0,
        'credit' => 195439,
        'currency' => 'INR',
        'balance' => 195439,
        'gateway_fee' => 2508,
        'api_fee' => 2054,
        'escrow_balance' => 1195439,
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
