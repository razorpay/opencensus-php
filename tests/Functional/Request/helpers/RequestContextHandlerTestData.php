<?php

namespace RZP\Tests\Functional\Invoice;

return [

    'testContextTrackIdGenerated' => [
        'request' => [
            'url' => '/iins/iin_npci_rupay/process',
            'method' => 'post',
            'content' => [
                [
                    'row'   => 'ABHY065000160726100060726199916S010101E&M01D356IN140513000000N',
                    'idempotent_id' => 'batch_abc123'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'batch_id'        => 'C0zv9I46W4wiOq',
                        'idempotent_id'   => 'batch_abc123',
                        'status'          => 1,
                    ]
                ],
            ],
        ],
   ],
    'testContextTraceIdGenerated' => [
        'request' => [
            'url' => '/iins/iin_npci_rupay/process',
            'method' => 'post',
            'content' => [
                [
                    'row'   => 'ABHY065000160726100060726199916S010101E&M01D356IN140513000000N',
                    'idempotent_id' => 'batch_abc123'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'batch_id'        => 'C0zv9I46W4wiOq',
                        'idempotent_id'   => 'batch_abc123',
                        'status'          => 1,
                    ]
                ],
            ],
        ],
    ],
];
