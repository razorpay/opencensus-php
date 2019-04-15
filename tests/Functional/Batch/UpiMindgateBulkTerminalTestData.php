<?php

use RZP\Models\Batch\Header;
use RZP\Error\PublicErrorCode;

return [
    'testBulkTerminalCreation'          => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'     => 'terminal',
                'sub_type' => 'upi_mindgate',
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
                'sub_type' => 'upi_mindgate',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 3,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Header::UPI_MINDGATE_MERCHANT_ID          => '10NodalAccount',
                        Header::UPI_MINDGATE_GATEWAY_MERCHANT_ID  => 'HDFC000011670815',
                        Header::UPI_MINDGATE_VPA                  => 'abc.razorpay@hdfcbank',
                        Header::UPI_MINDGATE_COLLECT              => null,
                        Header::UPI_MINDGATE_PAY                  => null,
                    ],
                    [
                        Header::UPI_MINDGATE_MERCHANT_ID          => '100000Razorpay',
                        Header::UPI_MINDGATE_GATEWAY_MERCHANT_ID  => 'HDFC000011670816',
                        Header::UPI_MINDGATE_VPA                  => 'xyz.razorpay@hdfcbank',
                        Header::UPI_MINDGATE_COLLECT              => '0',
                        Header::UPI_MINDGATE_PAY                  => 1,
                    ],
                    [
                        Header::UPI_MINDGATE_MERCHANT_ID          => '10NodalAccount',
                        Header::UPI_MINDGATE_GATEWAY_MERCHANT_ID  => 'HDFC000011670817',
                        Header::UPI_MINDGATE_VPA                  => 'pqr.razorpay@hdfcbank',
                        Header::UPI_MINDGATE_COLLECT              => null,
                        Header::UPI_MINDGATE_PAY                  => null,
                    ]
                ],
            ],
        ],
    ],

    'testBulkTerminalCreationValidateVpaRequired' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'     => 'terminal',
                'sub_type' => 'upi_mindgate',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'The vpa field is required.',
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'                 => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code'   => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],
];
