<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testThreeDSAuthFailedPayment' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
            'two_fa_error' => true,
        ],
    ],

    'testPayment' => [
        'merchant_id' => '10000000000000',
        'amount' => 50000,
        'method' => 'card',
        'status' => 'captured',
        'two_factor_auth' => 'passed',
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
        'fee'               => 1000,
        //'service_tax'       => 0,
        'tax'               => 0,
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
        'fee'               => 1000,
        'debit'             => 0,
        'credit'            => 49000,
        'currency'          => 'INR',
        'balance'           => 1049000,
        'gateway_fee'       => 0,
        'api_fee'           => 0,
//        'escrow_balance'    => 1048850,
        'channel'           => 'kotak',
        'settled'           => false,
        'settlement_id'     => null,
        'reconciled_at'     => null,
        'entity'            => 'transaction',
        'admin'             => true,
    ],

    'testGatewayCallbackWithEmptyInput' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        ],
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
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
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
        'late_authorized'     => false,
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
    ],

    'testRecurringPaymentAuthenticateCard' => [
        'amount'              => 50000,
        'method'              => 'card',
        'status'              => 'authorized',
        'order_id'            => null,
        'international'       => false,
        'amount_refunded'     => 0,
        'refund_status'       => null,
        'currency'            => 'INR',
        'bank'                => null,
        'wallet'              => null,
        'internal_error_code' => null,
        'customer_id'         => 'cust_100000customer',
        'global_customer_id'  => null,
        'app_token'           => null,
        'global_token_id'     => null,
        'email'               => 'a@b.com',
        'contact'             => '+919918899029',
        'transaction_id'      => null,
        'auto_captured'       => false,
        'gateway'             => 'cybersource',
        'recurring'           => true,
        'late_authorized'     => false,
        'captured'            => false,
        'entity'              => 'payment',
        'admin'               => true
    ],

    'testGatewayPaymentXidMisMatch' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::SERVER_ERROR,
                    'description' => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class' => RZP\Exception\LogicException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_LOGICAL_ERROR,
        ],
    ],

    'testGatewayMissingFieldError' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::SERVER_ERROR,
                    'description' => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class' => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_INVALID_ARGUMENT,
        ],
    ],

    'testGatewayInvalidReasonCode' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        ],
    ],

    'testSoapFaultException' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::SERVER_ERROR,
                    'description' => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class' => RZP\Exception\RuntimeException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_RUNTIME_ERROR,
        ],
    ],

    'testStatusAfterFailedAutoCapturePayment' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        ]
    ],

    'cybersourceRecurringEntity' => [
        'action' => 'authorize',
        'received' => true,
        'refund_id' => null,
        'auth_data' => null,
        'commerce_indicator' => 'recurring',
        'amount' => 50000,
        'pares_status' => null,
        'status' => 'authorized',
        'xid' => null,
        'avsCode' => 'Y',
        'cardCategory' => null,
        'cardGroup' => null,
        'cvCode' => 'M',
        'veresEnrolled' => null,
        'eci' => null,
        'collection_indicator' => null,
        'cavv' => null,
        'capture_ref' => null,
        'merchantAdviceCode' => '01',
        'processorResponse' => '00',
        'reason_code' => 100,
        'entity' => 'cybersource',
        'admin' => true
    ]
];
