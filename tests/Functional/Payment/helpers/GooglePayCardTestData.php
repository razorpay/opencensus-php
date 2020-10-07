<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testGooglePayCardCallbackDecryptionFailure' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_DECRYPTION_FAILED,
                    'reason_code'   => 'PRAZR067',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_DECRYPTION_FAILED,
        ],
    ],

    'testGooglePayCardCallbackDecryptionRequestFailure' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_DECRYPTION_FAILED,
                    'reason_code'   => 'PRAZR067',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_DECRYPTION_FAILED,
        ],
    ],

    'testGooglePayCardCallbackDecryptionIncomplete' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_INPUT_VALIDATION_FAILURE,
                    'reason_code'   => 'PRAZR072',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_INPUT_VALIDATION_FAILURE,
        ],
    ],

    'testGooglePayCardCallbackFailureSecondTime' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED,
                    'reason_code'   => 'GWAZR002',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED,
        ],
    ],

    'testGooglePayCardCallbackFailureAmountMismatch' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_AMOUNT_MISMATCH,
                    'reason_code'   => 'PRAZR065'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_AMOUNT_MISMATCH,
        ],
    ],

    'testGooglePayCardCallbackFailureValidationError' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_INPUT_VALIDATION_FAILURE,
                    'reason_code'   => 'PRAZR072'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_INPUT_VALIDATION_FAILURE,
        ],
    ],

    'testGooglePayCardCallbackFailureExtraFieldError' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'ThisField is/are not required and should not be sent',
                    'reason_code'   => 'PRAZR066'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code'   => 'BAD_REQUEST_EXTRA_FIELDS_PROVIDED',
        ],
    ],

    'testGooglePayCardCallbackFailureMessageExpired' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_MESSAGE_EXPIRED,
                    'reason_code'   => 'PRAZR069',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_MESSAGE_EXPIRED,
        ],
    ],

    'testGooglePayCardCallbackFailureInvalidCardNumber' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_INPUT_VALIDATION_FAILURE,
                    'reason_code'   => 'PRAZR072',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_INPUT_VALIDATION_FAILURE,
        ],
    ],

    'testGooglePayCardCallbackFailurePayment' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED,
                    'reason_code'   => 'GWAZR009'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        ],
    ]
];

