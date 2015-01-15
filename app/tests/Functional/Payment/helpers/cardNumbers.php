<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\HdfcGateway\HdfcGatewayErrorCode;

//contain array of test cards
return [
    'testShortCardNumber' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'number',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            'field' => 'number'
        ],
    ],

    'testNonNumericCardNumber' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '2123567890121s34',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'number',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCardNumberWithSpaces' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '40 1 2001 0384 43 33 5',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'authorized',
            ],
            'status_code' => 200,
        ],
    ],

    'testLongCardNumber' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001036275556243234234',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'number',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testNonLuhnCardNumber' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '12334567890123456',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'number',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testInvalidCardExpiryMonth' => [
        'request' => [
            'content' => [
                'card' => [
                    'expiry_month' => 13,
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'expiry_month',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testInvalidCardExpiryYear' => [
        'request' => [
            'content' => [
                'card' => [
                    'expiry_year' => 2012,
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'expiry_year',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testInvalidCardExpiryDate' => [
        'request' => [
            'content' => [
                'card' => [
                    'expiry_month' => 1,
                    'expiry_year' => 2015,
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CARD_INVALID_EXPIRY_DATE,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_EXPIRY_DATE,
        ],
    ],

    'testUnsupportedCardNetworks' => [
        'request' => [
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED,
        ],
    ],

    'testDescriptionMissing' => [
        'request' => [
            'method' => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'authorized',
                'description' => null
            ],
            'status_code' => 200,
        ],
    ],

    'testNotesMissing' => [
        'request' => [
            'method' => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'authorized',
                'notes' => array(),
            ],
            'status_code' => 200,
        ],
    ],
];
