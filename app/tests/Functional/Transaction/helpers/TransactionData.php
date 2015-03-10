<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;

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
        'gateway_fee' => 0,
        'api_fee' => 0,
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
        'credit' => 48876,
        'fee' => 1124,
        'gateway_fee' => 0,
        'api_fee' => 0,
        'balance' => 1048876,
        'escrow_balance' => 1048876,
        'merchant_id' => '10000000000000',
        'pricing_rule_id' => '1nvp2XPMmaRLxb',
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
        'gateway_fee' => 0,
        'api_fee' => 0,
        'balance' => 998876,
        'escrow_balance' => 998876,
        'merchant_id' => '10000000000000',
        'pricing_rule_id' => null,
        'channel' => 'kotak',
    ],

    'txnDataAfterCapturingAtomPayment' => [
        'entity' => 'transaction',
        'type' => 'payment',
        'amount' => 50000,
        'currency' => 'INR',
        'debit' => 0,
        'credit' => 48595,
        'fee' => 1405,
        'gateway_fee' => 1405,
        'api_fee' => 0,
        'balance' => 1048595,
        'escrow_balance' => 1048595,
        'merchant_id' => '10000000000000',
        'pricing_rule_id' => '1zD0BXpeOyaqpB',
        'channel' => 'atom',
    ],

    'txnDataAfterRefundingAtomPayment' => [
        'entity' => 'transaction',
        'type' => 'refund',
        'amount' => 50000,
        'currency' => 'INR',
        'debit' => 50000,
        'credit' => 0,
        'fee' => 0,
        'gateway_fee' => 0,
        'api_fee' => 0,
        'balance' => 998595,
        'escrow_balance' => 998595,
        'merchant_id' => '10000000000000',
        'pricing_rule_id' => null,
        'channel' => 'atom',
    ],

    'txnDataAfterPaymentOnSharedTerminal' => [
        'type' => 'payment',
        'amount' => 50000,
        'fee' => 1405,
        'gateway_fee' => 983,
        'api_fee' => 422,
        'pricing_rule_id' => '1zD0BXpeOyaqpB',
        'debit' => 0,
        'credit' => 48595,
        'currency' => 'INR',
        'balance' => 48595,
        'escrow_balance' => 1048595,
        'channel' => 'kotak',
        'settled' => false,
        'settlement_id' => null,
        'entity' => 'transaction',
    ],
];