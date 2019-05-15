<?php

use RZP\Models\Batch\Header;

return [
    'testBulkTerminalCreation'          => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'     => 'terminal',
                'sub_type' => 'billdesk',
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
                'sub_type' => 'billdesk',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 3,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Header::BILLDESK_MERCHANT_ID         => '10NodalAccount',
                        Header::BILLDESK_GATEWAY_MERCHANT_ID => '123',
                        Header::BILLDESK_CATEGORY            => 'ecommerce',
                        Header::BILLDESK_NON_RECURRING       => '1'
                    ],
                    [
                        Header::BILLDESK_MERCHANT_ID         => '100000Razorpay',
                        Header::BILLDESK_GATEWAY_MERCHANT_ID => '321',
                        Header::BILLDESK_CATEGORY            => 'ecommerce',
                        Header::BILLDESK_NON_RECURRING       => '0'
                    ]
                ],
            ],
        ],
    ],
];
