<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'testJsonpPayment' =>[
        'request' => [
            'url' => '/payments/create/jsonp',
            'method' => 'GET',
            'content' => [
                'callback' => 'abcdefghijkl',
                '_' => '',
            ],
        ],
        'response' => [
            'content' => [
                'http_status_code' => 200
            ]
        ],
        'jsonp' => true
    ],

    'testInvalidEmailInPayment' => [
        'request' => [
            'content' => [
                'email' => 'abc',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'email',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
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
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'email',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
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
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'contact',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_MIN_TEN_DIGITS,
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
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'contact',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_MAX_TWELVE_DIGITS,
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
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CURRENCY_NOT_SUPPORTED,
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
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MIN_AMOUNT,
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
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'contact',
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CONTACT_MAX_TWELVE_DIGITS
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_MAX_TWELVE_DIGITS,
        ],
    ],

    'testAmountLessThan50ForNetBanking' => [
        'request' => [
            'content' => [
                'method' => 'netbanking',
                'amount' => '4999',
                'bank' => 'SBIN'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'amount',
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_ATOM_NET_BANKING_MIN_AMOUNT_FIFTY,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ATOM_NET_BANKING_MIN_AMOUNT_FIFTY,
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
            'class' => 'EE\Exception\BadRequestValidationFailureException',
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
            'class' => 'EE\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
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
                    'description' => PublicErrorDescription::BAD_REQUEST_DESCRIPTION_SHOULD_BE_STRING,
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

    'testNotesStringNotArray' => [
        'request' => [
            'content' => [
                'notes' => 'a notes string.. wooohoooooooo',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'notes',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NOTES_SHOULD_BE_ARRAY,
        ],
    ],

    'testExcessValuesInNotes' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'notes',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NOTES_TOO_MANY_KEYS,
        ],
    ],

    'testArrayInNotesValue' => [
        'request' => [
            'content' => [
                'notes' => [
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
                    'field' => 'notes',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NOTES_VALUE_CANNOT_BE_ARRAY,
        ],
    ],

    'testArrayInNotesKey' => [
        'request' => [
            'content' => [
                'notes' => [
                    [0,1],
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'notes',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NOTES_VALUE_CANNOT_BE_ARRAY
        ],
    ],

    'testNotesKeyLarge' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'notes',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NOTES_KEY_TOO_LARGE,
        ],
    ],

    'testNotesValueLarge' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'notes',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NOTES_VALUE_TOO_LARGE,
        ],
    ],
];
