<?php

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testPayment' => [
        'merchant_id'       => '10000000000000',
        'amount'            => 50000,
        'method'            => 'netbanking',
        'status'            => 'captured',
        'amount_authorized' => 50000,
        'amount_refunded'   => 0,
        'refund_status'     => null,
        'currency'          => 'INR',
        'description'       => 'random description',
        'card_id'           => null,
        'bank'              => 'ALLA',
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'netbanking_allahabad',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
    ],

    'testPaymentNetbankingEntity' => [
        'action'          => 'authorize',
        'bank_payment_id' => '99999',
        'received'        => true,
        'bank'            => 'ALLA',
        'status'          => 'Y',
    ],

    'testAuthorizeFailed' => [
        'response' => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        ],
    ],

    'testFailedChecksum' => [
        'response' => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::SERVER_ERROR,
                    'description'   => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\RuntimeException',
            'internal_error_code' => ErrorCode::SERVER_ERROR_RUNTIME_ERROR,
        ],
    ],


    'testAmountTampering' => [
        'response' => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::SERVER_ERROR,
                    'description'   => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\LogicException',
            'internal_error_code' => ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED,
        ],
    ],

    'testAuthFailedVerifySuccess' => [
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

    'testAuthFailedVerifyFailedEntity' => [
        'received'        => true,
        'bank'            => 'ALLA',
        'status'          => 'N'
    ],

    'testPaymentFailedNetbankingEntity' => [
        'received'        => true,
        'bank'            => 'ALLA',
        'status'          => 'N'
    ],

    'testTamperedPaymentNetbankingEntity' => [
        'received'        => false,
        'bank'            => 'ALLA',
        'status'          => null
    ],

    'testUserCancelledPayments' => [
        'response' => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_NETBANKING_CANCELLED_BY_USER,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_NETBANKING_CANCELLED_BY_USER,
        ],
    ],

    'testUserCancelledNetbankingEntity' => [
        'received'        => true,
        'bank'            => 'ALLA',
        'status'          => 'C',
        'bank_payment_id' => null,
    ],

    'testTpvPayment' => [
        'request' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'netbanking',
                'bank'           => 'ALLA',
                'account_number' => '04030403040304',
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

    'testTpvPaymentEntity' => [
        'amount'          => 500,
        'action'          => 'authorize',
        'bank'            => 'ALLA',
        'bank_payment_id' => '99999',
        'status'          => 'Y',
        'reference1'      => null,
        'received'        => true,
    ],

    'testNetbankingAllahabadCombinedFile' => [
        'request' => [
            'content' => [
                'type'     => 'combined',
                'targets'  => ['allahabad'],
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
                        'target'              => 'allahabad',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ]
            ]
        ]
    ]
];
