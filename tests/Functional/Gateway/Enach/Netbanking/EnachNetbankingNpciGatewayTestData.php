<?php

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testPaymentRejectResponse' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        ],
    ],

    'testPaymentFailedWithWrongOtpVerify' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => RZP\Exception\GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
        ],
    ],

    'testPaymentErrorResponse' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
        ],
    ],

    'testPaymentErrorResponseWithoutCertificate' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST,
        ],
    ],

    'testDebitFileGenerationCiti' => [
        'request' => [
            'content' => [
                'type'    => 'nach_debit',
                'targets' => ['paper_nach_citi'],
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
                            ''
                        ],
                        'status'              => 'file_generated',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emandate@razorpay.com',
                        'type'                => 'nach_debit',
                        'target'              => 'paper_nach_citi',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ]
        ]
    ],
    'testDebitCancel' => [
        'request' => [
            'url' => '/admin/batches',
            'method' => 'POST',
            'content' => [
                'type'     => 'emandate',
                'sub_type' => 'cancel_debit',
                'gateway'  => 'all',
            ],
            'files' => [
                'file' => '',
            ]
        ],
        'response' => [
            'content' => [
                'entity'               => 'batch',
                'type'                 => 'emandate',
                'status'               => 'created',
                'total_count'          => 1,
                'success_count'        => 0,
                'failure_count'        => 0,
                'processed_count'      => 0,
                'processed_percentage' => 0,
            ]
        ]
    ],

    'testDebitFileGenerationOnNonWorkingDay' => [
        'request' => [
            'content' => [
                'type'    => 'nach_debit',
                'targets' => ['paper_nach_citi'],
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
                            ''
                        ],
                        'status'              => 'acknowledged',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'type'                => 'nach_debit',
                        'target'              => 'paper_nach_citi',
                        'entity'              => 'gateway_file',
                        'admin'               => true,
                        'comments'            => 'No data present for gateway file processing in the given time period'
                    ],
                ],
            ]
        ],
    ],

    'testDebitFileGenerationYesb' => [
        'request' => [
            'content' => [
                'type'    => 'emandate_debit',
                'targets' => ['enach_npci_netbanking'],
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
                            ''
                        ],
                        'status'              => 'file_generated',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emandate@razorpay.com',
                        'type'                => 'emandate_debit',
                        'target'              => 'enach_npci_netbanking',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ]
        ]
    ],

    'testDebitFileGenerationYesbTxtFormat' => [
        'request' => [
            'content' => [
                'type'          => 'emandate_debit',
                'targets'       => ['yesb'],
                'time_range'    => 24,
                'end'           => 9,
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
                            ''
                        ],
                        'status'              => 'file_generated',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emandate@razorpay.com',
                        'type'                => 'emandate_debit',
                        'target'              => 'yesb',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ]
        ]
    ],

    'testDebitFileGenerationYesbEarlyDebit' => [
        'request' => [
            'content' => [
                'type'    => 'emandate_debit',
                'targets' => ['enach_npci_netbanking_early_debit'],
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
                        'recipients'          => [],
                        'status'              => 'file_generated',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emandate@razorpay.com',
                        'type'                => 'emandate_debit',
                        'target'              => 'enach_npci_netbanking_early_debit',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ]
        ]
    ],

    'testCancelEmandateToken' => [
        'request' => [
            'url' => '/gateway/files',
            'method' => 'POST',
            'content' => [
                'type'    => 'emandate_cancel',
                'targets' => ['enach_npci_netbanking'],
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
                        'target'              => 'enach_npci_netbanking',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ]
        ]
    ],

    'testCancelEmandateTokenCiti' => [
        'request' => [
            'url' => '/gateway/files',
            'method' => 'POST',
            'content' => [
                'type'    => 'nach_cancel',
                'targets' => ['combined_nach_citi'],
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
                        'type'                => 'nach_cancel',
                        'target'              => 'combined_nach_citi',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ]
        ]
    ],

    'testDebitFileGenerationIcici' => [
        'request' => [
            'content' => [
                'type'    => 'nach_debit',
                'targets' => ['combined_nach_icici'],
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
                            ''
                        ],
                        'status'              => 'file_generated',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emandate@razorpay.com',
                        'type'                => 'nach_debit',
                        'target'              => 'combined_nach_icici',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ]
        ]
    ],

    'testPaymentFailedVerifySuccess' => [
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

    'testRegistrationPaymentForDebitOnlyBank' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_BANK_RECURRING_NOT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_BANK_RECURRING_NOT_SUPPORTED,
        ],
    ],

    'testPreferencesForDebitOnlyBank' => [
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

    'testRegistrationOrderForDebitOnlyBank' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_ORDER_BANK_INVALID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_ORDER_BANK_INVALID,
        ],
    ],
    
    
    'testEmandatePreferencesWithAccountMasking' => [
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
    
    'testEmandateRegistrationWithAccountMasking' => [
        'request' => [
            'content' => [
                "amount"      => 0,
                "currency"    => "INR",
                "method"      => "emandate",
                "order_id"    => "order_100000000order",
                "customer_id" => "cust_1000000000cust",
                "recurring"   => true,
                "contact"     => "9999999999",
                "email"       => "test@razorpay.com",
                "auth_type"   => "netbanking",
            ],
            'method'    => 'POST',
            'url'       => '/payments/create/ajax',
        ],
        'response'  => [
            'content'     => [
            ],
            'status_code' => 200,
        ]
    ],

    'process_via_batch_service' => [
        'request' => [
            'url'    => '/nach/batch_service',
            'method' => 'post',
            'server' => [
                'mode' => 'test',
            ],
        ],
        'response' => [
            'content' => [],
        ]
    ],

    'testFailureDebitFileGeneration' => [
        'request' => [
            'content' => [
                'type'    => 'nach_debit',
                'targets' => ['paper_nach_citi'],
                'begin'   => Carbon::yesterday(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::today(Timezone::IST)->getTimestamp() - 1,
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::GATEWAY_ERROR,
                    'description'   => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\GatewayErrorException',
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        ],
    ],

    'testPartialDebitFileGeneration' => [
        'request' => [
            'content' => [
                'type'    => 'nach_debit',
                'targets' => ['paper_nach_citi'],
                'begin'   => Carbon::yesterday(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::today(Timezone::IST)->getTimestamp() - 1,
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::GATEWAY_ERROR,
                    'description'   => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\GatewayErrorException',
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        ],
    ],

    'testPaymentVerifyWithoutUMRN' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
        ],
    ],
];
