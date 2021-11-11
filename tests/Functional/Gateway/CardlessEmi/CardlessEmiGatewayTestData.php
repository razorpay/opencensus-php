<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testPayment'               => [
        'merchant_id'       => '10000000000000',
        'amount'            => 50000,
        'method'            => 'cardless_emi',
        'status'            => 'authorized',
        'two_factor_auth'   => 'not_applicable',
        'amount_authorized' => 50000,
        'amount_refunded'   => 0,
        'refund_status'     => null,
        'currency'          => 'INR',
        'description'       => 'random description',
        'bank'              => null,
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'cardless_emi',
        'terminal_id'       => '1CrdlesEmiTrml',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'otp_attempts'      => null
    ],

    'testPaymentEarlysalary'               => [
        'merchant_id'       => '10000000000000',
        'amount'            => 50000,
        'method'            => 'cardless_emi',
        'status'            => 'authorized',
        'two_factor_auth'   => null,
        'amount_authorized' => 50000,
        'amount_refunded'   => 0,
        'refund_status'     => null,
        'currency'          => 'INR',
        'description'       => 'random description',
        'bank'              => null,
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'cardless_emi',
        'terminal_id'       => '1CrdlesEmiTrml',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'otp_attempts'      => null
    ],

    'testPaymentForSubMerchant' => [
        'amount'            => 50000,
        'method'            => 'cardless_emi',
        'status'            => 'authorized',
        'two_factor_auth'   => 'not_applicable',
        'amount_authorized' => 50000,
        'amount_refunded'   => 0,
        'refund_status'     => null,
        'currency'          => 'INR',
        'description'       => 'random description',
        'bank'              => null,
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'cardless_emi',
        'terminal_id'       => '1CrdlesEmiTrml',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'otp_attempts'      => null
    ],

    'testPaymentFlexMoney'  => [
        'merchant_id'       => '10000000000000',
        'amount'            => 50000,
        'method'            => 'cardless_emi',
        'status'            => 'authorized',
        'two_factor_auth'   => null,
        'amount_authorized' => 50000,
        'amount_refunded'   => 0,
        'refund_status'     => null,
        'currency'          => 'INR',
        'description'       => 'random description',
        'bank'              => null,
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'cardless_emi',
        'terminal_id'       => '20CrdlesEmiTml',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'otp_attempts'      => null
    ],


    'testPaymentFlexMoneySubprovider'  => [
        'merchant_id'       => '10000000000000',
        'amount'            => 50000,
        'method'            => 'cardless_emi',
        'status'            => 'authorized',
        'two_factor_auth'   => null,
        'amount_authorized' => 50000,
        'amount_refunded'   => 0,
        'refund_status'     => null,
        'currency'          => 'INR',
        'description'       => 'random description',
        'bank'              => null,
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'wallet'            => 'kkbk',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'cardless_emi',
        'terminal_id'       => '20CrdlsEmiMlTl',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'otp_attempts'      => null
    ],

    'testPaymentFlexMoneyForSubMerchant' => [
        'amount'            => 50000,
        'method'            => 'cardless_emi',
        'status'            => 'authorized',
        'two_factor_auth'   => null,
        'amount_authorized' => 50000,
        'amount_refunded'   => 0,
        'refund_status'     => null,
        'currency'          => 'INR',
        'description'       => 'random description',
        'bank'              => null,
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'cardless_emi',
        'terminal_id'       => '20CrdlesEmiTml',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'otp_attempts'      => null
    ],

    'testPaymentZestMoney'  => [
        'merchant_id'       => '10000000000000',
        'amount'            => 50000,
        'method'            => 'cardless_emi',
        'status'            => 'authorized',
        'two_factor_auth'   => 'not_applicable',
        'amount_authorized' => 50000,
        'amount_refunded'   => 0,
        'refund_status'     => null,
        'currency'          => 'INR',
        'description'       => 'random description',
        'bank'              => null,
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'cardless_emi',
        'terminal_id'       => '30CrdlesEmiTml',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'otp_attempts'      => null
    ],

    'testPaymentZestMoneyForSubMerchant'  => [
        'amount'            => 50000,
        'method'            => 'cardless_emi',
        'status'            => 'authorized',
        'two_factor_auth'   => 'not_applicable',
        'amount_authorized' => 50000,
        'amount_refunded'   => 0,
        'refund_status'     => null,
        'currency'          => 'INR',
        'description'       => 'random description',
        'bank'              => null,
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'cardless_emi',
        'terminal_id'       => '30CrdlesEmiTml',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'otp_attempts'      => null
    ],

    'testEmiPlans' => [
        [
            'entity'           => 'emi_plan',
            'duration'         => 3,
            'interest'         => 13,
            'currency'         => 'INR',
            'amount_per_month' => '1000.20',
        ],
        [
            'entity'           => 'emi_plan',
            'duration'         => 6,
            'interest'         => 19,
            'currency'         => 'INR',
            'amount_per_month' => '1000.20',
        ]
    ],

    'testPaymentCardlessEmiEntity'   => [
        'action'           => 'authorize',
        'amount'           => '50000',
        'gateway'          => 'cardless_emi',
        'contact'          => '+919918899029',
        'refund_id'        => null,
        'entity'           => 'cardless_emi'
    ],

    'testPaymentCaptureEntity' => [
        'action'    => 'capture',
        //'received'  => true,
        'amount'    => '50000',
        'currency'  => 'INR',
        'entity'    => 'cardless_emi'
    ],

    'testCheckAccountUserDne' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CARDLESS_EMI_USER_DOES_NOT_EXIST,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_CARDLESS_EMI_USER_DOES_NOT_EXIST,
        ],
    ],

    'testFetchTokenFailed' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_TOKEN_NOT_FOUND,
        ],
    ],

    'testFailedPayment'  => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "Your payment didn't go through due to a temporary issue. Any debited amount will be refunded in 4-5 business days.",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED
        ],
    ],

    'testFailedPaymentEarlysalary'  => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::GATEWAY_ERROR,
                    'description' => "Payment was unsuccessful due to an error at gateway's end. Try using another payment method",
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_PAYMENT_FLOW_MISMATCH
        ],
    ],

    'testChecksumFailedEarlysalary'  => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Failed checksum verification',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testPaymentAmountErrorEarlysalary'  => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    'description' => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => RZP\Exception\LogicException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED
        ],
    ],

    'testFlexmoneyFailedPayment' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::GATEWAY_ERROR,
                    'description' => "Your payment didn't go through due to a temporary issue. Any debited amount will be refunded in 4-5 business days.",
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_PAYMENT_FAILED
        ],
    ],

    'testCapturePaymentFailed'  => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_PAYMENT_CAPTURE_FAILED
        ],
    ],

    'testPaymentVerifyFailed'  => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\PaymentVerificationException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED
        ],
    ],

    'testRefundFailed'   => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_PAYMENT_REFUND_FAILED
        ],
    ],
];
