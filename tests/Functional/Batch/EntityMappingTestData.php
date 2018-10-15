<?php
return [
    'testCreateEntityMappingBatchQueued' => [
        'request' => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'   => 'entity_mapping',
                'config' => [
                    'entity_from_type' => 'admin',
                    'entity_to_type'   => 'merchant',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'entity_mapping',
                'status'           => 'created',
                'total_count'      => 3,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],
    'testCreateEntityMappingBatchStatus' => [
        'request' => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'   => 'entity_mapping',
                'config' => [
                    'entity_from_type' => 'admin',
                    'entity_to_type'   => 'merchant',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'entity_mapping',
                'status'           => 'created',
                'total_count'      => 3,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ]
];
