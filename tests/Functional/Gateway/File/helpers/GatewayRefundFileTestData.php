<?php

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Error\PublicErrorCode;

return [
    'testProcessRefundFile' => [
        'request' => [
            'content' => [
                'type'   => 'refund',
                'source' => 'hdfc',
                'from'   => Carbon::today(Timezone::IST)->timestamp,
                'to'     => Carbon::tomorrow(Timezone::IST)->timestamp
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'status'              => 'mail_sent',
                'scheduled'           => true,
                'partially_processed' => false,
                'attempts'            => 1,
                'sender'              => 'refunds@razorpay.com',
                'type'                => 'refund',
                'source'              => 'hdfc',
                'entity'              => 'gateway_file',
                'admin'               => true
            ]
        ]
    ],

    'testProcessGatewayFileWithInvalidType' => [
        'request' => [
            'content' => [
                'type'   => 'xyz',
                'source' => 'hdfc',
                'from'   => Carbon::today(Timezone::IST)->timestamp,
                'to'     => Carbon::tomorrow(Timezone::IST)->timestamp
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'xyz is not a valid gateway file type',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testProcessGatewayFileWithInvalidSource' => [
        'request' => [
            'content' => [
                'type'    => 'refund',
                'source'  => 'kotak',
                'from'    => Carbon::today(Timezone::IST)->timestamp,
                'to'      => Carbon::tomorrow(Timezone::IST)->timestamp
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'kotak is not a supported source for type refund',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testProcessGatewayFileWithInvalidRecipients' => [
        'request' => [
            'content' => [
                'type'       => 'refund',
                'source'     => 'hdfc',
                'from'       => Carbon::today(Timezone::IST)->timestamp,
                'to'         => Carbon::tomorrow(Timezone::IST)->timestamp,
                'recipients' => ['abc']
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The recipients.0 must be a valid email address.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testProcessGatewayFileStartingInFuture' => [
        'request' => [
            'content' => [
                'type'       => 'refund',
                'source'     => 'hdfc',
                'from'       => Carbon::tomorrow(Timezone::IST)->timestamp,
                'to'         => Carbon::tomorrow(Timezone::IST)->timestamp,
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'from cannot be in the future',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testProcessGatewayFileWithInvalidTimeRange' => [
        'request' => [
            'content' => [
                'type'       => 'refund',
                'source'     => 'hdfc',
                'from'       => Carbon::today(Timezone::IST)->timestamp,
                'to'         => Carbon::yesterday(Timezone::IST)->timestamp,
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'from cannot be after to',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testProcessRefundFileWithCustomRecipients' => [
        'request' => [
            'content' => [
                'type'       => 'refund',
                'source'     => 'hdfc',
                'recipients' => ['test@razorpay.com'],
                'from'       => Carbon::today(Timezone::IST)->timestamp,
                'to'         => Carbon::tomorrow(Timezone::IST)->timestamp
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'status'              => 'mail_sent',
                'scheduled'           => true,
                'partially_processed' => false,
                'attempts'            => 1,
                'sender'              => 'refunds@razorpay.com',
                'recipients'          => ['test@razorpay.com'],
                'type'                => 'refund',
                'source'              => 'hdfc',
                'entity'              => 'gateway_file',
                'admin'               => true
            ]
        ]
    ],

    'testProcessRefundFileWithNoRefundData' => [
        'request' => [
            'content' => [
                'type'    => 'refund',
                'source'  => 'hdfc',
                'from'    => Carbon::today(Timezone::IST)->timestamp,
                'to'      => Carbon::tomorrow(Timezone::IST)->timestamp
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'status'              => 'failed',
                'failure_code'        => 'no_data_for_file_generation',
                'scheduled'           => true,
                'partially_processed' => false,
                'attempts'            => 1,
                'sender'              => 'refunds@razorpay.com',
                'type'                => 'refund',
                'source'              => 'hdfc',
                'entity'              => 'gateway_file',
                'admin'               => true
            ]
        ]
    ],

    'testProcessRefundFileWithFileGenerationError' => [
        'request' => [
            'content' => [
                'type'    => 'refund',
                'source'  => 'hdfc',
                'from'    => Carbon::today(Timezone::IST)->timestamp,
                'to'      => Carbon::tomorrow(Timezone::IST)->timestamp
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'status'              => 'failed',
                'failure_code'        => 'error_creating_file',
                'scheduled'           => true,
                'partially_processed' => false,
                'attempts'            => 1,
                'sender'              => 'refunds@razorpay.com',
                'type'                => 'refund',
                'source'              => 'hdfc',
                'entity'              => 'gateway_file',
                'admin'               => true
            ]
        ]
    ],

    'testProcessRefundFileWithMailSendError' => [
        'request' => [
            'content' => [
                'type'    => 'refund',
                'source'  => 'hdfc',
                'from'    => Carbon::today(Timezone::IST)->timestamp,
                'to'      => Carbon::tomorrow(Timezone::IST)->timestamp
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'status'              => 'failed',
                'failure_code'        => 'error_sending_mail',
                'scheduled'           => true,
                'partially_processed' => false,
                'attempts'            => 1,
                'sender'              => 'refunds@razorpay.com',
                'type'                => 'refund',
                'source'              => 'hdfc',
                'entity'              => 'gateway_file',
                'admin'               => true
            ]
        ]
    ],

    'testRefundFileFileGenErrorRetryProcessing' => [
        'request' => [
            'content' => [
            ],
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'status'              => 'mail_sent',
                'scheduled'           => true,
                'partially_processed' => false,
                'attempts'            => 2,
                'sender'              => 'refunds@razorpay.com',
                'type'                => 'refund',
                'source'              => 'hdfc',
                'entity'              => 'gateway_file',
                'admin'               => true
            ]
        ]
    ],

    'testRefundFileMailSendErrorRetryProcessing' => [
        'request' => [
            'content' => [
            ],
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'status'              => 'mail_sent',
                'scheduled'           => true,
                'partially_processed' => false,
                'attempts'            => 2,
                'sender'              => 'refunds@razorpay.com',
                'type'                => 'refund',
                'source'              => 'hdfc',
                'entity'              => 'gateway_file',
                'admin'               => true
            ]
        ]
    ],

    'testRefundFileNoDataAvailableRetryProcessing' => [
        'request' => [
            'content' => [
            ],
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'This gateway file is not retriable',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => \RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_GATEWAY_FILE_NON_RETRIABLE,
        ]
    ],

    'testAcknowledgedGatewayFileRetry' => [
        'request' => [
            'content' => [
            ],
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'This gateway file is not retriable',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => \RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_GATEWAY_FILE_NON_RETRIABLE,
        ]
    ],

    'testGatewayFileAcknowledge' => [
        'request' => [
            'content' => [
            ],
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'status'              => 'acknowledged',
                'scheduled'           => true,
                'partially_processed' => false,
                'attempts'            => 1,
                'sender'              => 'refunds@razorpay.com',
                'type'                => 'refund',
                'source'              => 'hdfc',
                'entity'              => 'gateway_file',
                'admin'               => true
            ]
        ]
    ],

    'testGatewayFileAcknowledgePartiallyProcessed' => [
        'request' => [
            'content' => [
                'partially_processed' => '1',
            ],
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'status'              => 'acknowledged',
                'scheduled'           => true,
                'partially_processed' => true,
                'attempts'            => 1,
                'sender'              => 'refunds@razorpay.com',
                'type'                => 'refund',
                'source'              => 'hdfc',
                'entity'              => 'gateway_file',
                'admin'               => true
            ]
        ]
    ],

    'testGenerateGatewayFilesBulk' => [
        'request' => [
            'content' => [
                'sources' => [
                    'hdfc'
                ],
                'from' => Carbon::today(Timezone::IST)->timestamp,
                'to'   => Carbon::tomorrow(Timezone::IST)->timestamp
            ],
            'url' => '/gateway/files/refund/generate',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'admin' => true,
                'items' => [
                    [
                        'status'              => 'mail_sent',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'refunds@razorpay.com',
                        'type'                => 'refund',
                        'source'              => 'hdfc',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ],

    'testGenerateGatewayFilesBulkWithInvalidType' => [
        'request' => [
            'content' => [
                'sources' => [
                    'hdfc'
                ],
                'from' => Carbon::today(Timezone::IST)->timestamp,
                'to'   => Carbon::tomorrow(Timezone::IST)->timestamp,
            ],
            'url' => '/gateway/files/xyz/generate',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'xyz is not a valid gateway file type',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ]
];
