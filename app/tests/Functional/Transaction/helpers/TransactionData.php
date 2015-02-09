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
    ],

    'txnDataAfterCapturingPayment' => [
        'entity' => 'transaction',
        'type' => 'payment',
        'amount' => 50000,
        'currency' => 'INR',
        'debit' => 0,
        'credit' => 38764,
        'fee' => 11236,
        'gateway_fee' => 0,
        'api_fee' => 0,
        'balance' => 1038764,
        'escrow_balance' => 1038764,
        'merchant_id' => '10000000000000',
        'pricing_rule_id' => '1nvp2XPMmaRLxb',
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
        'balance' => 988764,
        'escrow_balance' => 988764,
        'merchant_id' => '10000000000000',
        'pricing_rule_id' => null,
    ],

    'txnDataAfterCapturingAtomPayment' => [
        'entity' => 'transaction',
        'type' => 'payment',
        'amount' => 50000,
        'currency' => 'INR',
        'debit' => 0,
        'credit' => 38764,
        'fee' => 11236,
        'gateway_fee' => 11236,
        'api_fee' => 0,
        'balance' => 1038764,
        'escrow_balance' => 1038764,
        'merchant_id' => '10000000000000',
        'pricing_rule_id' => '1nvp2XPMmaRLxb',
    ]
];