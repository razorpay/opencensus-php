<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\PaymentVerificationException;

return [
    'testAuthenticationFailed' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::GATEWAY_ERROR,
                    'description'   => 'Payment processing failed due to error at bank or wallet gateway'
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'                 => \RZP\Exception\GatewayErrorException::class,
            'internal_error_code'   => 'GATEWAY_ERROR_MANDATE_CREATION_FAILED',
        ],
    ],

    'testFailedPaymentVerifyOnLegaldesk' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 504,
        ],
        'exception' => [
            'class'                 => RZP\Exception\GatewayTimeoutException::class,
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ]
    ],

    'legaldeskVerifyFailed' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => RZP\Exception\PaymentVerificationException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
        ]
    ],

    'testRegistrationReconWithTestMerchantProxyAuth' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'Invalid type passed for batch creation'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code'   => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testRegistrationReconWithSharedMerchantProxyAuth' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'Invalid type passed for batch creation'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code'   => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testDebitFileGeneration' => [
        'request' => [
            'content' => [
                'type'    => 'emandate_debit',
                'targets' => ['enach_rbl'],
                'begin'   => Carbon::yesterday(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::today(Timezone::IST)->getTimestamp() - 1,
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
                        'recipients'          => [
                            'rbl.emandate@razorpay.com'
                        ],
                        'status'              => 'file_sent',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emandate@razorpay.com',
                        'type'                => 'emandate_debit',
                        'target'              => 'enach_rbl',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ]
        ]
    ],

    'testRegisterFileGeneration' => [
        'request' => [
            'content' => [
                'type'    => 'emandate_register',
                'targets' => ['enach_rbl'],
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
                        'sender'              => 'emandate@razorpay.com',
                        'type'                => 'emandate_register',
                        'target'              => 'enach_rbl',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ],

    'testRegisterFileGenerationWithReplicationLagError' => [
        'request' => [
            'content' => [
                'type'    => 'emandate_register',
                'targets' => ['enach_rbl'],
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
                        'sender'              => 'emandate@razorpay.com',
                        'type'                => 'emandate_register',
                        'target'              => 'enach_rbl',
                        'entity'              => 'gateway_file',
                        'admin'               => true,
                        'error_code'          => 'error_generating_file',
                        'error_description'   => 'Error occurred trying to create file',
                        'file_generated_at'   => null,
                        'sent_at'             => null
                    ]
                ]
            ]
        ]
    ],

    'tokenWebhookData' => [
        'mode'  => 'test',
        'event' => [
            'entity'   => 'event',
            'event' => 'token.confirmed',
            'contains' => [
                'token',
            ],
            'payload'  => [
                'token' => [
                    'entity' => [
                        'recurring' => true,
                        'recurring_details' => [
                            'status' => 'confirmed'
                        ]
                    ]
                ],
            ],
        ],
    ],

    'testDebitFileReconciliationRefund' => [
        'request' => [
            'url' => '',
            'method' => 'POST',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'refund',
                'amount' => 5000,
                'currency' => 'INR',
            ],
        ],
    ],

    'testDigioCallbackFailedVerifySuccess' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::GATEWAY_ERROR,
                    'description'   => PublicErrorDescription::GATEWAY_ERROR_REQUEST_TIMEOUT,
                ],
            ],
            'status_code' => 504,
        ],
        'exception' => [
            'class'                 => RZP\Exception\GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
    ],

    'testDigioAuthFailedVerifySuccess' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::GATEWAY_ERROR,
                    'description'   => 'Payment processing failed due to error at bank or wallet gateway'
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'                 => \RZP\Exception\GatewayErrorException::class,
            'internal_error_code'   => 'GATEWAY_ERROR_MANDATE_CREATION_FAILED',
        ],
    ],

    'testVerifyMismatch' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => PaymentVerificationException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
        ],
    ],

    'testDebitVerify' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\PaymentVerificationException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
        ],
    ],

    'testPreferencesForRegisterDisabledBank' => [
        'request' => [
            'content' => [],
            'url' => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCancelEmandateToken' => [
        'request' => [
            'url' => '/gateway/files',
            'method' => 'POST',
            'content' => [
                'type'    => 'emandate_cancel',
                'targets' => ['enach_rbl'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp() - 1,
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'admin' => true,
                'items' => [
                    [
                        'recipients'          => [''],
                        'status'              => 'file_sent',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emandate@razorpay.com',
                        'type'                => 'emandate_cancel',
                        'target'              => 'enach_rbl',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ]
        ]
    ],
];
