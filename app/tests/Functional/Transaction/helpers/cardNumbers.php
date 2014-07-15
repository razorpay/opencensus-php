<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\HdfcGateway\HdfcGatewayErrorCode;

//contain array of test cards
return [
    'shortCardNumber' => [
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
                    'code' => PublicErrorCode::CARD_ERROR_INVALID_NUMBER,
                    'field' => 'number',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\CardErrorException',
            'code' => ErrorCode::CARD_ERROR_INVALID_NUMBER,
        ],
    ],
    'nonNumericCardNumber' => [
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
                    'code' => PublicErrorCode::CARD_ERROR_INVALID_NUMBER,
                    'field' => 'number',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\CardErrorException',
            'code' => ErrorCode::CARD_ERROR_INVALID_NUMBER,
        ],
    ],
    'cardNumberWithSpaces' => [
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
    'longCardNumber' => [
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
                    'code' => PublicErrorCode::CARD_ERROR_INVALID_NUMBER,
                    'field' => 'number',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\CardErrorException',
            'code' => ErrorCode::CARD_ERROR_INVALID_NUMBER,
        ],
    ],
    'nonLuhnCardNumber' => [
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
                    'code' => PublicErrorCode::CARD_ERROR_INVALID_NUMBER,
                    'field' => 'number',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\CardErrorException',
            'code' => ErrorCode::CARD_ERROR_INVALID_NUMBER,
        ],
    ],
    'invalidCardExpiryMonth' => [
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
                    'code' => PublicErrorCode::CARD_ERROR_INVALID_EXPIRY_MONTH,
                    'field' => 'expiry_month',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\CardErrorException',
            'code' => ErrorCode::CARD_ERROR_INVALID_EXPIRY_MONTH,
        ],
    ],
    'invalidCardExpiryYear' => [
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
                    'code' => PublicErrorCode::CARD_ERROR_INVALID_EXPIRY_YEAR,
                    'field' => 'expiry_year',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\CardErrorException',
            'code' => ErrorCode::CARD_ERROR_INVALID_EXPIRY_YEAR,
        ],
    ],
    'invalidCardExpiryDate' => [
        'request' => [
            'content' => [
                'card' => [
                    'expiry_month' => 4,
                    'expiry_year' => 2014,
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::CARD_ERROR_INVALID_EXPIRY_DATE,
                    'description' => PublicErrorDescription::CARD_ERROR_INVALID_EXPIRY_DATE,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\CardErrorException',
            'code' => ErrorCode::CARD_ERROR_INVALID_EXPIRY_DATE,
        ],
    ],
    'invalidEmailInTransaction' => [
        'request' => [
            'content' => [
                'email' => 'abc',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::FIELD_ERROR_INVALID_EMAIL,
                    'field' => 'email',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\FieldErrorException',
            'code' => ErrorCode::FIELD_ERROR_INVALID_EMAIL,
        ],
    ],
    'invalidContactInTransaction' => [
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
            'code' => ErrorCode::FIELD_ERROR_INVALID_CONTACT,
        ],
    ],
];
