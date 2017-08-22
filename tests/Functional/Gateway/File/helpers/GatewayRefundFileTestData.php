<?php

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testRefundFileProcessor' => [
        'request' => [
            'content' => [
                'type'    => 'refund',
                'gateway' => 'netbanking_hdfc',
                'bank'    => 'HDFC',
                'from'    => Carbon::today('Asia/Kolkata')->timestamp,
                'to'      => Carbon::tomorrow('Asia/Kolkata')->timestamp
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
                'gateway'             => 'netbanking_hdfc',
                'bank'                => 'HDFC',
                'entity'              => 'gateway_file',
                'admin'               => true
            ]
        ]
    ],

    'testProcessGatewayFileWithInvalidType' => [
        'request' => [
            'content' => [
                'type'    => 'xyz',
                'gateway' => 'netbanking_hdfc',
                'bank'    => 'HDFC',
                'from'    => Carbon::today('Asia/Kolkata')->timestamp,
                'to'      => Carbon::tomorrow('Asia/Kolkata')->timestamp
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

    'testProcessGatewayFileWithInvalidGateway' => [
        'request' => [
            'content' => [
                'type'    => 'refund',
                'gateway' => 'hdfc',
                'bank'    => 'HDFC',
                'from'    => Carbon::today('Asia/Kolkata')->timestamp,
                'to'      => Carbon::tomorrow('Asia/Kolkata')->timestamp
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'hdfc is not a supported gateway for type refund',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testProcessGatewayFileWithInvalidBank' => [
        'request' => [
            'content' => [
                'type'    => 'refund',
                'gateway' => 'netbanking_hdfc',
                'bank'    => 'ICIC',
                'from'    => Carbon::today('Asia/Kolkata')->timestamp,
                'to'      => Carbon::tomorrow('Asia/Kolkata')->timestamp
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'ICIC is not supported for refund file for netbanking_hdfc gateway',
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
                'gateway'    => 'netbanking_hdfc',
                'bank'       => 'HDFC',
                'from'       => Carbon::today('Asia/Kolkata')->timestamp,
                'to'         => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                'recipients' => ['abc']
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'recipient email id provided is not valid: abc',
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
                'gateway'    => 'netbanking_hdfc',
                'bank'       => 'HDFC',
                'from'       => Carbon::tomorrow('Asia/Kolkata')->timestamp,
                'to'         => Carbon::tomorrow('Asia/Kolkata')->timestamp,
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
                'gateway'    => 'netbanking_hdfc',
                'bank'       => 'HDFC',
                'from'       => Carbon::today('Asia/Kolkata')->timestamp,
                'to'         => Carbon::yesterday('Asia/Kolkata')->timestamp,
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

    'testRefundFileProcessorWithCustomRecipients' => [
        'request' => [
            'content' => [
                'type'       => 'refund',
                'gateway'    => 'netbanking_hdfc',
                'bank'       => 'HDFC',
                'recipients' => ['test@razorpay.com'],
                'from'       => Carbon::today('Asia/Kolkata')->timestamp,
                'to'         => Carbon::tomorrow('Asia/Kolkata')->timestamp
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
                'gateway'             => 'netbanking_hdfc',
                'bank'                => 'HDFC',
                'entity'              => 'gateway_file',
                'admin'               => true
            ]
        ]
    ],

    'testRefundFileProcessorWithNoRefundData' => [
        'request' => [
            'content' => [
                'type'    => 'refund',
                'gateway' => 'netbanking_hdfc',
                'bank'    => 'HDFC',
                'from'    => Carbon::today('Asia/Kolkata')->timestamp,
                'to'      => Carbon::tomorrow('Asia/Kolkata')->timestamp
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
                'gateway'             => 'netbanking_hdfc',
                'bank'                => 'HDFC',
                'entity'              => 'gateway_file',
                'admin'               => true
            ]
        ]
    ],

    'testRefundFileProcessorWithFileGenerationError' => [
        'request' => [
            'content' => [
                'type'    => 'refund',
                'gateway' => 'netbanking_hdfc',
                'bank'    => 'HDFC',
                'from'    => Carbon::today('Asia/Kolkata')->timestamp,
                'to'      => Carbon::tomorrow('Asia/Kolkata')->timestamp
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
                'gateway'             => 'netbanking_hdfc',
                'bank'                => 'HDFC',
                'entity'              => 'gateway_file',
                'admin'               => true
            ]
        ]
    ],

    'testRefundFileProcessingWithMailSendError' => [
        'request' => [
            'content' => [
                'type'    => 'refund',
                'gateway' => 'netbanking_hdfc',
                'bank'    => 'HDFC',
                'from'    => Carbon::today('Asia/Kolkata')->timestamp,
                'to'      => Carbon::tomorrow('Asia/Kolkata')->timestamp
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
                'gateway'             => 'netbanking_hdfc',
                'bank'                => 'HDFC',
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
                'gateway'             => 'netbanking_hdfc',
                'bank'                => 'HDFC',
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
                'gateway'             => 'netbanking_hdfc',
                'bank'                => 'HDFC',
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

    'testRetryForAcknowledgedGatewayFile' => [
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
                'gateway'             => 'netbanking_hdfc',
                'bank'                => 'HDFC',
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
                'gateway'             => 'netbanking_hdfc',
                'bank'                => 'HDFC',
                'entity'              => 'gateway_file',
                'admin'               => true
            ]
        ]
    ],

    'testGenerateGatewayFilesBulk' => [
        'request' => [
            'content' => [
                'netbanking_hdfc' => 'HDFC'
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
                        'gateway'             => 'netbanking_hdfc',
                        'bank'                => 'HDFC',
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
                'netbanking_hdfc' => 'HDFC'
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
