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
        'count' => 1,
        'admin' => true,
        'items' => [
            [
                'merchant_id' => '10000000000000',
                'amount' => 4387640,
                'status' => 'failed',
                'channel' => 'kotak',
                'failure_reason' => 'Return reason: ',
                'entity' => 'settlement',
            ]
        ]
    ]
];