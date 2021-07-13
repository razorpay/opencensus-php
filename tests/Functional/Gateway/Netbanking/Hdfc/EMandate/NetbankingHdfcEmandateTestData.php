<?php

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testEmandateInitialPayment' => [
        'gateway'           => 'netbanking_hdfc',
        'status'            => 'authorized',
        'amount_authorized' => 0,
        'amount'            => 0,
        'verified'          => null,
        'late_authorized'   => false,
        'two_factor_auth'   => 'unavailable',
        'auto_captured'     => false,
        'captured'          => false,
        'recurring'         => true,
        'recurring_type'    => Payment\RecurringType::INITIAL,
    ],

    'testDirectDebitFlowSuccess' => [
        'gateway'           => 'netbanking_hdfc',
        'status'            => 'captured',
        'amount_authorized' => 50000,
        'amount'            => 50000,
        'verified'          => null,
        'late_authorized'   => false,
        'two_factor_auth'   => 'unavailable',
        'auto_captured'     => true,
        'captured'          => true,
        'recurring'         => true,
        'recurring_type'    => Payment\RecurringType::INITIAL,
    ],

    'matchInitiatedToken' => [
        'recurring'                 => false,
        'recurring_status'          => 'initiated',
        'recurring_details'         => [
            'status'            => 'initiated',
            'failure_reason'    => null,
        ],
        'bank'                      => 'HDFC',
        'method'                    => 'emandate',
        'used_count'                => 1,
        'recurring_failure_reason'  => null,
    ],

    'testSecondRecurringPaymentVerify' => [
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
            'class'                 => RZP\Exception\PaymentVerificationException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
        ],
    ],

    'testEmandateRegistration' => [
        'request' => [
            'content' => [
                'type'    => 'emandate_register',
                'targets' => ['hdfc'],
                'begin'   => Carbon::today(Timezone::IST)->timestamp,
                'end'     => Carbon::tomorrow(Timezone::IST)->timestamp,
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
                        'target'              => 'hdfc',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ]
        ]
    ],

    'testEmandateRegistrationForLateAuthFailure' => [
        'request' => [
            'content' => [
                'type'    => 'emandate_register',
                'targets' => ['hdfc'],
                'begin'   => Carbon::today(Timezone::IST)->timestamp,
                'end'     => Carbon::tomorrow(Timezone::IST)->timestamp,
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
                        'status'     => 'acknowledged',
                        'processing' => false,
                        'sender'     => 'emandate@razorpay.com',
                        'type'       => 'emandate_register',
                        'target'     => 'hdfc',
                        'entity'     => 'gateway_file',
                        'sent_at'    => null,
                    ],
                ],
            ]
        ]
    ],

    'testEmandateRegistrationForLateAuth' => [
        'request' => [
            'content' => [
                'type'    => 'emandate_register',
                'targets' => ['hdfc'],
                'begin'   => Carbon::today(Timezone::IST)->timestamp,
                'end'     => Carbon::tomorrow(Timezone::IST)->timestamp,
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
                        'target'              => 'hdfc',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ]
        ]
    ],

    'testEmandateDebit' => [
        'request' => [
            'content' => [
                'type'    => 'emandate_debit',
                'targets' => ['hdfc'],
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
                        'status'              => 'file_sent',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emandate@razorpay.com',
                        'type'                => 'emandate_debit',
                        'target'              => 'hdfc',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ]
        ]
    ],

    'matchAuthGatewayPayment' => [
        'action'            => 'authorize',
        'bank'              => 'HDFC',
        'received'          => false,
        'bank_payment_id'   => null,
        'status'            => null,
        'error_message'     => null,
        'si_token'          => null,
        'si_status'         => null,
        'si_message'        => null,
    ],

    'testEmandateDebitCreateFileFailure' => [
        'request' => [
            'content' => [
                'type'    => 'emandate_debit',
                'targets' => ['hdfc'],
                'begin'   => Carbon::yesterday(Timezone::IST)->gettimestamp(),
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
                        'status'              => 'failed',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emandate@razorpay.com',
                        'type'                => 'emandate_debit',
                        'target'              => 'hdfc',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ]
        ]
    ],

    'testEmandateDebitOnRetry' => [
        'request' => [
            'content' => [
                'type'    => 'emandate_debit',
                'targets' => ['hdfc'],
                'begin'   => Carbon::yesterday(Timezone::IST)->gettimestamp(),
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
                        'status'              => 'file_sent',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emandate@razorpay.com',
                        'type'                => 'emandate_debit',
                        'target'              => 'hdfc',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ]
        ]
    ],

    'testEmandateInitialPaymentFailure' => [
        'response' => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::GATEWAY_ERROR,
                    'description'   => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
        ],
    ],

    'testRefundDebitPayment' => [
        'type' => 'refund',
        'target' => 'hdfc_emandate',
        'sender' => 'refunds@razorpay.com',
        'status' => 'file_sent',
    ],

    'process_via_batch_service' => [
        'request' => [
            'url'    => '/emandate/batch_service',
            'method' => 'post',
            'server' => [
                'mode' => 'test',
            ],
        ],
        'response' => [
            'content' => [],
        ]
    ],
];
