<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testAddAdjustment' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'amount'      => 100,
                'description' => 'random desc',
                'currency'    => 'INR',
            ],
            'url' => '/adjustments',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'amount'      => 100,
                'description' => 'random desc',
                'channel'     => 'kotak',
                'currency'    => 'INR',
            ],
        ],
    ],

    'testAddReverseAdjustment' => [
        'request' => [
            'content' => [
            ],
            'url'    => '/adjustments/reversal',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
            ],
        ],

    ],

    'txnDataAfterAddingAdjustment' => [
        'entity'          => 'transaction',
        'type'            => 'adjustment',
        'amount'          => 100,
        'currency'        => 'INR',
        'debit'           => 0,
        'credit'          => 100,
        'fee'             => 0,
        'service_tax'     => 0,
        'tax'             => 0,
        'gateway_fee'     => 0,
        'api_fee'         => 0,
        'gratis'          => false,
        'balance'         => 1000100,
        'merchant_id'     => '10000000000000',
        'pricing_rule_id' => null,
        'channel'         => 'kotak',
    ],

    'txnDataAfterAddingAdjWithNoEscrowUpdate' => [
        'entity'          => 'transaction',
        'type'            => 'adjustment',
        'amount'          => 100,
        'currency'        => 'INR',
        'debit'           => 0,
        'credit'          => 100,
        'fee'             => 0,
        'gateway_fee'     => 0,
        'api_fee'         => 0,
        'gratis'          => false,
        'balance'         => 1000100,
        'merchant_id'     => '10000000000000',
        'pricing_rule_id' => null,
        'channel'         => 'kotak',
    ],

    'testGetAdjustment' => [
        'request' => [
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity'      => 'adjustment',
                'amount'      => 100,
                'description' => 'random desc',
                'currency'    => 'INR',
            ]
        ]
    ],

    'txnDataAfterCapturingPayment' => [
        'entity'          => 'transaction',
        'type'            => 'payment',
        'amount'          => 50000,
        'currency'        => 'INR',
        'debit'           => 0,
        'credit'          => 49000,
        'fee'             => 1000,
        'service_tax'     => 0,
        'tax'             => 0,
        'gateway_fee'     => 0,
        'api_fee'         => 0,
        'balance'         => 1049000,
        'merchant_id'     => '10000000000000',
        'pricing_rule_id' => null,
        'channel'         => 'kotak',
    ],

    'testTransactionCreateForOldPayment' => [
        'entity'          => 'transaction',
        'type'            => 'payment',
        'amount'          => 1000000,
        'currency'        => 'INR',
        'debit'           => 0,
        'credit'          => 1000000,
        'fee'             => 0,
        'service_tax'     => 0,
        'tax'             => 0,
        'gateway_fee'     => 0,
        'api_fee'         => 0,
        'merchant_id'     => '10000000000000',
        'pricing_rule_id' => '1ZeroPricingR1',
        'channel'         => 'kotak',
    ],

    'txnDataAfterRefundingPayment' => [
        'entity'          => 'transaction',
        'type'            => 'refund',
        'amount'          => 50000,
        'currency'        => 'INR',
        'debit'           => 50000,
        'credit'          => 0,
        'fee'             => 0,
        'service_tax'     => 0,
        'tax'             => 0,
        'gateway_fee'     => 0,
        'api_fee'         => 0,
        'gratis'          => false,
        'balance'         => 999000,
        'merchant_id'     => '10000000000000',
        'pricing_rule_id' => null,
        'channel'         => 'kotak',
    ],

    'txnDataAfterDisputingPayment' => [
        'entity'          => 'transaction',
        'type'            => 'adjustment',
        'amount'          => 1000000,
        'currency'        => 'INR',
        'debit'           => 1000000,
        'credit'          => 0,
        'fee'             => 0,
        'service_tax'     => 0,
        'gateway_fee'     => 0,
        'api_fee'         => 0,
        'gratis'          => false,
        'balance'         => 976400,
        'merchant_id'     => '10000000000000',
        'pricing_rule_id' => null,
        'channel'         => 'kotak',
    ],

    'txnDataAfterDisputingPaymentWithoutDeduct' => [
        'entity'          => 'transaction',
        'type'            => 'payment',
        'amount'          => 1000000,
        'currency'        => 'INR',
        'debit'           => 0,
        'credit'          => 976400,
        'fee'             => 23600,
        'service_tax'     => 3600,
        'gateway_fee'     => 0,
        'api_fee'         => 0,
        'gratis'          => false,
        'balance'         => 1976400,
        'merchant_id'     => '10000000000000',
        'pricing_rule_id' => null,
        'channel'         => 'kotak',
    ],
];
