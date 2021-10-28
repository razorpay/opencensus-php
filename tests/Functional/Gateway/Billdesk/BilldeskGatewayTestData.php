<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Payment\TwoFactorAuth;

return [
    'testPayment' => [
        'merchant_id'       => '10000000000000',
        'amount'            => 50000,
        'method'            => 'netbanking',
        'status'            => 'captured',
        'two_factor_auth'   => TwoFactorAuth::UNAVAILABLE,
        'amount_authorized' => 50000,
        'amount_refunded'   => 0,
        'refund_status'     => null,
        'currency'          => 'INR',
        'description'       => 'random description',
        'bank'              => 'UCBA',
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'billdesk',
        'terminal_id'       => '1000BdeskTrmnl',
        'signed'            => false,
        'verified'          => null,
        'fee'               => 1476,
        'tax'               => 226,
        'entity'            => 'payment',
    ],

    'testPaymentWithMerchantProcuredTerminal' => [
        'merchant_id'       => '10000000000000',
        'amount'            => 50000,
        'method'            => 'netbanking',
        'status'            => 'captured',
        'two_factor_auth'   => TwoFactorAuth::UNAVAILABLE,
        'amount_authorized' => 50000,
        'amount_refunded'   => 0,
        'refund_status'     => null,
        'currency'          => 'INR',
        'description'       => '1234',
        'bank'              => 'UCBA',
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'billdesk',
        'terminal_id'       => '1000BdeskTrmnl',
        'signed'            => false,
        'verified'          => null,
        'fee'               => 1476,
        'tax'               => 226,
        'entity'            => 'payment',
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
        'channel'         => 'axis',
        'settled'         => false,
        'settled_at'      => null,
        'settlement_id'   => null,
        'reconciled_at'   => null,
        'entity'          => 'transaction',
        'admin'           => true,
    ],

    'testTransactionAfterCapture' => [
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
        'channel'       => 'axis',
        'settled'       => false,
        'settlement_id' => null,
        'reconciled_at' => null,
        'entity'        => 'transaction',
        'admin'         => true,
    ],

    'testPaymentBilldeskEntity' => [
        'action'        => 'authorize',
        'received'      => true,
        'BankID'        => 'UCO',
        'CurrencyType'  => 'INR',
        'ItemCode'      => 'DIRECT',
        'TypeField1'    => 'R',
        'TypeField2'    => 'F',
        'RefAmount'     => null,
        'RefDateTime'   => null,
        'RefStatus'     => null,
        'RefundId'      => null,
        'ErrorCode'     => null,
        'ErrorReason'   => null,
        'ProcessStatus' => null,
        'refund_id'     => null,
        'entity'        => 'billdesk',
    ],

    'testPaymentRefund' => [
        'action'           => 'refund',
        'received'         => true,
        'TxnAmount'        => '500',
        'BankID'           => null,
        'CurrencyType'     => 'INR',
        'ItemCode'         => null,
        'TypeField1'       => null,
        'TypeField2'       => null,
        'AdditionalInfo1'  => null,
        'BankReferenceNo'  => null,
        'BankMerchantID'   => null,
        'SecurityType'     => null,
        'AuthStatus'       => null,
        'SettlementType'   => null,
        'ErrorStatus'      => null,
        'ErrorDescription' => null,
        'RequestType'      => '0410',
        'RefAmount'        => '500.00',
        'RefStatus'        => '0799',
        'ErrorCode'        => 'NA',
        'ErrorReason'      => 'NA',
        'ProcessStatus'    => 'Y',
        'entity'           => 'billdesk',
    ],

    'testPaymentMultiplePartialRefund' => [
        'action'           => 'refund',
        'received'         => true,
        'TxnAmount'        => '500',
        'BankID'           => null,
        'CurrencyType'     => 'INR',
        'ItemCode'         => null,
        'TypeField1'       => null,
        'TypeField2'       => null,
        'AdditionalInfo1'  => null,
        'BankReferenceNo'  => null,
        'BankMerchantID'   => null,
        'SecurityType'     => null,
        'AuthStatus'       => null,
        'SettlementType'   => null,
        'ErrorStatus'      => null,
        'ErrorDescription' => null,
        'RequestType'      => '0410',
        'RefAmount'        => '100.00',
        'RefStatus'        => '0799',
        'ErrorCode'        => 'NA',
        'ErrorReason'      => 'NA',
        'ProcessStatus'    => 'Y',
        'entity'           => 'billdesk',
    ],

    'testPaymentPartialRefund' => [
        'action'           => 'refund',
        'received'         => true,
        'TxnAmount'        => '500',
        'BankID'           => null,
        'CurrencyType'     => 'INR',
        'ItemCode'         => null,
        'TypeField1'       => null,
        'TypeField2'       => null,
        'AdditionalInfo1'  => null,
        'BankReferenceNo'  => null,
        'BankMerchantID'   => null,
        'SecurityType'     => null,
        'AuthStatus'       => null,
        'SettlementType'   => null,
        'ErrorStatus'      => null,
        'ErrorDescription' => null,
        'RequestType'      => '0410',
        'RefAmount'        => '400.00',
        'RefStatus'        => '0799',
        'ErrorCode'        => 'NA',
        'ErrorReason'      => 'NA',
        'ProcessStatus'    => 'Y',
        'entity'           => 'billdesk',
    ],

    'testTransactionAfterRefundingAuthorizedPayment' => [
        'type'            => 'refund',
        'merchant_id'     => '10000000000000',
        'amount'          => 50000,
        'fee'             => 0,
        'pricing_rule_id' => null,
        'debit'           => 0,
        'credit'          => 0,
        'currency'        => 'INR',
        'balance'         => 0,
        'gateway_fee'     => 0,
        'api_fee'         => 0,
        'tax'             => 0,
        'channel'         => 'axis',
        'settled'         => false,
        'settled_at'      => null,
        'settlement_id'   => null,
        'reconciled_at'   => null,
        'entity'          => 'transaction',
        'admin'           => true,
    ],

    'testGetPaymentMethodsRoute' => [
        'request' => [
            'url' => '/methods',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'methods',
                'card' => true,
                'netbanking' => [
                    'UTIB' => 'Axis Bank',
//                    'BARB' => 'Bank of Baroda',
                    'YESB' => 'Yes Bank',
                ],
                'wallet' => [],
            ],
        ],
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
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_TOTAL_REFUND_AMOUNT_IS_GREATER_THAN_THE_PAYMENT_AMOUNT,
        ],
    ],

    'testReconcileCancelledTransactions' => [
        'request' => [
            'method' => 'POST',
            'url' => '/reconciliate/billdesk/cancelled',
        ],
        'response' => [
            'content' => [
                'success_count' => [
                    "payment" => 1,
                    "refund"  => 1,
                    ],
                'failure_count' => [
                    "payment" => 0,
                    "refund"  => 0,
                ],
            ],
        ],
    ],

    'testPaymentAndNewPaymentOnDeleteTerminal' => [
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
            'internal_error_code'   => 'SERVER_ERROR_RUNTIME_ERROR',
        ],
    ],

    'testPaymentVerifyError' => [
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
            'class'                 => RZP\Exception\GatewayErrorException::class,
            'internal_error_code'   => 'GATEWAY_ERROR_INVALID_RESPONSE',
        ],
    ],

    'testServerToServerCallback' => [
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
    'testMakerCheckerPaymentNormalCallbackForFailed' => [
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
            'class'                 => RZP\Exception\GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        ],
    ],
    'testPaymentFailureBeforeRedirection' => [
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
            'class'                 => RZP\Exception\GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_AT_NETBANKING_PAYMENT_PAGE,
        ],
    ],
    'testServerToServerFailureCallback' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => "Your payment could not be completed due to insufficient account balance. Try again with another account.",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => RZP\Exception\GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
        ],
    ]
];
