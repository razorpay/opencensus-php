<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

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
                'service_tax' => 0,
                'entity' => 'settlement',
            ],
            [
                'merchant_id' => '10000000000000',
                'amount' => 4385500,
                'fees' => 114500,
                'status' => 'failed',
                'channel' => 'kotak',
                'service_tax' => 14500,
                'failure_reason' => 'Remitt info: some random info',
                'entity' => 'settlement',
            ],
        ]
    ],
];
