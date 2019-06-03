<?php

use RZP\Models\Batch\Header;

return [
    'testBulkTerminalCreation'          => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'     => 'terminal',
                'sub_type' => 'netbanking_axis',
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
                'sub_type' => 'netbanking_axis',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 3,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Header::AXIS_NB_MERCHANT_ID          => '10NodalAccount',
                        Header::AXIS_NB_GATEWAY_MERCHANT_ID  => '123',
                        Header::AXIS_NB_CATEGORY             => 'ecommerce',
                        Header::AXIS_NB_TPV                  => '1',
                        Header::AXIS_NB_NON_RECURRING        => '1',
                    ],
                    [
                        Header::AXIS_NB_MERCHANT_ID          => '100000Razorpay',
                        Header::AXIS_NB_GATEWAY_MERCHANT_ID  => '321',
                        Header::AXIS_NB_CATEGORY             => 'ecommerce',
                        Header::AXIS_NB_TPV                  => '0',
                        Header::AXIS_NB_NON_RECURRING        => '0',
                    ]
                ],
            ],
        ],
    ],
];
