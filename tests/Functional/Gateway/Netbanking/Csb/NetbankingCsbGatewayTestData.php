<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Csb;

use Carbon\Carbon;

use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\GatewayErrorException;
use RZP\Exception\PaymentVerificationException;

return [
    'testPayment' => [
        'amount'          => 50000,
        'action'          => 'authorize',
        'bank'            => 'CSBK',
        'bank_payment_id' => '9999999999',
        'status'          => 'Y',
        'reference1'      => null,
        'received'        => true,
    ],

    'testPaymentFailed' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        ],
    ],

    'testPaymentFailedNetbankingEntity' => [
        'amount'          => 50000,
        'action'          => 'authorize',
        'bank'            => 'CSBK',
        'bank_payment_id' => '0',
        'status'          => 'N',
        'reference1'      => null,
        'received'        => true,
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

    // When verify callback failure happens, the gateway entity is not updated
    'testVerifyCallbackFailureEntity' => [
        'amount'          => 50000,
        'action'          => 'authorize',
        'bank'            => 'CSBK',
        'bank_payment_id' => null,
        'status'          => null,
        'reference1'      => null,
        'received'        => false,
    ],

    'testVerifyCallbackFailure' => [
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
            'class'                 => GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR,
        ],
    ],

    'testTpvPayment' => [
        'request' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'netbanking',
                'bank'           => 'CSBK',
                'account_number' => '040304030403041234',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
            ],
        ],
    ],

    'testNetbankingCsbCombinedFile' => [
        'request' => [
            'content' => [
                'type'     => 'combined',
                'targets'  => ['csb'],
                'begin'    => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'      => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'type'                => 'combined',
                        'target'              => 'csb',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ]
            ]
        ]
    ]
];
