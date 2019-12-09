<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testPayment' => [
        'merchant_id'       => '10000000000000',
        'amount'            => 0,
        'method'            => 'upi',
        'status'            => 'created',
        'amount_authorized' => 0,
        'amount_refunded'   => 0,
        'refund_status'     => null,
        'currency'          => 'INR',
        'description'       => 'random description',
        'card_id'           => null,
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'upi_mindgate',
        'signed'            => false,
        'verified'          => null,
    ],
    'testPaymentMozartEntity' => [
        'action'            => 'mandate_create',
        'gateway'           => 'upi_mindgate',
        'amount'            => 0,
    ],
    'testMandateCreateFailed' => [
        'response'  => [
            'content'   => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED
                ]
            ],
            'status_code'           => 400
        ],
        'exception' => [
            'class'                 => RZP\Exception\GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_FAILED
        ]
    ],
    'testMandateExecuteAmountGreaterThanMaxTokenAmount' => [
        'response'  => [
            'content'   => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_AMOUNT_GREATER_THAN_TOKEN_MAX_AMOUNT
                ]
            ],
            'status_code'           => 400
        ],
        'exception' => [
            'class'                 => RZP\Exception\BadRequestException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_GREATER_THAN_TOKEN_MAX_AMOUNT
        ]
    ],
    'testMandateExecuteBeforeStartTime' => [
        'response'  => [
            'content'   => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_MANDATE_EXECUTION_ATTEMPT_BEFORE_START_TIME
                ]
            ],
            'status_code'           => 400
        ],
        'exception' => [
            'class'                 => RZP\Exception\BadRequestException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_MANDATE_EXECUTION_ATTEMPT_BEFORE_START_TIME
        ]
    ],
    'testMandateExecuteForTokenNotConfirmed' => [
        'response'  => [
            'content'   => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_UNCONFIRMED_TOKEN_PASSED_IN_SECOND_RECURRING
                ]
            ],
            'status_code'           => 400
        ],
        'exception' => [
            'class'                 => RZP\Exception\BadRequestException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_UNCONFIRMED_TOKEN_PASSED_IN_SECOND_RECURRING
        ]
    ],
    'testMandateExecuteFailed' => [
        'response'  => [
            'content'   => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED
                ]
            ],
            'status_code'           => 400
        ],
        'exception' => [
            'class'                 => RZP\Exception\GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_FAILED
        ]
    ],
    'testMandateUpdateNotConfirmedToken' => [
        'response'  => [
            'content'   => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_UPDATE_NOT_CONFIRMED_TOKEN
                ]
            ],
            'status_code'           => 400
        ],
        'exception' => [
            'class'                 => RZP\Exception\BadRequestException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_UPDATE_NOT_CONFIRMED_TOKEN
        ]
    ],
    'testMandateUpdateExpiredToken' => [
        'response'  => [
            'content'   => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_UPDATE_EXPIRED_TOKEN
                ]
            ],
            'status_code'           => 400
        ],
        'exception' => [
            'class'                 => RZP\Exception\BadRequestException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_UPDATE_EXPIRED_TOKEN
        ]
    ],
    'testMandateExecuteSameTokenTwice' => [
        'response'  => [
            'content'   => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_TOKEN_STATUS_ALREADY_PAID
                ]
            ],
            'status_code'           => 400
        ],
        'exception' => [
            'class'                 => RZP\Exception\BadRequestException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_TOKEN_STATUS_ALREADY_PAID
        ]
    ],
];
