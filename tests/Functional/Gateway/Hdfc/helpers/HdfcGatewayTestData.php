<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Payment\TwoFactorAuth;

return [
    'testDebitRecurringPayment' => [
        'request' => [
            'content' => [],
            'method'    => 'POST',
            'url'       => '/reminders/send/test/payment/card_auto_recurring/%s',
        ],
        'response' => [
            'content' => [],
        ],
    ],
    'testFailureInRecurringPayment' => [
        'request' => [
            'content' => [],
            'method'    => 'POST',
            'url'       => '/reminders/send/test/payment/card_auto_recurring/%s',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\GatewayErrorException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_GATEWAY,
        ],
    ],
    'testRecurringPayment' => [
        'request' => [
            'content' => [],
            'method'    => 'POST',
            'url'       => '/reminders/send/test/payment/card_auto_recurring/%s',
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testPayment' => [
        'merchant_id' => '10000000000000',
        'amount' => 50000,
        'method' => 'card',
        'status' => 'captured',
        'two_factor_auth' => TwoFactorAuth::NOT_APPLICABLE,
        'captured' => true,
        'amount_authorized' => 50000,
        'amount_refunded' => 0,
        'refund_status' => null,
        'currency' => 'INR',
        'description' => 'random description',
        'bank' => null,
        'error_code' => null,
        'error_description' => null,
        'email' => 'a@b.com',
        'contact' => '+919918899029',
        'notes' => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway' => 'hdfc',
        'signed' => false,
        'verified' => null,
        'entity' => 'payment',
    ],

    'testPaymentForAuthorizationTerminal' => [
        'merchant_id' => '10000000000000',
        'amount' => 50000,
        'method' => 'card',
        'status' => 'captured',
        'two_factor_auth' => TwoFactorAuth::PASSED,
        'captured' => true,
        'amount_authorized' => 50000,
        'amount_refunded' => 0,
        'refund_status' => null,
        'currency' => 'INR',
        'description' => 'random description',
        'bank' => null,
        'error_code' => null,
        'error_description' => null,
        'email' => 'a@b.com',
        'contact' => '+919918899029',
        'notes' => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway' => 'hdfc',
        'signed' => false,
        'verified' => null,
        'entity' => 'payment',
    ],

    'testPaymentForAuthorizationTerminalWithShieldRiskMock' => [
        'merchant_id' => '10000000000000',
        'amount' => 50000,
        'method' => 'card',
        'status' => 'captured',
        'two_factor_auth' => TwoFactorAuth::PASSED,
        'captured' => true,
        'amount_authorized' => 50000,
        'amount_refunded' => 0,
        'refund_status' => null,
        'currency' => 'INR',
        'description' => 'random description',
        'bank' => null,
        'error_code' => null,
        'error_description' => null,
        'email' => 'a@b.com',
        'contact' => '+919918899029',
        'notes' => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway' => 'hdfc',
        'signed' => false,
        'verified' => null,
        'entity' => 'payment',
    ],

    'testInternationalPaymentFailureForRisk' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\GatewayErrorException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD_GATEWAY,
        ],
    ],

    'testPaymentForAuthorizationTerminalRupay' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::SERVER_ERROR,
                    'description' => 'We are facing some trouble completing your request at the moment. Please try again shortly.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class' => RZP\Exception\RuntimeException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_RUNTIME_ERROR,
        ],
    ],

    'testPaymentForAuthorizationTerminalFailure' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => 'Payment processing failed due to error at bank or wallet gateway',
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class' => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
        ],
    ],

    'testPaymentForAuthorizationTerminalFailureDifferentErrorCode' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Your payment didn\'t go through as it was declined by the bank. Try another payment method or contact your bank.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_GATEWAY,
        ],
    ],

    'testTamperedPayment' => [
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

    'testPaymentVerifyAndTransactionNotFoundInResponse' => [
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

    'testLongErrorCode' => [
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
            'class' => 'RZP\Exception\GatewayTimeoutException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
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
            'class'                 => 'RZP\Exception\LogicException',
            'internal_error_code'   => ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED,
        ],
    ],

    'testVerifyRefundDeniedByRiskOnGateway' => [
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

    'testInternationalUSDPaymentOnApi' => [
        'merchant_id'       => '10000000000000',
        'amount'            => 5000,
        'method'            => 'card',
        'status'            => 'captured',
        'two_factor_auth'   => TwoFactorAuth::NOT_APPLICABLE,
        'captured'          => true,
        'amount_authorized' => 5000,
        'amount_refunded'   => 0,
        'refund_status'     => null,
        'currency'          => 'USD',
        'description'       => 'random description',
        'bank'              => null,
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'hdfc',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
    ],

    'testTransactionAfterCapture' => [
        'type' => 'payment',
        'merchant_id' => '10000000000000',
        'amount' => 50000,
        'fee' => 1000,
        'debit' => 0,
        'credit' => 49000,
        'currency' => 'INR',
        'balance' => 1049000,
        'gateway_fee' => 0,
        'api_fee' => 0,
//        'escrow_balance' => 1048850,
        'channel' => 'axis',
        'settled' => false,
//        'settled_at' => 1437589800,
        'settlement_id' => null,
        'reconciled_at' => null,
        'entity' => 'transaction',
        'admin' => true,
    ],

    'testHdfcEntityAfterPaymentRefund' => [
        // 'id' => '3',
        // 'payment_id' => '3bUZ9YystH0Ib0',
        // 'refund_id' => '3bUZ9cW3h3AEy4',
        // 'gateway_transaction_id' => '777700659480931',
        'action' => 5,
        'received' => true,
        'amount' => '500',
        'enroll_result' => null,
        'status' => 'captured',
        'result' => 'CAPTURED',
        'eci' => null,
        // 'auth' => '999999',
        // 'ref' => '373790441251',
        'avr' => 'N',
        // 'postdate' => '0720',
        'error_code2' => null,
        'error_text' => null,
        'entity' => 'hdfc',
        'admin' => true,
    ],

    'testHdfcPaymentEntity' => [
        'refund_id' => null,
//        'gateway_transaction_id' => '663191662573200',
        'action' => 5,
        'received' => true,
        'amount' => '500',
        'enroll_result' => null,
        'status' => 'captured',
        'result' => 'CAPTURED',
        'eci' => null,
        'auth' => '999999',
//        'ref' => '789071669515',
        'avr' => 'N',
//        'postdate' => '0719',
        'error_code2' => null,
        'error_text' => null,
        'entity' => 'hdfc',
    ],

    'testHdfcUSDPaymentEntity' => [
        'refund_id' => null,
//        'gateway_transaction_id' => '663191662573200',
        'action' => 5,
        'received' => true,
        'amount' => '500',
        'enroll_result' => null,
        'status' => 'captured',
        'result' => 'CAPTURED',
        'eci' => null,
        'auth' => '999999',
//        'ref' => '789071669515',
        'avr' => 'N',
//        'postdate' => '0719',
        'error_code2' => null,
        'error_text' => null,
        'entity' => 'hdfc',
    ],

    'testRefundDeniedByRisk' => [
        'action' => 2,
        'received' => true,
        'amount' => '500',
        'enroll_result' => null,
        'status' => 'refund_failed',
        'result' => 'DENIED BY RISK',
        'eci' => null,
        'auth' => null,
        'ref' => null,
        'avr' => null,
        'postdate' => null,
        'error_code2' => 'RP00005',
        'error_text' => 'Denied by risk. Response result code is "DENIED BY RISK"',
        'entity' => 'hdfc',
    ],

    'testCaptureDeniedByRisk' => [
        'action' => 5,
        'received' => true,
        'amount' => '500',
        'enroll_result' => null,
        'status' => 'capture_failed',
        'result' => 'DENIED BY RISK',
        'eci' => null,
        'auth' => null,
        'ref' => null,
        'avr' => null,
        'postdate' => null,
        'error_code2' => 'RP00005',
        'error_text' => 'Denied by risk. Response result code is "DENIED BY RISK"',
        'entity' => 'hdfc',
    ],

    'testDebitPinAuthorizeFailed' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_DECLINED_BY_GATEWAY,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\GatewayErrorException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_GATEWAY,
        ],
    ],

    'testDebitPinVerifyFailed' => [
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
];
