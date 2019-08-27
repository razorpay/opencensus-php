<?php

use RZP\Models\Batch\Header;

return [
    'testBulkIinUpdate'          => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'     => 'iin_npci_rupay',
            ],
        ],
        'response' => [
            'content' => [
                'entity'        => 'batch',
                'type'          => 'iin_npci_rupay',
                'status'        => 'created',
                'total_count'   => 11,
                'success_count' => 0,
                'failure_count' => 0,
                'attempts'      => 0,
            ],
        ],
    ],
];
