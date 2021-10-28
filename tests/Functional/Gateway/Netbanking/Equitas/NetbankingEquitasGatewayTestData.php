<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Gateway\Netbanking\Equitas\Status;

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
        'bank'              => 'ESFB',
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'netbanking_equitas',
        'signed'            => false,
        'verified'          => null,
    ],

    'testTpvPayment' => [
        'request' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'netbanking',
                'bank'           => 'ESFB',
                'account_number' => '12345678910',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
            ],
        ],
    ],

    'testPaymentNetbankingEntity' => [
        'action'            => 'authorize',
        'bank'              => 'ESFB',
        'received'          => true,
        'error_message'     => null,
    ],

    'testTamperedAmount' => [
        'response'  => [
            'content'     => [
                'error'         => [
                    'code'              => PublicErrorCode::SERVER_ERROR,
                    'description'       => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'                     => 'RZP\Exception\LogicException',
            'internal_error_code'       => ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED,
        ],
    ],

    'testPaymentIdMismatch' => [
        'response'  => [
            'content'     => [
                'error'         => [
                    'code'              => PublicErrorCode::SERVER_ERROR,
                    'description'       => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'                     => 'RZP\Exception\LogicException',
            'internal_error_code'       => ErrorCode::SERVER_ERROR_LOGICAL_ERROR,
        ],
    ],

    'testVerifyInvalidResponse' => [
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

    'testChecksumValidationFailed' => [
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

    'testAuthFailed' => [
        'response'  => [
            'content'     => [
                'error'         => [
                    'code'              => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'       => "Your payment didn't go through as it was declined by the bank. Try another payment method or contact your bank.",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                     => 'RZP\Exception\GatewayErrorException',
            'internal_error_code'       => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        ],
    ],

    'testAuthInvalidStatus' => [
        'response'  => [
            'content'     => [
                'error'         => [
                    'code'              => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'       => PublicErrorDescription::BAD_REQUEST_PAYMENT_INVALID_STATUS,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                     => 'RZP\Exception\GatewayErrorException',
            'internal_error_code'       => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_STATUS,
        ],
    ],

    'testAuthSuccessVerifyFailed' => [
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

    'testCallbackResponseError' => [
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
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        ],
    ],

    'testVerifyChecksumStatusFalse' => [
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
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_CHECKSUM_MATCH_FAILED,
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

    'testVerifyResponseError' => [
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
            'class'                 => 'RZP\Exception\GatewayErrorException',
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
        ],
    ],

    'testPaymentVerifySuccessEntity' => [
        'bank_payment_id' => 'AB1234',
        'status'          => Status::YES,
    ],

    'testPaymentFailedNetbankingEntity' => [
        'bank_payment_id' => null,
        'received'        => false,
        'bank'            => 'ESFB',
        'status'          => null
    ],

    'testAuthSuccessVerifyFailedNetbankingEntity' => [
        'received'        => true,
        'bank'            => 'ESFB',
        'status'          => 'Y'
    ],

    'testPaymentFailedPaymentEntity' => [
        'verified'        => null,
        'bank'            => 'ESFB',
        'status'          => 'failed',
    ],

    'testPaymentErrorPaymentEntity' => [
        'verified'        => 2,
        'bank'            => 'ESFB',
        'status'          => 'captured',
    ],
];
