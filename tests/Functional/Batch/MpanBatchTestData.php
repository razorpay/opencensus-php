<?php
use RZP\Models\Batch\Header;
return [
    'testMpanCreationBatch'      =>      [
        'request'   => [
            'url'       =>  '/admin/batches',
            'method'    =>  'post',
            'content'   =>  [
                'type'      =>  'mpan',
            ],
        ],
        'response'  => [
            'content' => [
                'entity'        => 'batch',
                'type'          => 'mpan',
                'status'        => 'created',
                'total_count'   => 2,
                'success_count' => 0,
                'failure_count' => 0,
                'attempts'      => 0,
            ],
        ],
    ],

    'testMpanCreationBatchEmptyCell'      =>      [
        'request'   => [
            'url'       =>  '/admin/batches',
            'method'    =>  'post',
            'content'   =>  [
                'type'      =>  'mpan',
            ],
        ],
        'response'  => [
            'content' => [
                'entity'        => 'batch',
                'type'          => 'mpan',
                'status'        => 'created',
                'total_count'   => 2,
                'success_count' => 0,
                'failure_count' => 0,
                'attempts'      => 0,
            ],
        ],
    ],

    'testMpanCreationInvalidMpan'          => [
        'request'   => [
            'url'       =>  '/admin/batches',
            'method'    =>  'post',
            'content'   =>  [
                'type'      =>  'mpan',
            ],
        ],
        'response'  => [
            'content' => [
                'entity'        => 'batch',
                'type'          => 'mpan',
                'status'        => 'created',
                'total_count'   => 2,
                'success_count' => 0,
                'failure_count' => 0,
                'attempts'      => 0,
            ],
        ],
    ],

    'testMpanCreationExistingMpan'          => [
        'request'   => [
            'url'       =>  '/admin/batches',
            'method'    =>  'post',
            'content'   =>  [
                'type'      =>  'mpan',
            ],
        ],
        'response'  => [
            'content' => [
                'entity'        => 'batch',
                'type'          => 'mpan',
                'status'        => 'created',
                'total_count'   => 2,
                'success_count' => 0,
                'failure_count' => 0,
                'attempts'      => 0,
            ],
        ],
    ],

    'testMpanCreationBatchMigrated'      =>      [
        'request'   => [
            'url'       =>  '/admin/batches',
            'method'    =>  'post',
            'content'   =>  [
                'type'      =>  'mpan',
            ],
        ],
        'response'  => [
            'content' => [
                'id'               => 'Ev6Ob5J8kaMV6o',
                'created_at'       => 1590521524,
                'updated_at'       => 1590521524,
                'entity_id'        => '100000Razorpay',
                'name'             =>  null,
                'batch_type_id'    => 'mpan',
                'type'             => 'mpan',
                'is_scheduled'     => false,
                'upload_count'     => 0,
                'total_count'      => 3,
                'failure_count'    => 0,
                'success_count'    => 0,
                'amount'           => 0,
                'attempts'         => 0,
                'status'           => 'created',
                'processed_amount' => 0
            ],
        ],
        'encrypted_data' => [
            [
                "SrNo",
                "ADDEDON",
                "VPAN",
                "MPAN",
                "RPAN",
            ],
            [
                 "",
                 "",
                  "fec4acfbd152e9ba67bc27dd1c33fcaec7673c2ad7ab4994d08e837a0edd100c",
                  "17c7000b90b16eb452cefc2a6015b15aa2671c12e9082fb4d59abf27c739bc33",
                  "d0d780c370ca74ad2f2ae0c1c04560de383770c22c081e3ef36846af81276738",
            ],
            [
                 "",
                 "",
                  "34cf3f5cd79ecb217a9f4da04d5a9337275abb1e4f65398c81661c34c5438917",
                  "c873e51586b3d2f0edb9ec92d42767ef24e3efba1495453c5e7f2869fddc7718",
                  "f83c4c88ec1b574327932551acfe4332facfdffd223bf764473c3b29d6852d0c",
            ]
        ]
    ],

];
