<?php

use RZP\Models\Batch\Header;

return [
    'testBulkTerminalCreation'          => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'     => 'terminal',
                'sub_type' => 'zestmoney',
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
                'sub_type' => 'zestmoney',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 3,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Header::ZESTMONEY_MERCHANT_ID          => '10NodalAccount',
                        Header::ZESTMONEY_GATEWAY_MERCHANT_ID  => '123',
                        Header::ZESTMONEY_GATEWAY_MERCHANT_ID2 => '123',
                        Header::ZESTMONEY_CATEGORY             => '1234'
                    ],
                    [
                        Header::ZESTMONEY_MERCHANT_ID          => '100000Razorpay',
                        Header::ZESTMONEY_GATEWAY_MERCHANT_ID  => '321',
                        Header::ZESTMONEY_GATEWAY_MERCHANT_ID2 => '321',
                        Header::ZESTMONEY_CATEGORY             => '1234'
                    ]
                ],
            ],
        ],
    ],
];
