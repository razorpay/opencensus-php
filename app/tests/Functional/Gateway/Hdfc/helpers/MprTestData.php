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
                'amount' => 28500,
                'status' => 'failed',
                'channel' => 'kotak',
                'entity' => 'settlement',
            ],
            [
                'merchant_id' => '10000000000000',
                'amount' => 4386000,
                'status' => 'failed',
                'channel' => 'kotak',
                'failure_reason' => 'Remitt info: some random info',
                'entity' => 'settlement',
            ],
        ]
    ]
];