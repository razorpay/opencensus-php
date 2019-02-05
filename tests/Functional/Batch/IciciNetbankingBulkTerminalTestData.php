<?php

use RZP\Models\Batch\Header;

return [
    'testBulkTerminalCreation'          => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'     => 'terminal',
                'sub_type' => 'netbanking_icici',
            ],
        ],
        'response' => [
            'content' => [
                'entity'        => 'batch',
                'type'          => 'terminal',
                'status'        => 'created',
                'total_count'   => 3,
                'success_count' => 0,
                'failure_count' => 0,
                'attempts'      => 0,
            ],
        ],
    ],
    'testBulkTerminalCreationValidateFile' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'     => 'terminal',
                'sub_type' => 'netbanking_icici',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 3,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Header::ICIC_NB_MERCHANT_ID  => '10NodalAccount',
                        Header::ICIC_NB_SUB_IDS      => '10000000000000',
                        Header::ICIC_NB_GATEWAY_MID  => '1233231',
                        Header::ICIC_NB_GATEWAY_MID2 => '23443',
                        Header::ICIC_NB_SECTOR       => 'ecommerce',
                    ],
                    [
                        Header::ICIC_NB_MERCHANT_ID  => '100000Razorpay',
                        Header::ICIC_NB_SUB_IDS      => null,
                        Header::ICIC_NB_GATEWAY_MID  => '1233291',
                        Header::ICIC_NB_GATEWAY_MID2 => '13443',
                        Header::ICIC_NB_SECTOR       => 'ecommerce',
                    ]
                ],
            ],
        ],
    ],
];
