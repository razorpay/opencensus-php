<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

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
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
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
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUppercaseEmail' => [
        'request' => [
            'content' => [
                'email' => 'UPPERCASE@Razorpay.com'
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ]
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
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_TOO_SHORT,
        ],
    ],

    'testContactTooLong' => [
        'request' => [
            'content' => [
                'contact' => '1234567890110044',
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
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_TOO_LONG,
        ],
    ],

    'testContactInvalidCountryCode' => [
        'request' => [
            'content' => [
                'contact' => '+091212324',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CONTACT_INVALID_COUNTRY_CODE,
                    'field' => 'contact',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_INVALID_COUNTRY_CODE,
        ],
    ],

    'testInvalidContactPassingSyntaxCheck' => [
        'request' => [
            'content' => [
                'contact' => '43634423',
            ],
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 200,
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
            'class' => 'RZP\Exception\BadRequestException',
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
            'class' => 'RZP\Exception\BadRequestException',
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
            'class' => 'RZP\Exception\BadRequestException',
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
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MIN_AMOUNT,
        ],
    ],

    'testAmountVeryHigh' => [
        'request' => [
            'content' => [
                'amount' => '10000000000',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'amount',
                    'description' => 'Amount exceeds maximum amount allowed.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAmountLessThan50ForNetbanking' => [
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
            'class' => 'RZP\Exception\BadRequestException',
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
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
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
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPaymentWithBlankMethod' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'method',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
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
            'class' => 'RZP\Exception\BadRequestException',
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
            'class' => 'RZP\Exception\BadRequestException',
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
            'class' => 'RZP\Exception\BadRequestException',
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
            'class' => 'RZP\Exception\BadRequestException',
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
            'class' => 'RZP\Exception\BadRequestException',
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
            'class' => 'RZP\Exception\BadRequestException',
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
            'class' => 'RZP\Exception\BadRequestException',
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
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NOTES_VALUE_TOO_LARGE,
        ],
    ],

    'testNotesEmptyString' => [
        'request' => [
            'content' => [
                'notes' => ''
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
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NOTES_SHOULD_BE_ARRAY,
        ],
    ],

    'testNotesNull' => [
        'request' => [
            'content' => [
                'notes' => null
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
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NOTES_SHOULD_BE_ARRAY,
        ],
    ],

    'testNotesAsArray' => [
        'request' => [
            'content' => [
                'notes' => ['0' => 'test string', 'temp' => 'string 2']
            ],
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 200,
        ]
    ],

    'testTimeoutOldPayment' => [
        'request' => [
            'content' => [],
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'status' => 'failed',
                'error_code' => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT,
                'error_description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_TIMED_OUT,
            ],
        ],
    ],

    'testTimeoutOldPaymentWithErrorRetention' => [
        'request' => [
            'content' => [],
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'status' => 'failed',
                'error_code' => PublicErrorCode::BAD_REQUEST_ERROR,
                'error_description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
            ],
        ],
    ],

    'testFailTimeoutOldPayments' => [
        'request' => [
            'content' => [],
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'status' => 'authorized',
                'error_code' => null,
                'error_description' => null,
            ],
        ]
    ],

    'testPaymentViaWalletS2SWoAuth' =>[
        'request' => [
            'url' => '/payments/create/wallet',
            'method' => 'POST',
            'content' => [
                'wallet'    => 'payumoney',
                'amount'    => 10000,
                'currency'  => 'INR',
                'contact'   => '9999999999',
                'email'     => 'a@b.com'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_BASICAUTH_EXPECTED,
                ],
            ],
            'status_code' => 401,
        ]
    ],

    'testWalletS2SPaymentWoFeature' =>[
        'request' => [
            'url' => '/payments/create/wallet',
            'method' => 'POST',
            'content' => [
                'wallet'    => 'payumoney',
                'amount'    => 10000,
                'currency'  => 'INR',
                'contact'   => '9999999999',
                'email'     => 'a@b.com'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND,
                ],
            ],
            'status_code' => 400,
        ]
    ],

    'testWalletWithInternationalContact' =>[
        'request' => [
            'content' => [
                'wallet'        => 'payumoney',
                'method'        => 'wallet',
                'amount'        => 10000,
                'currency'      => 'INR',
                'contact'       => '+1 (213) 298-9734',
                'email'         => 'a@b.com',
                'description'   => 'description',
                'notes'         => [
                    'key'   => 'value'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'  => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CONTACT_ONLY_INDIAN_ALLOWED,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_ONLY_INDIAN_ALLOWED,
        ],
    ],

    'testPayumoneyPaymentViaWalletS2S' =>[
        'request' => [
            'url' => '/payments/create/wallet',
            'method' => 'POST',
            'content' => [
                'wallet'        => 'payumoney',
                'amount'        => 10000,
                'currency'      => 'INR',
                'contact'       => '9999999999',
                'email'         => 'a@b.com',
                'description'   => 'description',
                'notes'         => [
                    'key'   => 'value'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'request' => [
                    'method'    => 'post'
                ]
            ],
            'status_code' => 200,
        ]
    ],

    'testMobikwikPaymentViaWalletS2S' =>[
        'request' => [
            'url' => '/payments/create/wallet',
            'method' => 'POST',
            'content' => [
                'wallet'    => 'mobikwik',
                'amount'    => 10000,
                'currency'  => 'INR',
                'contact'   => '9999999999',
                'email'     => 'a@b.com'
            ],
        ],
        'response' => [
            'content' => [
                'request' => [
                    'method'    => 'post'
                ]
            ],
            'status_code' => 200,
        ]
    ],

    'testPaymentTopupViaInvalidGateway' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_GATEWAY_CANNOT_TOPUP,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_GATEWAY_CANNOT_TOPUP,
        ],
    ]
];
