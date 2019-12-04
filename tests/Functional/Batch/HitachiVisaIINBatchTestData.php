<?php

use RZP\Models\Batch\Header;

return [
    'testBulkIinVisaUpdate'          => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'     => 'iin_hitachi_visa',
            ],
        ],
        'response' => [
            'content' => [
                'entity'        => 'batch',
                'type'          => 'iin_hitachi_visa',
                'status'        => 'created',
                'total_count'   => 21,
                'success_count' => 0,
                'failure_count' => 0,
                'attempts'      => 0,
            ],
        ],
    ],
    'testBulkIinViaBatchService' => [
        'request'   => [
            'url'     => '/iins/iin_hitachi_visa/process',
            'method'  => 'POST',
            'content' => [
                [
                    'idempotent_id'    => 'batch_abc123'
                ],
                [
                    'idempotent_id'    => 'batch_abc124'
                ],
                [
                    'idempotent_id'    => 'batch_abc125'
                ],
                [
                    'idempotent_id'    => 'batch_abc125'
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
                        'status'          => 1,
                    ],
                    [
                        'batch_id'        => 'C0zv9I46W4wiOq',
                        'idempotent_id'   => 'batch_abc125',
                        'error'           => [
                            'description' => 'Invalid Country Code: XY',
                            'code'        => 'BAD_REQUEST_ERROR',
                        ],
                        'http_status_code' => 400,
                    ]
                ],
            ],
        ],

    ],
];
