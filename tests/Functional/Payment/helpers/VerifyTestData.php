<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testVerifyMultipleFailedPaymentsByVerifyAt' => [
        'request' => [
            'url'    => '/payments/verify/all',
            'method' => 'post',
            'content' => [
                'delay' => 300
            ],
        ],
        'response' => [
            'content' => [
                'not_applicable' => 0,
                'locked_count'   => 0,
                'authorized'     => 0,
                'success'        => 2,
                'timeout'        => 0,
                'error'          => 0,
                'unknown'        => 0
            ],
        ],
    ],

    'testIciciBqrVerify' => [
        'request' => [
            'url'    => '/payments/captured/verify',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'not_applicable' => 0,
                'locked_count'   => 0,
                'authorized'     => 0,
                'success'        => 2,
                'timeout'        => 0,
                'error'          => 0,
                'unknown'        => 0
            ],
        ],
    ],

    'testNotVerifiablePaymentForCaptureVerify' => [
        'request' => [
            'url'    => '/payments/captured/verify',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'not_applicable' => 2,
                'locked_count'   => 0,
                'authorized'     => 0,
                'success'        => 1,
                'timeout'        => 0,
                'error'          => 0,
                'unknown'        => 0
            ],
        ],
    ],

    'testIsgBqrVerify' => [
        'request' => [
            'url'    => '/payments/captured/verify',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'not_applicable' => 0,
                'locked_count'   => 0,
                'authorized'     => 0,
                'success'        => 2,
                'timeout'        => 0,
                'error'          => 0,
                'unknown'        => 0
            ],
        ],
    ],

    'testAmexVerify' => [
        'request' => [
            'url'    => '/payments/captured/verify',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'not_applicable' => 0,
                'locked_count'   => 0,
                'authorized'     => 0,
                'success'        => 2,
                'timeout'        => 0,
                'error'          => 0,
                'unknown'        => 0
            ],
        ],
    ],

    'testHitachiUpiVerifyShdFail' => [
        'request' => [
            'url'    => '/payments/captured/verify',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'not_applicable' => 1,
                'locked_count'   => 0,
                'authorized'     => 0,
                'success'        => 1,
                'timeout'        => 0,
                'error'          => 0,
                'unknown'        => 0
            ],
        ],
    ],

    'testTimeoutPaymentVerify' => [
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
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ACTION,
        ],
    ],

    'testCapturedPaymentVerify' => [
        'response'  => [
            'content'     => [
            ],
            'status_code' => 200,
        ],

    ],

    'testInvalidFilter' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_INVALID_PARAMETERS,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_INVALID_PARAMETERS,
        ],
    ],

    'testVerifyGooglePayCardPaymentNotFound' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_NOT_FOUND,
                    'reason_code'   => 'PRAZR070',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_NOT_FOUND,
        ],
    ],

    'testVerifyGooglePayCardPaymentNotOfGooglePay' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_NOT_FOUND,
                    'reason_code'   => 'PRAZR070'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_NOT_FOUND,
        ],
    ],
];
