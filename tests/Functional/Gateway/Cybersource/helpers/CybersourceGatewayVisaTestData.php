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

    'testEnrolledCardUnavailableAuthentication' => [
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

    'testEnrollmentErrorCheckWithErrorResponse' => [
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

    'testEnrollmentErrorCheckWithIncorrectConf' => [
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
