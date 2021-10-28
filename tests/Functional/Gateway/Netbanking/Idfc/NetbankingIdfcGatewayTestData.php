<?php

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\GatewayErrorException;

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
        'bank'              => 'IDFB',
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'netbanking_idfc',
        'signed'            => false,
        'verified'          => null,
        'terminal_id'       => '100NbIdfcTrmnl',
    ],

    'testTpvPayment' => [
        'request' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'method'         => 'netbanking',
                'bank'           => 'IDFB',
                'account_number' => '14030403040304',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
            ],
        ],
    ],

    'testTpvPaymentEntity' => [
        'amount'          => 500,
        'action'          => 'authorize',
        'bank'            => 'IDFB',
        'bank_payment_id' => '9999999999',
        'status'          => 'SUC000',
        'reference1'      => null,
        'received'        => true,
    ],

    'testPaymentNetbankingEntity' => [
        'bank_payment_id' => '9999999999',
        'received'        => true,
        'bank'            => 'IDFB',
        'status'          => 'SUC000',
    ],

    'testPaymentFailedNetbankingEntity' => [
        'action'          => 'authorize',
        'bank'            => 'IDFB',
        'status'          => 'ACT001',
        'received'        => true,
    ],

    'testPaymentVerifySuccessEntity' => [
        'bank_payment_id' => '9999999999',
        'status'          => RZP\Gateway\Netbanking\Idfc\StatusCode::SUCCESS_CODE,
    ],

    'testTamperedPayment' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::SERVER_ERROR,
                    'description'   => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\LogicException',
            'internal_error_code'   => ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED,
        ],
    ],

    'testPaymentTampered' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::SERVER_ERROR,
                    'description'   => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\LogicException',
            'internal_error_code'   => ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED,
        ],
    ],

    'testPaymentFailed' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => "Your payment didn't go through as it was declined by the bank. Try another payment method or contact your bank.",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        ],
    ],

    'testPaymentCancelledByUser' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => "Your payment has been cancelled. Try again or complete the payment later.",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER,
        ],
    ],

    'testPaymentCancelledByUserForResponse' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => "Your payment has been cancelled. Try again or complete the payment later.",
                    'reason'        => 'payment_cancelled',
                    'source'        => 'customer',
                    'step'          => 'payment_authentication'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER,
        ],
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
    'testPaymentCancelledByUserForResponseInHindi' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'उपयोगकर्ता द्वारा पेमेंट प्रोसेसिंग को रद्द किया गया',
                    'reason'        => 'payment_cancelled',
                    'source'        => 'customer',
                    'step'          => 'payment_authentication'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER,
        ],
    ],
    'testNetbankingIdfcCombinedFile' => [
        'request' => [
            'content' => [
                'type'     => 'combined',
                'targets'  => ['idfc'],
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
                        'target'              => 'idfc',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ]
            ]
        ]
    ]
];
