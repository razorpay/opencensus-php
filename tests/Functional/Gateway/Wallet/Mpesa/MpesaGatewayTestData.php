<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testOtpPayment' => [
        'merchant_id'     => '10000000000000',
        'amount'          => 50000,
        'currency'        => 'INR',
        'base_amount'     => 50000,
        'status'          => 'authorized',
        'two_factor_auth' => 'passed',
        'method'          => 'wallet',
        'wallet'          => 'mpesa',
        'gateway'         => 'wallet_mpesa',
        'terminal_id'     => '100VodaMpesaTl',
    ],

    'testOtpPaymentWalletEntity' => [
        'action'   => 'otp_generate',
        'received' => true,
        'wallet'   => 'mpesa',
        'amount'   => 500
    ],

    'testAuthFailure' => [
        'response' => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::GATEWAY_ERROR,
                    'description'   => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
            'gateway_error_code'  => '104',
            'gateway_error_desc'  => 'Mobile number not found'
        ],
    ],

    'testOtpPaymentVerify' => [
        'payment'                   => [
            'verified'              => 1
        ],
        'gateway'                   => [
            'apiSuccess'            => true,
            'gatewaySuccess'        => true,
            'status'                => 'status_match',
            'gateway'               => 'wallet_mpesa',
            'verifyResponseContent' => [
                'statusCode'        => '100',
                'reason'            => 'SUCCESS',
            ],
        ],
    ],
];
