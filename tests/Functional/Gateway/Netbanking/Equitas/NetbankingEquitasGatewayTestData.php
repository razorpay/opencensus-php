<?php

use Carbon\Carbon;

use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
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

    'testNetbankingEquitasCombinedFile' => [
        'request' => [
            'content' => [
                'type'     => 'combined',
                'targets'  => ['equitas'],
                'begin'    => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'      => Carbon::tomorrow(Timezone::IST)->getTimestamp()
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'admin' => true,
                'items' => [
                    [
                        'status'              => 'file_sent',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'refunds@razorpay.com',
                        'type'                => 'combined',
                        'target'              => 'equitas',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ]
            ]
        ]
    ],

    'testPaymentInvalidPaymentEntity' => [
        'merchant_id'            => "10000000000000",
        'amount'                 => 50000,
        'currency'               => "INR",
        'base_amount'            => 50000,
        'method'                 => "netbanking",
        'status'                 => "captured",
        'two_factor_auth'        => "unavailable",
        'order_id'               => null,
        'invoice_id'             => null,
        'transfer_id'            => null,
        'payment_link_id'        => null,
        'receiver_id'            => null,
        'receiver_type'          => null,
        'international'          => false,
        'amount_authorized'      => 50000,
        'amount_refunded'        => 0,
        'base_amount_refunded'   => 0,
        'amount_transferred'     => 0,
        'amount_paidout'         => 0,
        'refund_status'          => null,
        'description'            => "random description",
        'card_id'                => null,
        'subscription_id'        => null,
        'bank'                   => "ESFB",
        'wallet'                 => null,
        'vpa'                    => null,
        'on_hold'                => false,
        'on_hold_until'          => null,
        'emi_plan_id'            => null,
        'emi_subvention'         => null,
        'error_code'             => null,
        'internal_error_code'    => null,
        'error_description'      => null,
        'cancellation_reason'    => null,
        'customer_id'            => null,
        'global_customer_id'     => null,
        'app_token'              => null,
        'token_id'               => null,
        'global_token_id'        => null,
        'email'                  => "a@b.com",
        'contact'                => "+919918899029",
        'notes'                  => [
            'merchant_order_id' => "random order id",
        ],
        'auto_captured'          => false,
        'gateway'                => "netbanking_equitas",
        'terminal_id'            => "100NbEsfbTrmnl",
        'authentication_gateway' => null,
        'batch_id'               => null,
        'reference1'             => "AB1234",
        'reference2'             => null,
        'cps_route'              => 0,
        'signed'                 => false,
        'verified'               => 0,
        'gateway_captured'       => true,
        'verify_bucket'          => null,
        'verify_at'              => null,
        'callback_url'           => null,
        'fee'                    => 1476,
        'mdr'                    => 1476,
        'tax'                    => 226,
        'otp_attempts'           => null,
        'otp_count'              => null,
        'recurring'              => false,
        'save'                   => false,
        'late_authorized'        => false,
        'convert_currency'       => null,
        'disputed'               => false,
        'recurring_type'         => null,
        'auth_type'              => null,
        'acknowledged_at'        => null,
        'refund_at'              => null,
        'fee_bearer'             => "platform",
        'reference13'            => null,
        'reference14'            => null,
        'settled_by'             => "Razorpay",
        'reference16'            => null,
        'reference17'            => null,
        'captured'               => true,
        'gateway_provider'       => "Razorpay",
    ],
];
