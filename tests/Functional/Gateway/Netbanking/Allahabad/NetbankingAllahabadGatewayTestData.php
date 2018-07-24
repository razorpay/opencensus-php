<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\PaymentVerificationException;

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
        'bank_payment_id' => '99999',
        'received'        => true,
        'bank'            => 'ALLA',
        'status'          => 'Y',
    ],


    'testPaymentVerifySuccessEntity' => [
        'received'        => true,
        'bank'            => 'ALLA',
        'status'          => RZP\Gateway\Netbanking\Allahabad\Status::YES,
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
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\RuntimeException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
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

    'testPaymentSuccessVerifyFail' => [
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
        'received'        => false,
        'bank'            => 'ALLA',
        'status'          => 'N'
    ],

    'testPaymentFailedNetbankingEntity' => [
        'bank_payment_id' => null,
        'received'        => false,
        'bank'            => 'ALLA',
        'status'          => null
    ],

    'testUserCancelledPayments' => [
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

    'testAuthSuccessVerifyFailedNetbankingEntity' => [
        'received'        => true,
        'bank'            => 'ALLA',
        'status'          => 'N'
    ],
    ];