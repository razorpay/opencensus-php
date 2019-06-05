<?php

use RZP\Models\Batch\Header;

return [
    'testBulkSubmerchantAssign' => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'     => 'submerchant_assign',
            ],
        ],
        'response' => [
            'content' => [
                'entity'        => 'batch',
                'type'          => 'submerchant_assign',
                'status'        => 'created',
                'total_count'   => 4,
                'success_count' => 0,
                'failure_count' => 0,
                'attempts'      => 0,
            ],
        ],
    ],
    'testBulkAssignValidateFile' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'     => 'submerchant_assign',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 4,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Header::SUBMERCHANT_ID   => '100000Razorpay',
                        Header::TERMINAL_ID      => '100000EbsTrmnl',
                    ],
                    [
                        Header::SUBMERCHANT_ID   => '10NodalAccount',
                        Header::TERMINAL_ID      => '100HitachiTmnl',
                    ],
                    [
                        Header::SUBMERCHANT_ID   => '10000000000000',
                        Header::TERMINAL_ID      => '100HitachiTmnl',
                    ],
                ],
            ],
        ],
    ],
];
