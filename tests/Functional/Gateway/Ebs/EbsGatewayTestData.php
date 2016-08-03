<?php
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testPayment' => [
        'merchant_id'               => '10000000000000',
        'amount'                    => 50000,
        'method'                    => 'netbanking',
        'status'                    => 'captured',
        'amount_authorized'         => 50000,
        'amount_refunded'           => 0,
        'currency'                  => 'INR',
        'description'               => 'random description',
        'bank'                      => 'ICIC',
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
        'fee'                       => 1438,
        'service_tax'               => 188,
        'entity'                    => 'payment',
    ],

    'testTransactionAfterAuthorize' => [
        'type'                      => 'payment',
        'merchant_id'               => '10000000000000',
        'amount'                    => 50000,
        'fee'                       => 1438,
        'service_tax'               => 188,
        'pricing_rule_id'           => '1zD0BXpeOyaqpB',
        'debit'                     => 0,
        'credit'                    => 48562,
        'currency'                  => 'INR',
        'balance'                   => 0,
        'gateway_fee'               => 0,
        'api_fee'                   => 0,
        'escrow_balance'            => 1048562,
        'channel'                   => 'kotak',
        'settled'                   => false,
        'settled_at'                => null,
        'settlement_id'             => null,
        'reconciled_at'             => null,
        'entity'                    => 'transaction',
        'admin'                     => true,
    ],

    'testTransactionAfterCapture'   => [
        'type'                      => 'payment',
        'merchant_id'               => '10000000000000',
        'amount'                    => 50000,
        'fee'                       => 1438,
        'debit'                     => 0,
        'credit'                    => 48562,
        'currency'                  => 'INR',
        'balance'                   => 1048562,
        'gateway_fee'               => 0,
        'api_fee'                   => 0,
        'escrow_balance'            => 1048562,
        'channel'                   => 'kotak',
        'settled'                   => false,
        'settlement_id'             => null,
        'reconciled_at'             => null,
        'entity'                    => 'transaction',
        'admin'                     => true,
    ],

    'testPaymentEbsEntity'          => [
        'action'                    => 'authorize',
        'received'                  => true,
        'entity'                    => 'ebs',
        'is_flagged'                => false,
        'error_code'                => null,
    ],

    'testPaymentFlaggedEbsEntity'   => [
        'action'                    => 'authorize',
        'received'                  => true,
        'entity'                    => 'ebs',
        'is_flagged'                => true,
        'error_code'                => null,
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
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED,
        ],
    ],

    'testPaymentInvalidRefund' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => 'BAD_REQUEST_ERROR',
                    'description'   => 'Account Balance is insufficient',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\GatewayErrorException',
            'internal_error_code'   => 'BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE',
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
            'class'                 => 'RZP\Exception\LogicException',
            'internal_error_code'   => ErrorCode::SERVER_ERROR_LOGICAL_ERROR,
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
            'class'                 => 'RZP\Exception\PaymentVerificationException',
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
            'class'                 => 'RZP\Exception\RuntimeException',
            'internal_error_code'   => ErrorCode::SERVER_ERROR_RUNTIME_ERROR,
        ],
    ],

    'testPaymentRefund'             => [
        'received'                  => true,
        'action'                    => 'refund',
        'entity'                    => 'ebs',
        'is_flagged'                => false,
    ],

    'testTransactionAfterRefundingAuthorizedPayment' => [
        'type'                      => 'refund',
        'merchant_id'               => '10000000000000',
        'amount'                    => 50000,
        'fee'                       => 0,
        'pricing_rule_id'           => null,
        'debit'                     => 50000,
        'credit'                    => 0,
        'currency'                  => 'INR',
        'balance'                   => 0,
        'gateway_fee'               => 0,
        'api_fee'                   => 0,
        'fee'                       => 0,
        'service_tax'               => 0,
        'escrow_balance'            => 998562,
        'channel'                   => 'kotak',
        'settled'                   => false,
        'settled_at'                => null,
        'settlement_id'             => null,
        'reconciled_at'             => null,
        'entity'                    => 'transaction',
        'admin'                     => true,
    ],
];
