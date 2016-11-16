<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testAuthenticationSuccessfulInvalidPares' => [
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
        ],
    ],

    'testAuthenticationError' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR_PAYMENT_AUTHENTICATION_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class' => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_PAYMENT_AUTHENTICATION_ERROR,
        ],
    ],

    'testEnrolledIncompleteAuthentication' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR_PAYMENT_AUTHENTICATION_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class' => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_PAYMENT_AUTHENTICATION_ERROR,
        ],
    ],

    'testUnsuccessfulAuthenticationUserFailed' => [
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
        ],
    ],

    'testEnrolledCardSuccessfulAuthentication' => [
        'action'               => 'authorize',
        'received'             => true,
        'refund_id'            => null,
        'auth_data'            => null,
        'commerce_indicator'   => 'vbv',
        'amount'               => 50000,
        'pares_status'         => 'Y',
        'status'               => 'authorized',
        'avsCode'              => 'Y',
        'cardCategory'         => null,
        'cardGroup'            => null,
        'cvCode'               => 'M',
        'veresEnrolled'        => 'Y',
        'eci'                  => '05',
        'collection_indicator' => null,
        'capture_ref'          => null,
        'merchantAdviceCode'   => '01',
        'processorResponse'    => '00',
        'reason_code'          => 100,
        'entity'               => 'cybersource',
        'admin'                => true,
    ],

    'testEnrolledAttemptsProcessing' => [
        'action'               => 'authorize',
        'received'             => true,
        'refund_id'            => null,
        'auth_data'            => null,
        'commerce_indicator'   => 'vbv_attempted',
        'amount'               => 50000,
        'pares_status'         => 'A',
        'status'               => 'authorized',
        'avsCode'              => 'Y',
        'cvCode'               => 'M',
        'veresEnrolled'        => 'Y',
        'eci'                  => '06',
        'cavv'                 => 'BwAQAgJ4IAUFBwdik3ggEETHTsU=',
        'collection_indicator' => null,
        'capture_ref'          => null,
        'merchantAdviceCode'   => '01',
        'processorResponse'    => '00',
        'reason_code'          => 100,
        'entity'               => 'cybersource',
        'admin'                => true,
    ]
];