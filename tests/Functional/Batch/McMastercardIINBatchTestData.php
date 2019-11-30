<?php

use RZP\Models\Batch\Header;

return [
    'testBulkIinMcUpdate'          => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'     => 'iin_mc_mastercard',
            ],
        ],
        'response' => [
            'content' => [
                'entity'        => 'batch',
                'type'          => 'iin_mc_mastercard',
                'status'        => 'created',
                'total_count'   => 3,
                'success_count' => 0,
                'failure_count' => 0,
                'attempts'      => 0,
            ],
        ],
    ],
    'testBulkIinViaBatchService' => [
        'request'   => [
            'url'     => '/iins/iin_mc_mastercard/process',
            'method'  => 'POST',
            'content' => [

            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 3,
                'items' => [
                    [
                        'batch_id'        => 'C0zv9I46W4wiOq',
                        'idempotent_id'   => 'batch_abc123',
                        'status'          => 1,
                    ],
                    [
                        'batch_id'        => 'C0zv9I46W4wiOq',
                        'idempotent_id'   => 'batch_abc124',
                        'status'          => 1,
                    ],
                    [
                        'batch_id'        => 'C0zv9I46W4wiOq',
                        'idempotent_id'   => 'batch_abc125',
                        'error'           => [
                            'description' => 'The type field is required.',
                            'code'        => 'BAD_REQUEST_ERROR',
                        ],
                        'http_status_code' => 400,
                    ]
                ],
            ],
        ],

    ],
];
