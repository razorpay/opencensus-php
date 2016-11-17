<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testAddAdjustment' => [
        'request' => [
            'content' => [
                'amount' => 100,
                'description' => 'random desc',
                'currency' => 'INR',
            ],
            'url' => '/adjustments',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'amount' => 100,
                'description' => 'random desc',
                'channel' => 'kotak',
                'currency' => 'INR',
            ],
        ],
    ],

   'testAddAdjustmentWithoutUpdatingEscrowBalance' => [
        'request' => [
            'content' => [
                'amount' => 100,
                'description' => 'random desc',
                'currency' => 'INR',
                'update_escrow' => '0',
            ],
            'url' => '/adjustments',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'amount' => 100,
                'description' => 'random desc',
                'channel' => 'kotak',
                'currency' => 'INR',
            ],
        ],
    ],

    'txnDataAfterAddingAdjustment' => [
        'entity' => 'transaction',
        'type' => 'adjustment',
        'amount' => 100,
        'currency' => 'INR',
        'debit' => 0,
        'credit' => 100,
        'fee' => 0,
        'service_tax' => 0,
        'gateway_fee' => 0,
        'api_fee' => 0,
        'gratis' => false,
        'balance' => 1000100,
        'escrow_balance' => 1000100,
        'merchant_id' => '10000000000000',
        'pricing_rule_id' => null,
        'channel' => 'kotak',
    ],

    'txnDataAfterAddingAdjWithNoEscrowUpdate' => [
        'entity' => 'transaction',
        'type' => 'adjustment',
        'amount' => 100,
        'currency' => 'INR',
        'debit' => 0,
        'credit' => 100,
        'fee' => 0,
        'gateway_fee' => 0,
        'api_fee' => 0,
        'gratis' => false,
        'balance' => 1000100,
        'escrow_balance' => 1000000,
        'merchant_id' => '10000000000000',
        'pricing_rule_id' => null,
        'channel' => 'kotak',
    ],

    'testGetAdjustment' => [
        'request' => [
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'adjustment',
                'amount' => 100,
                'description' => 'random desc',
                'currency' => 'INR',
            ]
        ]
    ],

    'txnDataAfterCapturingPayment' => [
        'entity' => 'transaction',
        'type' => 'payment',
        'amount' => 50000,
        'currency' => 'INR',
        'debit' => 0,
        'credit' => 48850,
        'fee' => 1150,
        'service_tax' => 150,
        'gateway_fee' => 0,
        'api_fee' => 0,
        'balance' => 1048850,
        'escrow_balance' => 1048850,
        'merchant_id' => '10000000000000',
        'pricing_rule_id' => null,
        'channel' => 'kotak',
    ],

    'testTransactionCreateForOldPayment' => [
        'entity' => 'transaction',
        'type' => 'payment',
        'amount' => 1000000,
        'currency' => 'INR',
        'debit' => 0,
        'credit' => 1000000,
        'fee' => 0,
        'service_tax' => 0,
        'gateway_fee' => 0,
        'api_fee' => 0,
        'merchant_id' => '10000000000000',
        'pricing_rule_id' => '1ZeroPricingR1',
        'channel' => 'kotak',
    ],

    'txnDataAfterRefundingPayment' => [
        'entity' => 'transaction',
        'type' => 'refund',
        'amount' => 50000,
        'currency' => 'INR',
        'debit' => 50000,
        'credit' => 0,
        'fee' => 0,
        'service_tax' => 0,
        'gateway_fee' => 0,
        'api_fee' => 0,
        'gratis' => false,
        'balance' => 998850,
        'escrow_balance' => 998850,
        'merchant_id' => '10000000000000',
        'pricing_rule_id' => null,
        'channel' => 'kotak',
    ],
];
