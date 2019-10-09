<?php

use RZP\Error\ErrorCode;
use RZP\Models\Batch\Header;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testBulkSubmerchantAssign' => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'     => 'submerchant_assign',
            ],
        ],
        'response' => [
            'content' => [
                'entity'        => 'batch',
                'type'          => 'submerchant_assign',
                'status'        => 'created',
                'total_count'   => 4,
                'success_count' => 0,
                'failure_count' => 0,
                'attempts'      => 0,
            ],
        ],
    ],
    'testBulkAssignValidateFile' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'     => 'submerchant_assign',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 4,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Header::SUBMERCHANT_ID   => '100000Razorpay',
                        Header::TERMINAL_ID      => '100000EbsTrmnl',
                    ],
                    [
                        Header::SUBMERCHANT_ID   => '10NodalAccount',
                        Header::TERMINAL_ID      => '100HitachiTmnl',
                    ],
                    [
                        Header::SUBMERCHANT_ID   => '10000000000000',
                        Header::TERMINAL_ID      => '100HitachiTmnl',
                    ],
                ],
            ],
        ],
    ],
    'testBulkSubmerchantAssignViaBatchService' => [
        'request'   => [
            'url'     => '/submerchant/assign/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'idempotency_key'    => 'batch_abc123'
                ],
                [
                    'idempotency_key'    => 'batch_abc124'
                ],
                [
                    'idempotency_key'    => 'batch_abc125'
                ],
                [
                    'idempotency_key'    => 'batch_abc125'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 4,
                'items' => [
                    [
                        'batch_id'        => 'C0zv9I46W4wiOq',
                        'idempotency_key' => 'batch_abc123',
                        'status'          => 'SUCCESS',
                        'failure_reason'  => null,
                    ],
                    [
                        'batch_id'        => 'C0zv9I46W4wiOq',
                        'idempotency_key' => 'batch_abc124',
                        'status'          => 'SUCCESS',
                        'failure_reason'  => null,
                    ],
                    [
                        'batch_id'        => 'C0zv9I46W4wiOq',
                        'idempotency_key' => 'batch_abc125',
                        'status'          => 'SUCCESS',
                        'failure_reason'  => null,
                    ],
                    [
                        'batch_id'        => 'C0zv9I46W4wiOq',
                        'idempotency_key' => 'batch_abc125',
                        'error'           => [
                            'description' => 'Sub-Merchant already assigned to terminal',
                            'code'        => 'BAD_REQUEST_ERROR',
                        ],
                        'http_status_code' => 400,
                    ]
                ],
            ],
        ],
    ],
    'testBulkSubmerchantAssignViaBatchServiceWithoutBatchId' => [
        'request'   => [
            'url'     => '/submerchant/assign/bulk',
            'method'  => 'POST',
            'content' => [
                [ 'idempotency_key'    => 'batch_abc01' ],
                [ 'idempotency_key'    => 'batch_abc02' ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Batch Id not present',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
    'testBulkSubmerchantAssignViaBatchServiceForExcessCount' => [
        'request'   => [
            'url'     => '/submerchant/assign/bulk',
            'method'  => 'POST',
            'content' => [
                [ 'idempotency_key'    => 'batch_abc01' ],
                [ 'idempotency_key'    => 'batch_abc02' ],
                [ 'idempotency_key'    => 'batch_abc03' ],
                [ 'idempotency_key'    => 'batch_abc04' ],
                [ 'idempotency_key'    => 'batch_abc05' ],
                [ 'idempotency_key'    => 'batch_abc06' ],
                [ 'idempotency_key'    => 'batch_abc07' ],
                [ 'idempotency_key'    => 'batch_abc08' ],
                [ 'idempotency_key'    => 'batch_abc09' ],
                [ 'idempotency_key'    => 'batch_abc10' ],
                [ 'idempotency_key'    => 'batch_abc11' ],
                [ 'idempotency_key'    => 'batch_abc12' ],
                [ 'idempotency_key'    => 'batch_abc13' ],
                [ 'idempotency_key'    => 'batch_abc14' ],
                [ 'idempotency_key'    => 'batch_abc15' ],
                [ 'idempotency_key'    => 'batch_abc16' ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Current batch size 16, max limit of Bulk Submerchant Assign is 15',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
];
