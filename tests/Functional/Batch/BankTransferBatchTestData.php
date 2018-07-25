<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testCreateBatchOfBankTransferTypeQueued' => [
        'request' => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'bank_transfer',
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'bank_transfer',
                'status'           => 'created',
                'total_count'      => 1,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'amount'           => 50000,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],

    'testCreateBatchOfBankTransferTypeStatus' => [
        'request' => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'bank_transfer',
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'bank_transfer',
                'status'           => 'created',
                'total_count'      => 1,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'amount'           => 50000,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],
];
