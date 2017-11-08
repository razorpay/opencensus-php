<?php

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testEMandateInitialPayment' => [
        'gateway'           => 'netbanking_hdfc',
        'status'            => 'authorized',
        'amount_authorized' => 2000,
        'amount'            => 2000,
        'verified'          => null,
        'late_authorized'   => false,
        'two_factor_auth'   => 'unavailable',
        'auto_captured'     => false,
        'captured'          => false,
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
        'method'                    => 'netbanking',
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

    'testEMandateRegistration' => [
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

    'testEMandateDebit' => [
        'request' => [
            'content' => [
                'type'    => 'emandate_debit',
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
        'merchant_code'     => '10000000000000',
        'bank_payment_id'   => null,
        'status'            => null,
        'error_message'     => null,
        'si_token'          => null,
        'si_status'         => null,
        'si_message'        => null,
    ],

    'testEMandateDebitCreateFileFailure' => [
        'request' => [
            'content' => [
                'type'    => 'emandate_debit',
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

    'testEMandateDebitOnRetry' => [
        'request' => [
            'content' => [
                'type'    => 'emandate_debit',
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
                        'type'                => 'emandate_debit',
                        'target'              => 'hdfc',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ]
        ]
    ],
];