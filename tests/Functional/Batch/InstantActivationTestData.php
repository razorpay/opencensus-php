<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateBatchOfInstantActivation' => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'instant_activation',
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'instant_activation',
                'status'           => 'created',
                'total_count'      => 3,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'amount'           => 0,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],

    'testVerifyBatchDataMigration' => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'instant_activation',
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'batch',
                'type'         => 'instant_activation',
                'status'       => 'created',
                'processed_at' => null,
            ],
        ],
    ],

    'testVerifyBatchForGreylistActivation' => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'instant_activation',
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'batch',
                'type'         => 'instant_activation',
                'status'       => 'created',
                'processed_at' => null,
            ],
        ],
    ],

    'testVerifyBatchForSuccessAndFailureCount' => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'instant_activation',
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'batch',
                'type'         => 'instant_activation',
                'status'       => 'created',
                'processed_at' => null,
                'total_count'  => 4,
            ],
        ],
    ],
];
