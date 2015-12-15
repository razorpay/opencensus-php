<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'testUploadMpr' => [
        'request' => [
            'content' => [
                'gateway' => 'hdfc',
            ],
            'url' => '/gateway/mpr/reconcile',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
            ]
        ],
    ],

    'testUploadMprSettlementData' => [
        'entity' => 'collection',
        'count' => 2,
        'admin' => true,
        'items' => [
            [
                'merchant_id' => '1ApiFeeAccount',
                'amount' => 29000,
                'fees' => 0,
                'status' => 'failed',
                'channel' => 'kotak',
                'entity' => 'settlement',
            ],
            [
                'merchant_id' => '10000000000000',
                'amount' => 4385500,
                'fees' => 114500,
                'status' => 'failed',
                'channel' => 'kotak',
                'failure_reason' => 'Remitt info: some random info',
                'entity' => 'settlement',
            ],
        ]
    ],
    'testUploadMprSetlTxnsData' => [
        'entity' => 'collection',
        'count' => 2,
        'items' => [
            [
               'type' => 'settlement',
               'merchant_id' => '1ApiFeeAccount',
               'amount' => 29000,
               'fee' => 0,
               'service_tax' => 0,
               'debit' => 29000,
               'credit' => 0,
               'currency' => "INR",
               'balance' => 1000000,
               'gateway_fee' => 0,
               'api_fee' => 0,
               'escrow_balance' => 1029000,
               'channel' => 'kotak',
               'entity' => 'transaction',
           ],
           [
               'type' => 'settlement',
               'merchant_id' => '10000000000000',
               'amount' => 4385500,
               'fee' => 0,
               'service_tax' => 14500,
               'debit' => 4385500,
               'credit' => 0,
               'currency' => "INR",
               'balance' => 1000000,
               'gateway_fee' => 0,
               'api_fee' => 0,
               'escrow_balance' => 1000000,
               'channel' => 'kotak',
               'entity' => 'transaction',
           ]

        ],
    ],
];
