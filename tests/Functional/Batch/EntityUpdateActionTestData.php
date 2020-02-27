<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testEntityUpdateActionBatchCreate' => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'   => 'entity_update_action',
                'name'   => 'file',
                'config' => [
                    'batch_action' => 'update_entity',
                    'entity'       => 'merchant_detail',
                ],
            ],
        ],
        'response' => [
            'content' => [

                'entity'               => "batch",
                'name'                 => "file",
                'type'                 => "entity_update_action",
                'status'               => "created",
                'total_count'          => 2,
                'success_count'        => 0,
                'failure_count'        => 0,
                'processed_count'      => 0,
                'processed_percentage' => 0,
                'attempts'             => 0,
                'amount'               => 0,
                'processed_amount'     => 0,
                'processed_at'         => null,
            ],
        ],
    ],

    'testEntityUpdateActionBatchCreateInvalidAction' => [
        'request'   => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'   => 'entity_update_action',
                'name'   => 'file',
                'config' => [
                    'batch_action' => 'update_enty',
                    'entity'       => 'merchant_detail',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The input batch action is not supported for the merchant',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_BATCH_ACTION_NOT_SUPPORTED,
        ],
    ],

    'testEntityUpdateActionBatchCreateInvalidEntity' => [
        'request'   => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'   => 'entity_update_action',
                'name'   => 'file',
                'config' => [
                    'batch_action' => 'update_entity',
                    'entity'       => 'merchant_detl',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The input batch action entity is not supported for the merchant',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_BATCH_ACTION_ENTITY_NOT_SUPPORTED,
        ],
    ],
];
