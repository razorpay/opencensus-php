<?php

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Error\PublicErrorCode;

return [
    'testWithInvalidTarget'  => [
        'request' => [
            'content' => [
                'type'  => 'refund_failed',
                'targets' => ['abcd'],
                'begin' => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'   => Carbon::tomorrow(Timezone::IST)->getTimestamp(),
            ],
            'url' => '/gateway/files/',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'abcd is not a valid target for type refund_failed',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testUpiFailedRefundFile' => [
        'request' => [
            'content' => [
                'type'    => 'refund_failed',
                'targets' => ['upi_icici'],
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'admin' => true,
                'items' => [
                    [
                        'status'              => 'file_sent',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'refunds@razorpay.com',
                        'type'                => 'refund_failed',
                        'target'              => 'upi_icici',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ],
];
