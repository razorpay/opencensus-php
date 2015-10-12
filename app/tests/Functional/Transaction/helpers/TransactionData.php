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
        'credit' => 48860,
        'fee' => 1140,
        'gateway_fee' => 0,
        'api_fee' => 0,
        'balance' => 1048860,
        'escrow_balance' => 1048860,
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
        'balance' => 998860,
        'escrow_balance' => 998860,
        'merchant_id' => '10000000000000',
        'pricing_rule_id' => null,
        'channel' => 'kotak',
    ],

    'txnDataAfterPaymentOnSharedTerminal' => [
        'type' => 'payment',
        'amount' => 50000,
        'fee' => 1425,
        'gateway_fee' => 998,
        'api_fee' => 427,
        'pricing_rule_id' => '1zD0BXpeOyaqpB',
        'debit' => 0,
        'credit' => 48575,
        'currency' => 'INR',
        'balance' => 48575,
        'escrow_balance' => 1048575,
        'channel' => 'kotak',
        'settled' => false,
        'settlement_id' => null,
        'entity' => 'transaction',
    ],
];