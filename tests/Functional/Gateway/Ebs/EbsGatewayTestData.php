<?php

use Razorpay\IFSC\Bank;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Payment\TwoFactorAuth;
use RZP\Models\Settlement\Channel;

return [
    'testPayment' => [
        'merchant_id'               => '10000000000000',
        'amount'                    => 50000,
        'method'                    => 'netbanking',
        'status'                    => 'captured',
        'two_factor_auth'           => TwoFactorAuth::UNAVAILABLE,
        'amount_authorized'         => 50000,
        'amount_refunded'           => 0,
        'currency'                  => 'INR',
        'description'               => 'random description',
        'bank'                      => Bank::MAHB,
        'error_code'                => null,
        'error_description'         => null,
        'email'                     => 'a@b.com',
        'contact'                   => '+919918899029',
        'notes'                     => [
            'merchant_order_id'     => 'random order id',
        ],
        'gateway'                   => 'ebs',
        'terminal_id'               => '100000EbsTrmnl',
        'signed'                    => false,
        'verified'                  => null,
        'fee'                       => 1476,
        'tax'                       => 226,
        'entity'                    => 'payment',
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
            'class'                 => 'RZP\Exception\LogicException',
            'internal_error_code'   => ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED,
        ],
    ],

    'testTransactionAfterAuthorize' => [
        'type'            => 'payment',
        'merchant_id'     => '10000000000000',
        'amount'          => 50000,
        'fee'             => 0,
        'tax'             => 0,
        'pricing_rule_id' => null,
        'debit'           => 0,
        'credit'          => 0,
        'currency'        => 'INR',
        'balance'         => 0,
        'gateway_fee'     => 0,
        'api_fee'         => 0,
        'channel'         => Channel::AXIS,
        'settled'         => false,
        'settled_at'      => null,
        'settlement_id'   => null,
        'reconciled_at'   => null,
        'entity'          => 'transaction',
        'admin'           => true,
    ],

    'testTransactionAfterCapture'   => [
        'type'          => 'payment',
        'merchant_id'   => '10000000000000',
        'amount'        => 50000,
        'fee'           => 1476,
        'debit'         => 0,
        'credit'        => 48524,
        'currency'      => 'INR',
        'balance'       => 1048524,
        'gateway_fee'   => 0,
        'api_fee'       => 0,
        'channel'       => Channel::AXIS,
        'settled'       => false,
        'settlement_id' => null,
        'reconciled_at' => null,
        'entity'        => 'transaction',
        'admin'         => true,
    ],

    'testPaymentEbsEntity'          => [
        'action'                    => 'authorize',
        'received'                  => true,
        'entity'                    => 'ebs',
        'is_flagged'                => false,
        'error_code'                => '0',
    ],

    'testPaymentFlaggedEbsEntity'   => [
        'action'                    => 'authorize',
        'received'                  => true,
        'entity'                    => 'ebs',
        'is_flagged'                => true,
        'error_code'                => null,
    ],

    'testPaymentMultipleInvalidPartialRefund' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_TOTAL_REFUND_AMOUNT_IS_GREATER_THAN_THE_PAYMENT_AMOUNT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => RZP\Exception\BadRequestException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_TOTAL_REFUND_AMOUNT_IS_GREATER_THAN_THE_PAYMENT_AMOUNT,
        ],
    ],

    'testPaymentForFirstGatewayRequestFailure' => [
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
            'class'                 => RZP\Exception\GatewayTimeoutException::class,
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
    ],

    'testPaymentForSecondGatewayRequestFailure' => [
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
            'class'                 => RZP\Exception\GatewayTimeoutException::class,
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
    ],

    'testPaymentForThirdGatewayRequestFailure' => [
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
            'class'                 => RZP\Exception\GatewayTimeoutException::class,
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
    ],

    'testPaymentRefundWithoutCapture' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => RZP\Exception\BadRequestException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED,
        ],
    ],

    'testHackedPayment' => [
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
            'class'                 => RZP\Exception\RuntimeException::class,
            'internal_error_code'   => ErrorCode::SERVER_ERROR_RUNTIME_ERROR,
        ],
    ],

    'testPaymentFailedVerify' => [
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

    'testErrorOnCard' => [
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
            'class'                 => RZP\Exception\RuntimeException::class,
            'internal_error_code'   => ErrorCode::SERVER_ERROR_RUNTIME_ERROR,
        ],
    ],


    'testPaymentMultiplePartialRefund' => [
        'received'                  => true,
        'action'                    => 'refund',
        'entity'                    => 'ebs',
        'is_flagged'                => false,
        'amount'                    => 10000,
        'error_code'                => null,
    ],

    'testPaymentPartialRefund'      => [
        'received'                  => true,
        'action'                    => 'refund',
        'entity'                    => 'ebs',
        'is_flagged'                => false,
        'amount'                    => 40000,
        'error_code'                => null,
    ],

    'testPaymentRefund'             => [
        'received'                  => true,
        'action'                    => 'refund',
        'entity'                    => 'ebs',
        'is_flagged'                => false,
        'amount'                    => 50000,
        'error_code'                => null,
    ],

    'testTransactionAfterRefundingAuthorizedPayment' => [
        'type'                      => 'refund',
        'merchant_id'               => '10000000000000',
        'amount'                    => 50000,
        'fee'                       => 0,
        'pricing_rule_id'           => null,
        'debit'                     => 0,
        'credit'                    => 0,
        'currency'                  => 'INR',
        'balance'                   => 0,
        'gateway_fee'               => 0,
        'api_fee'                   => 0,
        'fee'                       => 0,
        'tax'                       => 0,
//        'escrow_balance'            => 998562,
        'channel'                   => Channel::AXIS,
        'settled'                   => false,
        'settled_at'                => null,
        'settlement_id'             => null,
        'reconciled_at'             => null,
        'entity'                    => 'transaction',
        'admin'                     => true,
    ],
];
