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
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED,
        ],
    ],

    'testUnavailableAuthentication' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED,
        ],
    ],

    'testEnrolledIncompleteAuthentication' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED,
        ],
    ],

    'testAuthenticationError' => [
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

    'testUnsuccessfulAuthenticationUserFailed' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED,
        ],
    ],

    'testEnrolledSuccessfulAuthentication' => [
        'action'               => 'authorize',
        'received'             => true,
        'refund_id'            => null,
        'auth_data'            => 'jELUbgG+Tfj0AREACMLdCae+oIs=',
        'commerce_indicator'   => 'spa',
        'amount'               => 50000,
        'pares_status'         => 'Y',
        'status'               => 'authorized',
        'avsCode'              => 'Y',
        'cardCategory'         => null,
        'cardGroup'            => null,
        'cvCode'               => 'M',
        'veresEnrolled'        => 'Y',
        'eci'                  => '02',
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
        'auth_data'            => 'hsjuQljfI86bAQAFvVQGaWsBPwI=',
        'commerce_indicator'   => 'spa',
        'amount'               => 50000,
        'pares_status'         => 'A',
        'status'               => 'authorized',
        'avsCode'              => 'Y',
        'cvCode'               => 'M',
        'veresEnrolled'        => 'Y',
        'eci'                  => '01',
        'cavv'                 => null,
        'collection_indicator' => null,
        'capture_ref'          => null,
        'merchantAdviceCode'   => '01',
        'processorResponse'    => '00',
        'reason_code'          => 100,
        'entity'               => 'cybersource',
        'admin'                => true,
    ]
];
