<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testPayment' => [
        'merchant_id'       => '10000000000000',
        'amount'            => 50000,
        'method'            => 'card',
        'status'            => 'captured',
        'amount_authorized' => 50000,
        'amount_refunded'   => 0,
        'refund_status'     => null,
        'currency'          => 'INR',
        'description'       => 'random description',
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'cybersource',
        'terminal_id'       => '1000CybrsTrmnl',
        'signed'            => false,
        'verified'          => null,
        'fee'               => 1150,
        'service_tax'       => 150,
        'entity'            => 'payment',
    ],

    'testCybersourceCaptureEntity' => [
        'amount'             => 50000,
        'pares_status'       => null,
        'reason_code'        => 100,
        'action'             => 'capture',
        'received'           => true,
        'refund_id'          => null,
        'auth_data'          => null,
        'commerce_indicator' => null,
        'eci'                => null,
        'cavv'               => null,
        'status'             => 'captured',
        'entity'             => 'cybersource',
    ],

    'testTransactionAfterCapture' => [
        'type'              => 'payment',
        'merchant_id'       => '10000000000000',
        'amount'            => 50000,
        'fee'               => 1150,
        'debit'             => 0,
        'credit'            => 48850,
        'currency'          => 'INR',
        'balance'           => 1048850,
        'gateway_fee'       => 0,
        'api_fee'           => 0,
        'escrow_balance'    => 1048850,
        'channel'           => 'kotak',
        'settled'           => false,
        'settlement_id'     => null,
        'reconciled_at'     => null,
        'entity'            => 'transaction',
        'admin'             => true,
    ],

    'testGatewayTimeoutError' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR_REQUEST_TIMEOUT,
                ],
            ],
            'status_code' => 504,
        ],
        'exception' => [
            'class' => RZP\Exception\GatewayTimeoutException::class,
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
    ],

    'testGatewayProcessorTimeout' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => 'There is a problem with the gateway causing the payment to fail',
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_TIMED_OUT,
        ],
    ],

    'testPaymentWithSavedCard' => [
        'amount'              => 50000,
        'method'              => 'card',
        'status'              => 'authorized',
        'amount_authorized'   => 50000,
        'amount_refunded'     => 0,
        'refund_status'       => null,
        'currency'            => 'INR',
        'internal_error_code' => null,
        'global_customer_id'  => '10000gcustomer',
        'app_token'           => '1000000custapp',
        'global_token_id'     => '10000custgcard',
        'email'               => 'a@b.com',
        'contact'             => '+919918899029',
        'transaction_id'      => null,
        'auto_captured'       => false,
        'captured_at'         => null,
        'gateway'             => 'cybersource',
        'terminal_id'         => '1000CybrsTrmnl',
        'recurring'           => false,
        'save'                => false,
        'late_authorized'     => 0,
        'captured'            => false,
        'entity'              => 'payment',
        'admin'               => true
    ],

    'testGatewayFullRefund' => [
        'action'             => 'refund',
        'received'           => true,
        'auth_data'          => null,
        'commerce_indicator' => null,
        'amount'             => 50000,
        'pares_status'       => null,
        'status'             => 'refunded',
        'merchantAdviceCode' => null,
        'reason_code'        => 100,
        'entity'             => 'cybersource',
        'admin'              => true
    ],

    'testGatewayPartialRefund' => [
        'action'             => 'refund',
        'received'           => true,
        'auth_data'          => null,
        'commerce_indicator' => null,
        'amount'             => 10000,
        'pares_status'       => null,
        'status'             => 'refunded',
        'merchantAdviceCode' => null,
        'reason_code'        => 100,
        'entity'             => 'cybersource',
        'admin'              => true
    ],

    'testAuthorizedPaymentRefund' => [
        'amount'    => 50000,
        'currency'  => 'INR',
        'entity'    => 'refund',
        'admin'     => true,
    ],

    'testGatewayPaymentMismatchVerify' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\PaymentVerificationException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
        ],
    ],

    'testAuthorizeFailedPayment' => [
        'action'        => 'authorize',
        'received'      => true,
        'refund_id'     => null,
        'auth_data'     => null,
        'amount'        => 50000,
        'pares_status'  => null,
        'status'        => 'authorized',
        'xid'           => 'bWJWb1RsYzN1dEpTVUVvQ1NBMDA=',
        'eci'           => '2',
        'cavv'          => 'jAt2OkgfBuDnCBAAAJDIBBkAAAA=',
        'capture_ref'   => null,
        'reason_code'   => 100,
        'entity'        => 'cybersource',
        'admin'         => true
    ]
];