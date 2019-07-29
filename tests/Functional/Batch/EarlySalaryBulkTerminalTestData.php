<?php

use RZP\Models\Batch\Header;

return [
    'testBulkTerminalCreation'          => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'     => 'terminal',
                'sub_type' => 'earlysalary',
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
                'sub_type' => 'earlysalary',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 3,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Header::EARLYSALARY_MERCHANT_ID          => '10NodalAccount',
                        Header::EARLYSALARY_GATEWAY_MERCHANT_ID  => '123',
                        Header::EARLYSALARY_GATEWAY_MERCHANT_ID2 => '123',
                        Header::EARLYSALARY_CATEGORY             => '1234'
                    ],
                    [
                        Header::EARLYSALARY_MERCHANT_ID          => '100000Razorpay',
                        Header::EARLYSALARY_GATEWAY_MERCHANT_ID  => '321',
                        Header::EARLYSALARY_GATEWAY_MERCHANT_ID2 => '321',
                        Header::EARLYSALARY_CATEGORY             => '1234'
                    ]
                ],
            ],
        ],
    ],
];
