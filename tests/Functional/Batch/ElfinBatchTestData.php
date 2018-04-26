<?php

use RZP\Models\Batch\Header;

return [

    'testCreateBatchOfElfinType' => [
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type'   => 'elfin',
                'config' => [
                    'ptype' => 'marketing',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'elfin',
                'status'           => 'created',
                'total_count'      => 2,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'amount'           => null,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],

    'testCreateBatchOfElfinTypeFileRows' => [
        [
            Header::ELFIN_LONG_URL => 'https://www.google.com',
        ],
        [
            Header::ELFIN_LONG_URL => 'https://www.fb.com',
        ],
    ],
];
