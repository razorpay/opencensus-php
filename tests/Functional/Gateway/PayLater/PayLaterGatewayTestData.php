<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testPaymentAndRefund'      => [
        'merchant_id'       => '10000000000000',
        'amount'            => 50000,
        'method'            => 'paylater',
        'status'            => 'captured',
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
        'contact'           => '+918602579721',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'paylater',
        'terminal_id'       => '10PayLaterTrml',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'otp_attempts'      => null,
        'wallet'            => 'epaylater'
    ],

    'testPaymentForSubMerchant' => [
        'amount'            => 50000,
        'method'            => 'paylater',
        'status'            => 'captured',
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
        'contact'           => '+918602579721',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'paylater',
        'terminal_id'       => '10PayLaterTrml',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'otp_attempts'      => null,
        'wallet'            => 'epaylater'
    ],

    'testPaymentCardlessEmiEntity'   => [
        'provider'              => 'epaylater',
        'currency'              => 'INR',
//        'gateway_reference_id'  => '12345678',
        'status'                => 'authorized',
        'error_code'            => null,
        'error_description'     => null,
        'action'                => 'authorize',
        'amount'                => '50000',
        'gateway'               => 'paylater',
        'contact'               => '+918602579721',
        'refund_id'             => null,
        'entity'                => 'cardless_emi'
    ],

    'testPaymentCaptureEntity' => [
        'action'                => 'capture',
        'amount'                => '50000',
        'currency'              => 'INR',
        'entity'                => 'cardless_emi',
        'provider'              => 'epaylater',
        'status'                => 'captured',
        'error_code'            => null,
        'error_description'     => null,
        'gateway'               => 'paylater',
        'contact'               => '+918602579721',
        'refund_id'             => null,
    ],

    'testPaymentRefundEntity' => [
        'action'                => 'refund',
        'amount'                => '50000',
        'currency'              => 'INR',
        'entity'                => 'cardless_emi',
        'provider'              => 'epaylater',
        'status'                => 'Success',
        'error_code'            => null,
        'error_description'     => null,
        'gateway'               => 'paylater',
        'contact'               => '+918602579721',
    ],

    'testAccountDoesNotExist' => [
        'request' => [
            'content' => [
                'method'    => $this->method,
                'provider'  => $this->provider,
                'amount'    => 100.00,
            ],
            'method' => 'GET',
            'url'    => '/customers/status/9918899029',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYLATER_USER_DOES_NOT_EXIST,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYLATER_USER_DOES_NOT_EXIST,
        ],
    ],

    'testFetchTokenFailed' => [
        'request'   => [
            'method'    => 'POST',
            'url'       => '/payments',
            'content'   => [
                'amount'    => 50000,
                'currency'  => 'INR',
                'email'     => 'a@b.com',
                'contact'   => '+918602579721',
                'notes'     => [
                    'merchant_order_id' => 'random order id',
                ],
                'description'   => 'random description',
                'method'        => $this->method,
                'provider'      => $this->provider,
            ]
        ],
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
        'request'   => [
            'method'    => 'POST',
            'url'       => '/payments',
            'content'   => [
                'amount'    => 50000,
                'currency'  => 'INR',
                'email'     => 'a@b.com',
                'contact'   => '+918602579721',
                'notes'     => [
                    'merchant_order_id' => 'random order id',
                ],
                'description'   => 'random description',
                'method'        => $this->method,
                'provider'      => $this->provider,
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "Payment failed",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED
        ],
    ],

    'testFailedCapture'  => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payments/' . 'id' . '/capture',
            'content' => [
                'amount'    => '50000',
                'currency'  => 'INR',
                ],
        ],
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

    'testInvalidOtt'   => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Token provided is invalid for paylater',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],
];
