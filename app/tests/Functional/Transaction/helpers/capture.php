<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'capture' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'transaction',
            ],
        ],
    ],

    'captureTwice' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TRANSACTION_ALREADY_CAPTURED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TRANSACTION_ALREADY_CAPTURED
        ],
    ],

    'captureWithLessAmountThanAuth' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'amount' => 10000
                ],
            ],
    ],

    'captureWithMoreAmountThanAuth' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CAPTURE_AMOUNT_GREATER_THAN_AUTH,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CAPTURE_AMOUNT_GREATER_THAN_AUTH
        ],

    ],
    'captureWithRandomId' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_ID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID
        ],
    ],

    'captureWithRefunded' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TRANSACTION_CAPTURE_ONLY_AUTHORIZED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TRANSACTION_CAPTURE_ONLY_AUTHORIZED
        ],
    ],

    'captureWithNoAmount' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'amount',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'captureWithZeroAmount' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'amount',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'captureWithNegativeAmount' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'amount',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'captureWithMinAmountAllowedMinusOne' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'amount',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'captureWithMinAmountAllowed' => [
        'response' => [
            'content' => [
                'status' => 'captured',
                'amount' => 100
                ],
            ],
    ],

    'captureWithOverflowingAmount' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'amount',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'debitCardSuccess4' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001037490014',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'authorized',
            ],
        ],
    ],
    'debitCardSuccess5' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001037141112',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'authorized',
            ],
        ],
    ],
    'paresNotSuccess' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001037461114',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_PARES_NOT_SUCCESFUL,
            'gateway_error_code'  => Hdfc\ErrorCode::GV00004,
        ],
    ],
    'debitCardAuthNotAvailable1' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001037484447',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_AUTHENTICATION_NOT_AVAILABLE,
            'gateway_error_code'  => Hdfc\ErrorCode::FSS0001,
        ],
    ],
    'debitCardAuthNotAvailable2' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001037490006',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_AUTHENTICATION_NOT_AVAILABLE,
            'gateway_error_code'  => Hdfc\ErrorCode::FSS0001,
        ],
    ],
];
