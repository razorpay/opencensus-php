<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testPaymentNetbankingEntity' => [
        'action'          => 'authorize',
        'amount'          => 20,
        'bank'            => 'ICIC',
        'received'        => true,
        'error_message'   => null,
        'schedule_status' => 'Y',
        'status'          => 'Y',
        'entity'          => 'netbanking',
    ],

    'testEMandateInitialPaymentFailure' => [
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
            'class'                 => RZP\Exception\GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        ],
    ],

    'testUnknownReasonSecondRecurringFailure' => [
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
            'class'                 => RZP\Exception\GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            'gateway_error_code'    => 'N',
            'gateway_error_desc'    => 'Random Error',
        ],
    ],

    'testDebitRequestFailure' => [
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
            'class'                 => RZP\Exception\GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        ],
    ],

    'testSiRecurringMessageNotSet' => [
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
            'class'                 => RZP\Exception\LogicException::class,
            'internal_error_code'   => ErrorCode::SERVER_ERROR_LOGICAL_ERROR,
        ],
    ],

    'testAuthSecondRecurringNullGatewayToken' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_GATEWAY_TOKEN_EMPTY,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => RZP\Exception\BadRequestException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_GATEWAY_TOKEN_EMPTY,
        ],
    ],

    'testNullSecondRecurringResponse' => [
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
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
        ],
    ],

    'testEMandateScheduledPaymentFailure' => [
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
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_PREMATURE_SI_EXECUTION,
            'gateway_error_code'    => 'N',
            'gateway_error_desc'    => 'PaymentDateOverdue',
        ],
    ],

    'testPaymentVerifyFailed' => [
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
            'class'               => RZP\Exception\PaymentVerificationException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED
        ],
    ],

    'testScheduledPaymentWithRejectedToken' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_TOKEN_NOT_ENABLED_FOR_RECURRING,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => RZP\Exception\BadRequestException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_TOKEN_NOT_ENABLED_FOR_RECURRING,
        ],
    ],

    'testPaymentAmountGreaterThanTokenMaxAmount' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_AMOUNT_GREATER_THAN_TOKEN_MAX_AMOUNT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => RZP\Exception\BadRequestException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_GREATER_THAN_TOKEN_MAX_AMOUNT,
        ],
    ],

    'testTokenPassedInFirstRecurringPayment' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_EMANDATE_TOKEN_PASSED_IN_FIRST_RECURRING,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => RZP\Exception\BadRequestException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_EMANDATE_TOKEN_PASSED_IN_FIRST_RECURRING,
        ],
    ],

    'testAuthorizeFailedRegistrationPayment' => [
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
            'class'                 => RZP\Exception\GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        ],
    ],
];
