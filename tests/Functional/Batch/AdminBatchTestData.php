<?php
return [
    'testCreateAdminBatch' => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'admin_batch',
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'admin_batch',
                'status'           => 'created',
                'total_count'      => 4,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],

    'testCreateAdminBatchInvalidFileEntries' => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'admin_batch',
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'admin_batch',
                'status'           => 'created',
                'total_count'      => 4,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],
];
