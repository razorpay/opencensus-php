<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Exception\LogicException;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\GatewayErrorException;
use RZP\Gateway\Netbanking\Vijaya;
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
        'bank'              => 'VIJB',
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id'   => 'random order id',
        ],
        'acquirer_data'     => [
            'bank_transaction_id' =>  Vijaya\Mock\Server::BANK_REF_NUMBER
        ],
        'gateway'           => 'netbanking_vijaya',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'terminal_id'       => '100NbVijbTrmnl',
    ],

    'testPaymentNetbankingEntity' => [
        'bank_payment_id' => Vijaya\Mock\Server::BANK_REF_NUMBER,
        'received'        => true,
        'bank'            => 'VIJB',
        'status'          => Vijaya\Status::SUCCESS,
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

    'testAmountTampering' => [
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
            'class'                 => LogicException::class,
            'internal_error_code'   => ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED,
        ],
    ],

    'netbankingPaymentFailed' => [
        'amount'          => 50000,
        'status'          => 'N',
    ],

    'netbankingVerify' => [
        'bank_payment_id' => Vijaya\Mock\Server::BANK_REF_NUMBER,
        'received'        => true,
        'bank'            => 'VIJB',
        'status'          => Vijaya\Status::SUCCESS
    ],

    'testPaymentVerifyMismatch' => [
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
            'class'                 => PaymentVerificationException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
        ],
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
];
