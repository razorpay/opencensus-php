<?php

namespace RZP\Tests\Functional\Gateway\Hitachi;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testPaymentEnrollEntity' => [
        'entity'      => 'hitachi',
        'action'      => 'authorize',
        'received'    => true,
        'amount'      => 50000,
        'currency'    => 'INR',
        'pEnrolled'   => 'Y',
        'pAuthStatus' => 'Y',
        'pECI'        => '06',
        'pALGO'       => 2,
    ],

    'testPaymentNotEnrollEntity' => [
        'entity'      => 'hitachi',
        'action'      => 'authorize',
        'received'    => true,
        'amount'      => 50000,
        'currency'    => 'INR',
        'pEnrolled'   => 'N',
        'pXID'        => null,
        'pCAVV2'      => null,
        'pUCAF'       => null,
        'pAuthStatus' => null,
        'pECI'        => null,
        'pALGO'       => null,
    ],

    'testPaymentEnrollUnavailableEntity' => [
        'entity'      => 'hitachi',
        'action'      => 'authorize',
        'received'    => false,
        'amount'      => 50000,
        'currency'    => 'INR',
        'pRespCode'   => null,
        'pXID'        => null,
        'pCAVV2'      => null,
        'pUCAF'       => null,
        'pAuthStatus' => null,
        'pECI'        => null,
        'pALGO'       => null,
        'pAuthID'     => null,
        'pRRN'        => null,
    ],

    'testPaymentRefundEntity' => [
        'merchant_id'      => '10000000000000',
        'currency'         => 'INR',
        'gateway_refunded' => true,
        'entity'           => 'refund',
        'status'           => 'processed',
    ],

    'testPartialRefund' => [
        'merchant_id'      => '10000000000000',
        'amount'           => 50000,
        'currency'         => 'INR',
        'base_amount'      => 50000,
        'gateway_refunded' => true,
        'entity'           => 'refund',
        'status'           => 'processed',
    ],

    'testPaymentRefundFailureEntity' => [
        'merchant_id'      => '10000000000000',
        'amount'           => 50000,
        'currency'         => 'INR',
        'base_amount'      => 50000,
        'gateway_refunded' => false,
        'entity'           => 'refund',
        'status'           => 'failed',
    ],

    'testPaymentCaptureEntity' => [
        'action'    => 'capture',
        'received'  => true,
        'amount'    => 50000,
        'currency'  => 'INR',
        'pRespCode' => '00',
        'entity'    => 'hitachi'
    ],

    'testCaptureFailureEntity' => [
        'action'    => 'capture',
        'received'  => true,
        'amount'    => 50000,
        'currency'  => 'INR',
        'pRespCode' => '79',
        'entity'    => 'hitachi'
    ],

    'testPaymentEnrollUnavailable' => [
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
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_AUTHENTICATION_NOT_AVAILABLE,
        ],
    ],

    'testPaymentStrangeEnrollResponse' => [
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
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_PAYMENT_AUTHENTICATION_ERROR,
            'gateway_error_desc'    => 'Unexpected response',
        ],
    ],

    'testVerifyMismatch' => [
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

    'testFailureException' => [
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
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
            'gateway_error_code'    => '06',
            'gateway_error_desc'    => 'Error',
        ],
    ],

    'testCaptureFailureException' => [
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
            'class'                 => 'RZP\Exception\GatewayErrorException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            'gateway_error_code'    => '79',
            'gateway_error_desc'    => 'No Response Message found in mapping',
        ],
    ],

    'testCallbackWithEmptyResponse' => [
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
            'class'                 => 'RZP\Exception\GatewayErrorException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            'gateway_error_code'    => null,
            'gateway_error_desc'    => 'The pa res field is required.',
        ],
    ],

    'testSavedCardPayment' => [
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
        'gateway'             => 'hitachi',
        'terminal_id'         => '100HitachiTmnl',
        'recurring'           => false,
        'save'                => false,
        'late_authorized'     => false,
        'captured'            => false,
        'entity'              => 'payment',
        'admin'               => true
    ],

    'testCardAuthFailure' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_PIN_INCORRECT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\GatewayErrorException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_PIN_INCORRECT,
            'gateway_error_code'    => '55',
            'gateway_error_desc'    => 'Incorrect PIN',
        ],
    ],

    'testVerifyFailedResponseContent' => [
        'status'  => 'status_match',
        'gateway' => 'hitachi',
        'verifyResponseContent' => [
            'pTranType'   => 'TS',
            'pTranAmount' => 500,
            'pRespCode'   => '55',
            'pStatus'     => 'F',
        ],
    ],

    'testAuthFailedVerifyFailedPaymentEntity' => [
        'amount'              => 50000,
        'status'              => 'failed',
        'error_code'          => 'BAD_REQUEST_ERROR',
        'internal_error_code' => 'BAD_REQUEST_PAYMENT_PIN_INCORRECT',
        'error_description'   => 'Incorrect Pin',
        'verified'            => 1,
    ],

    'testEciValue07VisaPayment' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\GatewayErrorException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED,
            'gateway_error_code'    => '07',
            'gateway_error_desc'    => "pECI value shouldn't be 7 for Visa",
        ],
    ],

    'testPaymentEciFailEntity' => [
        'entity'      => 'hitachi',
        'action'      => 'authorize',
        'received'    => false,
        'amount'      => 50000,
        'currency'    => 'INR',
        'pEnrolled'   => 'Y',
        'pAuthStatus' => 'Y',
        'pALGO'       => 2,
    ],

    'testEciValue07MasterCardPayment' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\GatewayErrorException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED,
            'gateway_error_code'    => '00',
            'gateway_error_desc'    => "pECI value shouldn't be 0 or 7 for MasterCard",
        ],
    ],
];
