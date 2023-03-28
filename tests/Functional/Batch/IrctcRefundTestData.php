<?php


return [
    'testIrctcRefundCreationProcessWithValidFile'          => [
        'request'  => [
            'url'     => '/merchant/10000000000000/batches',
            'method'  => 'post',
            'content' => [    'type' => 'irctc',
                'data' => [ 'refund' =>'',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'irctc_refund'        => 'batch',
            ],
        ],
    ],
    'testUploadIrctcWithAdminAuthPermissionRefundFile'          => [
        'request'  => [
            'url'     => '/merchant/10000000000000/batches',
            'method'  => 'post',
            'content' => [    'type' => 'irctc',
                'data' => [ 'refund' =>'',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'irctc_refund'        => 'batch',
            ],
        ],
    ],
    'testUploadIrctcWithAdminAuthRefundFile'          => [
        'request'  => [
            'url'     => '/merchant/10000000000000/batches',
            'method'  => 'post',
            'content' => [    'type' => 'irctc',
                'data' => [ 'refund' =>'',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'irctc_refund'        => 'batch',
            ],
        ],
    ],
    'testIrctcRefundCreationProcessWithInValidFile'          => [
        'request'  => [
            'url'     => '/merchant/10000000000000/batches',
            'method'  => 'post',
            'content' => [    'type' => 'irctc',
                'data' => [ 'refund' =>'',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'irctc_refund'        => 'batch',
            ],
        ],
    ],
];
