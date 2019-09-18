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

];
