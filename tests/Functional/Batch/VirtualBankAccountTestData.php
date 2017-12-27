<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testCreateBatchOfVirtualBankAccountTypeQueued' => [
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'virtual_bank_account',
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'virtual_bank_account',
                'status'           => 'created',
                'total_count'      => 6,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'amount'           => 0,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],

    'testCreateBatchOfVirtualBankAccountTypeStatus' => [
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'virtual_bank_account',
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'virtual_bank_account',
                'status'           => 'created',
                'total_count'      => 6,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'amount'           => 0,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],
];
