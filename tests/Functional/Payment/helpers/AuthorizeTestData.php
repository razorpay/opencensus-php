<?php

use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Gateway\Hdfc;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testJsonpPayment' => [
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
                'http_status_code' => 200,
            ]
        ],
        'jsonp' => true
    ],

    'testMagicKeySet' => [
        'request' => [
            'method' => 'GET',
            'url' => '/payments/create/jsonp',
            'content' => [
                'card' => [
                    'number' => '4012001037167778'
                ],
                'callback' => 'abcdefghijkl',
                '_' => '',
            ]
        ],
        'response' => [
            'content' => [
                'magic' => true,
            ]
        ],
        'jsonp' => true
    ],

    'testMagicKeyFalseDisabledIin' => [
        'request' => [
            'method' => 'GET',
            'url' => '/payments/create/jsonp',
            'content' => [
                'card' => [
                    'number' => '4012001037167778'
                ],
                'callback' => 'abcdefghijkl',
                '_' => '',
            ]
        ],
        'response' => [
            'content' => [
                'magic' => false,
            ]
        ],
        'jsonp' => true
    ],

    'testMagicKeyFalseDisabledGlobally' => [
        'request' => [
            'method' => 'GET',
            'url' => '/payments/create/jsonp',
            'content' => [
                'card' => [
                    'number' => '4012001037167778'
                ],
                'callback' => 'abcdefghijkl',
                '_' => '',
            ]
        ],
        'response' => [
            'content' => [
                'magic' => false,
            ]
        ],
        'jsonp' => true
    ],

    'testMagicKeyFalseMerchantDisabled' => [
        'request' => [
            'method' => 'GET',
            'url' => '/payments/create/jsonp',
            'content' => [
                'card' => [
                    'number' => '4012001037167778'
                ],
                'callback' => 'abcdefghijkl',
                '_' => '',
            ]
        ],
        'response' => [
            'content' => [
                'magic' => false,
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

    'testCardWithoutCvv' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'The cvv field is required'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testNegativeAmount' => [
        'request' => [
            'content' => [
                'amount' => -200,
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
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
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

    'testNonSupportedCurrency' => [
        'request' => [
            'content' => [
                'currency' => 'AAA',
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
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
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
                    'description' => 'The description must be a string.',
                    'field' => 'description',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDescriptionTooLarge' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The description may not be greater than 255 characters.',
                    'field' => 'description',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
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

    'testInvalidUtf8InDescription' => [
        'request' => [
            'content' => [
                'description' => ' ❣👌READY TO SHIP 👌❣',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'description contains invalid characters',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testIntentPaymentWithVpa' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The vpa field is not required and not shouldn\'t be sent.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testIntentPayment' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'UPI intent transactions are not enabled for the merchant'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_UPI_INTENT_NOT_ENABLED_FOR_MERCHANT
        ],
    ],

    'testCollectPayment' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment was unsuccessful as the seller does not accept UPI payments. Try using another method.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_NOT_ENABLED_FOR_MERCHANT
        ],
    ],

    'testOmnichannelPayment' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment was unsuccessful as the seller does not accept UPI payments. Try using another method.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_NOT_ENABLED_FOR_MERCHANT
        ],
    ],

    'testFixAuthorizedAt' => [
        'request' => [
            'content' => [],
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'success_count' => 1,
                'failure_count' => 0,
                'failure_payments' => [],
                'total' => 1,
            ],
        ],
    ],

    'testTimeoutOldPayment' => [
        'request' => [
            'content' => [],
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'status' => 'failed',
                'error_code' => ErrorCode::BAD_REQUEST_ERROR,
                'error_description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_TIMED_OUT,
            ],
        ],
    ],

    'testTimeoutAuthenticatedPayment' => [
        'request' => [
            'content' => [],
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'status' => 'failed',
                'error_code' => ErrorCode::BAD_REQUEST_ERROR,
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
    'testTimeoutOldPaymentsCustomTimeout' => [
        'request'  => [
            'url'     => '/settings/merchant',
            'method'  => 'post',
            'content' => [
                Merchant\Constants::PAYMENT_TIMEOUT_WINDOW       => 60 * 27, // 27 minutes
            ]
        ],
        'response' => [
            'content' => [
                'success' => true
            ],
        ],
    ],

    'testAtmPinAuthenticationPayment' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The selected auth_type is invalid',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAtmPinAuthenticationWithNoTerminal' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::SERVER_ERROR,
                    'description' => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class' => 'RZP\Exception\RuntimeException',
            'internal_error_code' => ErrorCode::SERVER_ERROR_RUNTIME_ERROR,
        ],
    ],

    'testAtmPinAuthenticationNotSupported' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The pin authentication type is not applicable on the given card',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'test3dsPaymentWithPinTerminal' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class' => 'RZP\Exception\RuntimeException',
            'internal_error_code' => ErrorCode::SERVER_ERROR_RUNTIME_ERROR
        ],
    ],

    'testPaymentViaWalletS2SWoAuth' => [
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

    'testWalletS2SPaymentWoFeature' => [
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

    'testInvalidWalletS2SPayment' => [
        'request' => [
            'url' => '/payments/create/wallet',
            'method' => 'POST',
            'content' => [
                'wallet'    => 'invalid_wallet',
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
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_WALLET_NOT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_NOT_SUPPORTED,
        ],
    ],

    'testWalletWithInternationalContact' => [
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

    'testPayumoneyPaymentViaWalletS2S' => [
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

    'testIntentPaymentViaUpiS2S' => [
        'request' => [
            'url' => '/payments/create/upi',
            'method' => 'POST',
            'content' => [
                'amount'        => 10000,
                'currency'      => 'INR',
                'contact'       => '9999999999',
                'email'         => 'a@b.com',
                'description'   => 'description',
                'notes'         => [
                    'key'   => 'value'
                ],
                'flow'          => 'intent'
            ],
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 200,
        ]
    ],

    'testMobikwikPaymentViaWalletS2S' => [
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
    ],

    'testCancelPaymentWithReason' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
                // 'http_status_code' => 400,
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER
        ],
    ],

    'testCancelUnintendedPaymentWithReason' => [
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
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER
        ],
    ],

    'testCancelPaymentWithArrayReason' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
                // 'http_status_code' => 400,
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER
        ],
    ],

    'testFailPayment' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            'gateway_error_code'  => null
        ],
    ],

    'testCardNetworkDisabled' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED
        ],
    ],

    'testAuthorizeWithSubTypeDisabled' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CARD_SUBTYPE_BUSINESS_NOT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_SUBTYPE_BUSINESS_NOT_SUPPORTED
        ],
    ],


    'testPaymentWithSkipAuthWithMotoFeatureDisabled' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The selected auth_type is invalid',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testPaymentWithSkipAuthWithNotSupportedCard' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The skip authentication type is not applicable on the given card',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testLBPCurrency' => [
        'request' => [
            'content' => [
                'currency' => 'LBP',
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
];
