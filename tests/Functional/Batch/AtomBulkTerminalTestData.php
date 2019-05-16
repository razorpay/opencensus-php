<?php

use RZP\Models\Batch\Header;

return [
    'testBulkTerminalCreation'          => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'     => 'terminal',
                'sub_type' => 'atom',
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
                'sub_type' => 'atom',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 3,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Header::ATOM_MERCHANT_ID          => '10NodalAccount',
                        Header::ATOM_GATEWAY_MERCHANT_ID  => '123',
                        Header::ATOM_CATEGORY             => 'ecommerce',
                        Header::ATOM_TERMINAL_PASSWORD    => 'abc123',
                        Header::ATOM_TERMINAL_PASSWORD2   => 'abc321',
                        Header::ATOM_ACCESS_CODE          => '12345678',
                        Header::ATOM_SECURE_SECRET        => 's12345678',
                        Header::ATOM_SECURE_SECRET2       => 's12345678',
                        Header::ATOM_NON_RECURRING        => '1',
                    ],
                    [
                        Header::ATOM_MERCHANT_ID          => '100000Razorpay',
                        Header::ATOM_GATEWAY_MERCHANT_ID  => '321',
                        Header::ATOM_CATEGORY             => 'ecommerce',
                        Header::ATOM_TERMINAL_PASSWORD    => 'abc123',
                        Header::ATOM_TERMINAL_PASSWORD2   => 'abc321',
                        Header::ATOM_ACCESS_CODE          => '12345678',
                        Header::ATOM_SECURE_SECRET        => 's12345678',
                        Header::ATOM_SECURE_SECRET2       => 's12345678',
                        Header::ATOM_NON_RECURRING        => '0',
                    ],
                ],
            ],
        ],
    ],
];
