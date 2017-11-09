<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Constants\Timezone;

use Carbon\Carbon;

return [
    'testPaymentNetbankingEntity' => [
        'action'          => 'authorize',
        'amount'          => 20,
        'bank'            => 'UTIB',
        'received'        => true,
        'error_message'   => null,
        'schedule_status' => 'Y',
        'status'          => 'Y',
        'entity'          => 'netbanking',
    ],

    'testEmandateInitialPaymentFailure' => [
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
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        ],
    ],

    'testPaymentVerify' => [
        'TYP' => 'TEST',
        'STC' => '000',
        'RMK' => 'Success',
        'PMD' => 'AIB',
    ],

    'testPaymentVerifyFailure' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\PaymentVerificationException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
        ],
    ],

    'testPaymentVerifyAmountMismatch' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => RZP\Exception\RuntimeException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_RUNTIME_ERROR,
        ],
    ],

    'testEmandateDebit' => [
        'request' => [
            'content' => [
                'type'    => 'emandate_debit',
                'targets' => ['axis'],
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
                        'target'              => 'axis',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ]
        ]
    ],

    'matchAuthGatewayPayment' => [
        'action'            => 'authorize',
        'bank'              => 'UTIB',
        'received'          => false,
        'merchant_code'     => '10000000000000',
        'bank_payment_id'   => null,
        'status'            => null,
        'error_message'     => null,
        'si_token'          => null,
        'si_status'         => null,
        'si_message'        => null,
    ]
];
