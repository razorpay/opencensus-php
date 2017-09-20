<?php

namespace RZP\Tests\Functional\Gateway\Hitachi;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
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
        'channel'           => 'kotak',
        'settled'           => false,
        'settlement_id'     => null,
        'reconciled_at'     => null,
        'entity'            => 'transaction',
        'admin'             => true,
    ],

    'testHitachiAuthEntity' => [
        'refund_id' => null,
        'acquirer' => 'rbl',
        'action' => 'authorize',
        'received' =>  true,
        'amount' => 50000,
        'currency' => 'INR',
        'pRespCode' => '00',
        'pAuthStatus' => null,
        'entity' => 'hitachi',
        'admin' => true,
    ],

    'testHitachiRefundEntity' => [
        'acquirer' => 'rbl',
        'action' => 'refund',
        'amount' => 50000,
        'currency' => 'INR',
        'pRespCode' => '00',
        'pAuthStatus' => null,
        'entity' => 'hitachi',
        'admin' => true,
    ],

    'testHitachiCaptureEntity' => [
        'refund_id' => null,
        'acquirer' => 'rbl',
        'action' => 'capture',
        'received' =>  true,
        'amount' => 50000,
        'currency' => 'INR',
        'pRespCode' => '00',
        'pAuthStatus' => null,
        'entity' => 'hitachi',
        'admin' => true,
    ],
    'testNotEnrolledCard' => [
        'merchant_id' => '10000000000000',
        'amount' => 50000,
        'method' => 'card',
        'status' => 'captured',
        'two_factor_auth' => 'not_applicable',
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
        'gateway'           => 'hitachi',
        'terminal_id'       => '100HitachiTmnl',
        'signed'            => false,
        'verified'          => null,
        'fee'               => 1000,
        'service_tax'       => 0,
        'tax'               => 0,
        'entity'            => 'payment',
    ],
    'testSuccessful13DigitPanForEnrolledCard' => [
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
        'gateway'           => 'hitachi',
        'terminal_id'       => '100HitachiTmnl',
        'signed'            => false,
        'verified'          => null,
        'fee'               => 1000,
        'service_tax'       => 0,
        'tax'               => 0,
        'entity'            => 'payment',
    ],
    'testPaymentEnrollEntity' => [
        'entity'      => 'hitachi',
        'action'      => 'authorize',
        'received'    => true,
        'amount'      => 50000,
        'currency'    => 'INR',
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

    'testInvalidJson' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_INVALID_FORMAT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\GatewayErrorException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_FORMAT,
            'gateway_error_code'    => '30',
        ],
    ],

    'testMismatchVerify' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED ,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\PaymentVerificationException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
        ],
    ],
];
