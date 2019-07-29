<?php

use RZP\Models\Batch\Header;

return [
    'testBulkTerminalCreation'          => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'     => 'terminal',
                'sub_type' => 'flexmoney',
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
                'sub_type' => 'flexmoney',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 3,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Header::FLEXMONEY_MERCHANT_ID          => '10NodalAccount',
                        Header::FLEXMONEY_GATEWAY_MERCHANT_ID  => '123',
                        Header::FLEXMONEY_GATEWAY_MERCHANT_ID2 => '123',
                        Header::FLEXMONEY_CATEGORY             => '1234'
                    ],
                    [
                        Header::FLEXMONEY_MERCHANT_ID          => '100000Razorpay',
                        Header::FLEXMONEY_GATEWAY_MERCHANT_ID  => '321',
                        Header::FLEXMONEY_GATEWAY_MERCHANT_ID2 => '321',
                        Header::FLEXMONEY_CATEGORY             => '1234'
                    ]
                ],
            ],
        ],
    ],
];
