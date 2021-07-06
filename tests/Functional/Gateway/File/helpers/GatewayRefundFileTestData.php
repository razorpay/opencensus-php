<?php

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Error\PublicErrorCode;

return [
    'testProcessRefundFileIciciEmi' => [
        'request' => [
            'content' => [
                'type'    => 'refund',
                'targets' => ['icici_emi'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'type'                => 'refund',
                        'target'              => 'icici_emi',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ],

    'testProcessRefundFile' => [
        'request' => [
            'content' => [
                'type'    => 'refund',
                'targets' => ['hdfc'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'type'                => 'refund',
                        'target'              => 'hdfc',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ],

    'testProcessRefundFileAsync' => [
        'request' => [
            'content' => [
                'type'    => 'refund',
                'targets' => ['hdfc'],
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
                        'status'              => 'created',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 0,
                        'sender'              => 'refunds@razorpay.com',
                        'type'                => 'refund',
                        'target'              => 'hdfc',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ],

    'testProcessGatewayFileWithInvalidType' => [
        'request' => [
            'content' => [
                'type'   => 'xyz',
                'targets' => ['hdfc'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                'targets' => ['kotak'],
                'begin'    => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'      => Carbon::tomorrow(Timezone::IST)->getTimestamp()
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'kotak is not a valid target for type refund',
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
                'targets'    => ['hdfc'],
                'begin'      => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'        => Carbon::tomorrow(Timezone::IST)->getTimestamp(),
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
                'type'    => 'refund',
                'targets' => ['hdfc'],
                'begin'   => Carbon::tomorrow(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp(),
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'begin cannot be in the future',
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
                'type'    => 'refund',
                'targets' => ['hdfc'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::yesterday(Timezone::IST)->getTimestamp(),
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'begin cannot be after end',
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
                'targets'    => ['hdfc'],
                'recipients' => ['test@razorpay.com'],
                'begin'      => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'        => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'recipients'          => ['test@razorpay.com'],
                        'type'                => 'refund',
                        'target'              => 'hdfc',
                        'entity'              => 'gateway_file',
                    ]
                ]
            ]
        ]
    ],

    'testProcessRefundFileWithNoRefundData' => [
        'request' => [
            'content' => [
                'type'    => 'refund',
                'targets' => ['hdfc'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'status'              => 'acknowledged',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'refunds@razorpay.com',
                        'comments'            => 'No data present for gateway file processing in the given time period',
                        'type'                => 'refund',
                        'target'              => 'hdfc',
                        'entity'              => 'gateway_file',
                    ]
                ]
            ]
        ]
    ],

    'testProcessRefundFileWithFileGenerationError' => [
        'request' => [
            'content' => [
                'type'    => 'refund',
                'targets' => ['hdfc'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'status'              => 'failed',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'refunds@razorpay.com',
                        'type'                => 'refund',
                        'target'              => 'hdfc',
                        'error_code'          => 'error_generating_file',
                        'entity'              => 'gateway_file',
                    ]
                ]
            ]
        ]
    ],

    'testProcessRefundFileWithMailSendError' => [
        'request' => [
            'content' => [
                'type'    => 'refund',
                'targets' => ['hdfc'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'status'              => 'failed',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'refunds@razorpay.com',
                        'type'                => 'refund',
                        'target'              => 'hdfc',
                        'error_code'          => 'error_sending_file',
                        'entity'              => 'gateway_file',
                    ]
                ]
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
                'status'              => 'file_sent',
                'scheduled'           => true,
                'partially_processed' => false,
                'attempts'            => 2,
                'sender'              => 'refunds@razorpay.com',
                'type'                => 'refund',
                'target'              => 'hdfc',
                'entity'              => 'gateway_file',
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
                'status'              => 'file_sent',
                'scheduled'           => true,
                'partially_processed' => false,
                'attempts'            => 2,
                'sender'              => 'refunds@razorpay.com',
                'type'                => 'refund',
                'target'              => 'hdfc',
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
                    'description' => 'This gateway file generation attempt is not retriable',
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
                    'description' => 'This gateway file generation attempt is not retriable',
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
                'comments' => 'test comments',
            ],
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'status'              => 'acknowledged',
                'scheduled'           => true,
                'partially_processed' => false,
                'comments'            => 'test comments',
                'attempts'            => 1,
                'sender'              => 'refunds@razorpay.com',
                'type'                => 'refund',
                'target'              => 'hdfc',
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
                'target'              => 'hdfc',
                'entity'              => 'gateway_file',
                'admin'               => true
            ]
        ]
    ],

    'testGenerateGatewayFilesBulkWithNoTargets' => [
        'request' => [
            'content' => [
                'type'  => 'refund',
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
                    'description' => 'targets are required and should be sent',
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
