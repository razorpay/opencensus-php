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
                'sub_type' => 'netbanking_hdfc',
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
                'sub_type' => 'netbanking_hdfc',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 3,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Header::HDFC_NB_MERCHANT_ID          => '10NodalAccount',
                        Header::HDFC_NB_GATEWAY_MERCHANT_ID  => '1233231',
                        Header::HDFC_NB_CATEGORY             => 'ecommerce',
                        Header::HDFC_NB_TPV                  => 0,
                    ],
                    [
                        Header::HDFC_NB_MERCHANT_ID          => '100000Razorpay',
                        Header::HDFC_NB_GATEWAY_MERCHANT_ID  => '1233291',
                        Header::HDFC_NB_CATEGORY             => 'ecommerce',
                        Header::HDFC_NB_TPV                  => 1

                    ]
                ],
            ],
        ],
    ],

    'testBulkTerminalCreationValidateFileInvalidTpv' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'     => 'terminal',
                'sub_type' => 'netbanking_hdfc',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'The selected tpv is invalid.',
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
