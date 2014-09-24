<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'testInvalidEmailInPayment' => [
        'request' => [
            'content' => [
                'email' => 'abc',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::FIELD_ERROR_INVALID_EMAIL,
                    'field' => 'email',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\FieldErrorException',
            'internal_error_code' => ErrorCode::FIELD_ERROR_INVALID_EMAIL,
        ],
    ],

    'testEmailMissing' => [
        'request' => [
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::FIELD_ERROR_INVALID_EMAIL,
                    'field' => 'email',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\FieldErrorException',
            'internal_error_code' => ErrorCode::FIELD_ERROR_INVALID_EMAIL,
        ],
    ],

    'testContactTooShort' => [
        'request' => [
            'content' => [
                'contact' => '4012',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::FIELD_ERROR_INVALID_CONTACT,
                    'field' => 'contact',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\FieldErrorException',
            'internal_error_code' => ErrorCode::FIELD_ERROR_INVALID_CONTACT,
        ],
    ],

    'testContactTooLong' => [
        'request' => [
            'content' => [
                'contact' => '1234567890110',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::FIELD_ERROR_INVALID_CONTACT,
                    'field' => 'contact',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\FieldErrorException',
            'internal_error_code' => ErrorCode::FIELD_ERROR_INVALID_CONTACT,
        ],
    ],

    'testContactWithDashAndBracket' => [
        'request' => [
            'content' => [
                'contact' => '+1234-(456)-(789)',
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'authorized'
            ],
        ],
    ],

    'testContactWithPlusAndNumbers' => [
        'request' => [
            'content' => [
                'contact' => '+1234-(456)-(789)',
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'authorized'
            ],
        ],
    ],

    'testNonInrCurrency' => [
        'request' => [
            'content' => [
                'currency' => 'USD',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'currency',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CURRENCY_NOT_SUPPORTED,
        ],
    ],

    'testPaymentCardAsString' => [
        'request' => [
            'content' => [
                'card' => 'dfdf',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'card',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_IS_NOT_ARRAY,
        ],
    ],

    'testCardMissing' => [
        'request' => [
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'card',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_NOT_PROVIDED,
        ],
    ],

    'testAmountBelowMin' => [
        'request' => [
            'content' => [
                'amount' => '99',
            ],
        ],
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
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAmountVeryHigh' => [
        'request' => [
            'content' => [
                'contact' => '1000000000000000000000000',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::FIELD_ERROR_INVALID_CONTACT,
                    'field' => 'contact',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\FieldErrorException',
            'internal_error_code' => ErrorCode::FIELD_ERROR_INVALID_CONTACT,
        ],
    ],

    'testAmountNonNumeric' => [
        'request' => [
            'content' => [
                'amount' => '1 a non numeric amount',
            ],
        ],
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
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAmountMissing' => [
        'request' => [
            'content' => [
                'amount' => null,
            ],
        ],
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
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDescriptionMissing' => [
        'request' => [
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'authorized',
                'description' => null
            ],
            'status_code' => 200,
        ],
    ],

    'testDescriptionAsArray' => [
        'request' => [
            'content' => [
                'description' => [],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'description',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_DESCRIPTION_SHOULD_BE_STRING,
        ],
    ],

    'testDescriptionTooLarge' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'description',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_DESCRIPTION_TOO_LARGE,
        ],
    ],

    'testUdfMissing' => [
        'request' => [
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            'status' => 'authorized',
            'udf' => array(),
            ],
            'status_code' => 200,
        ],
    ],

    'testUdfStringNotArray' => [
        'request' => [
            'content' => [
                'udf' => 'a udf string.. wooohoooooooo',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'udf',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_UDF_SHOULD_BE_ARRAY,
        ],
    ],

    'testExcessValuesInUdf' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'udf',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_UDF_TOO_MANY_KEYS,
        ],
    ],

    'testArrayInUdfValue' => [
        'request' => [
            'content' => [
                'udf' => [
                    'array' => [
                        '1' => '1',
                        '2' => '2'
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'udf',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_UDF_VALUE_CANNOT_BE_ARRAY,
        ],
    ],

    'testArrayInUdfKey' => [
        'request' => [
            'content' => [
                'udf' => [
                    [0,1],
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'udf',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_UDF_VALUE_CANNOT_BE_ARRAY
        ],
    ],

    'testUdfKeyLarge' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'udf',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_UDF_KEY_TOO_LARGE,
        ],
    ],

    'testUdfValueLarge' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'udf',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_UDF_VALUE_TOO_LARGE,
        ],
    ],
];
