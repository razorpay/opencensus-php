<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testPostRequestForCreatingPayoutLink' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
                'amount'      => 1000,
                'currency'    => 'INR',
                'description' => 'This is a test payout',
                'contact'     => [
                    'name'       => 'cskdsds',
                    'email'      => 'dsknlds@gmail.com',
                    'contact'    => '1231231231'
                ],
                'notes'       => ['hi' => 'hello'],
                'receipt'     => 'Test Payout Receipt'
            ]
        ],
        'response' => [
            'content' => [
                'amount'      => 1000,
                'currency'    => 'INR',
                'description' => 'This is a test payout',
                'receipt'     => 'Test Payout Receipt',
                'notes'       => [
                    'hi' => 'hello'
                ],
                'status'      => 'issued',
            ]
        ]
    ],

    'testPostRequestForCreatingPayoutLinkWithContactId' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
                'amount'      => 1000,
                'currency'    => 'INR',
                'description' => 'This is a test payout',
                'contact'     => [
                    'id' => '1000010contact',
                ],
                'notes'       => ['hi' => 'hello'],
                'receipt'     => 'Test Payout Receipt'
            ]
        ],
        'response' => [
            'content' => [
                'amount'      => 1000,
                'contact_id'  => '1000010contact',
                'currency'    => 'INR',
                'description' => 'This is a test payout',
                'receipt'     => 'Test Payout Receipt',
                'notes'       => [
                    'hi' => 'hello'
                ],
                'status'      => 'issued',
            ]
        ]
    ],

    'testShortUrlGenerationSuccessful'                => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
                'amount'      => 1000,
                'currency'    => 'INR',
                'description' => 'This is a test payout',
                'contact'     => [
                    'id' => '1000010contact',
                ],
                'notes'       => ['hi' => 'hello'],
                'receipt'     => 'Test Payout Receipt'
            ]
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testPayoutLinkFailedDueToContactCreationFailure' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
                'amount'         => 1000,
                'currency'       => 'INR',
                'description'    => 'This is a test payout',
                'contact'        => [
                    'name'    => 'cskdsdssdklifnjs',
                    'email'   => '@@',
                    'contact' => '1231231231'
                ],
                'notes'          => ['hi' => 'hello'],
                'receipt'        => 'Test Payout Receipt'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The email must be a valid email address.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testContactAddFailsWhenEmailAndPhoneNumberBothMissing' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
                'amount'      => 1000,
                'currency'    => 'INR',
                'description' => 'This is a test payout',
                'contact'     => [
                    'name'       => 'cskdsdssdklifnjs'
                ],
                'notes'       => ['hi' => 'hello'],
                'receipt'     => 'Test Payout Receipt'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_AT_LEAST_ONE_OF_EMAIL_OR_PHONE_REQUIRED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_AT_LEAST_ONE_OF_EMAIL_OR_PHONE_REQUIRED,
        ]
    ],

    'testPayoutLinkCreationFailsWhenContactIdIsMissingBothEmailAndPhone' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
                'amount'      => 1000,
                'currency'    => 'INR',
                'description' => 'This is a test payout',
                'contact'     => [
                    'id'    => 'id_for_a_contact_without_both_phone_and_email'
                ],
                'notes'       => ['hi' => 'hello'],
                'receipt'     => 'Test Payout Receipt'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_AT_LEAST_ONE_OF_EMAIL_OR_PHONE_REQUIRED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_AT_LEAST_ONE_OF_EMAIL_OR_PHONE_REQUIRED,
        ]
    ],

    'testShortUrlGenerationExceptionThrown' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
                'amount'      => 1000,
                'currency'    => 'INR',
                'description' => 'This is a test payout',
                'contact'     => [
                    'id' => '1000010contact',
                ],
                'notes'       => ['hi' => 'hello'],
                'receipt'     => 'Test Payout Receipt'
            ]
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testGetPayoutLinkById' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payout-links/',
        ],
        'response' => [
            'content' => [
                'id'          => 'poutlk_DnhDjMDHlQEjgM',
                'amount'      => 1000,
                'contact_id'  => '1000010contact',
                'currency'    => 'INR',
                'description' => 'This is a test payout',
                'receipt'     => 'Test Payout Receipt',
                'notes'       => [
                    'hi' => 'hello'
                ],
                'status'      => 'issued',
            ]
        ]
    ],

    'testListPayoutLink' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payout-links',
        ],
        'response' => ['content' => []]
    ],

    'testListPayoutLinkWithSearchParameter' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payout-links/',
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testGenerateOtpForOnlyPhoneContact' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/poutlk_DnhDjMDHlQEjgM/generate-customer-otp',

        ],
        'response' => [
            'content' => ['success' => 'OK']
        ]
    ],

    'testGenerateOtpForOnlyEmailContact' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/poutlk_DnhDjMDHlQEjgM/generate-customer-otp',

        ],
        'response' => [
            'content' => ['success' => 'OK']
        ]
    ],

    'testOtpVerificationWithContext' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/poutlk_DnhDjMDHlQEjgM/verify-customer-otp',
            'content' => [
                'context' => '1576208561',
                'otp'     => '0007'

            ]

        ],
        'response' => [
            'content' => []
        ]
    ],

    'testOtpGenerationWithContext' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/poutlk_DnhDjMDHlQEjgM/generate-customer-otp',
            'content' => [
                'context' => '1576208561'
            ]
        ],
        'response' => [
            'content' => ['success' => 'OK']
        ]
    ],

    'testVerifyOtpSuccessful' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/poutlk_DnhDjMDHlQEjgM/verify-customer-otp',
            'content' => ['otp' => '0007']
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testVerifyOtpFailedByInvalidOtp' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/poutlk_DnhDjMDHlQEjgM/verify-customer-otp',
            'content' => ['otp' => '1234']
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INCORRECT_OTP,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INCORRECT_OTP,
        ]
    ],

    'testExceptionWhenOtpGeneratedWithoutEmailAndPhoneNumber' => [
        'request'   => [
            'method' => 'POST',
            'url'    => '/payout-links/poutlk_DnhDjMDHlQEjgM/generate-customer-otp',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CANNOT_GENERATE_OTP_WITHOUT_PHONE_AND_EMAIL,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CANNOT_GENERATE_OTP_WITHOUT_PHONE_AND_EMAIL,
        ]
    ],

    'testExceptionWhenOnlyPhoneIsPresentAndSmsFails' => [
        'request'   => [
            'method' => 'POST',
            'url'    => '/payout-links/poutlk_DnhDjMDHlQEjgM/generate-customer-otp',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CUSTOMER_OTP_DELIVERY_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CUSTOMER_OTP_DELIVERY_FAILED,
        ]
    ],

    'testExceptionWhenOnlyEmailIsPresentAndEmailSendingFails' => [
        'request'   => [
            'method' => 'POST',
            'url'    => '/payout-links/poutlk_DnhDjMDHlQEjgM/generate-customer-otp',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CUSTOMER_OTP_DELIVERY_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CUSTOMER_OTP_DELIVERY_FAILED,
        ]
    ],

    'testPayoutLinkCancelApiSuccess' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/poutlk_DnhDjMDHlQEjgM/cancel',
        ],
        'response' => [
            'content' => [
                'id'          => 'DnhDjMDHlQEjgM',
                'status'      => 'cancelled',
            ]
        ]
    ],

    'testCancellingPayoutLinkFromProcessingStatusShouldThrowException' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/pyol_DnhDjMDHlQEjgM/cancel',
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYOUT_LINK_INVALID_STATUS,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
        ]
    ],

   'testWhenRavenFailsWhileOtpGenerationExceptionIsThrown' => [
        'request'   => [
            'method' => 'POST',
            'url'    => '/payout-links/poutlk_DnhDjMDHlQEjgM/generate-customer-otp',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CUSTOMER_OTP_GENERATION_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CUSTOMER_OTP_GENERATION_FAILED,
        ]
    ],

    'testCancelIdempotencyByCallingTheCancelApiTwice' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/poutlk_DnhDjMDHlQEjgM/cancel',
        ],
        'response' => [
            'content' => [
                'id'          => 'DnhDjMDHlQEjgM',
                'status'      => 'cancelled',
            ]
        ]
    ],

    'testGetFundAccountWithValidTokenReturnsFundAccountArray' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payout-links/poutlk_DnhDjMDHlQEjgM/fund-accounts',
            'content' => ['token' => 'some-random-token']
        ],
        'response' => [
            'content' => [
                [
                    'merchant_id'  => '10000000000000',
                    'source_type'  => 'contact',
                    'source_id'    => '1000010contact',
                    'account_type' => 'bank_account',
                ]
            ],
        ]
    ],

    'testGetFundAccountWithInvalidTokenRaisesException' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payout-links/poutlk_DnhDjMDHlQEjgM/fund-accounts',
            'content' => ['token' => 'some-random-token']
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_OTP_AUTH_TOKEN,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_OTP_AUTH_TOKEN,
        ]
    ],

    'testInitiateApiBankAccountRequiredWhenTypeIsBankAccount' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/poutlk_DnhDjMDHlQEjgM/initiate',
            'content' => [
                'account_type' => 'bank_account',
                'token'        => 'random token string',
            ]
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Only one of card, vpa or bank_account can be present',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testInitiateApiVpaRequiredWhenTypeIsVpa' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/poutlk_DnhDjMDHlQEjgM/initiate',
            'content' => [
                'token'        => 'random token string',
                'account_type' => 'vpa'
            ]
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Only one of card, vpa or bank_account can be present',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testInitiateApiWithInvalidAccountTypeRaisesException' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/poutlk_DnhDjMDHlQEjgM/initiate',
            'content' => [
                'token'        => 'random token string',
                'account_type' => 'invalid bank account type'
            ]
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The selected account type is invalid.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testInitiateApiWhenTokenIsAbsent' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/poutlk_DnhDjMDHlQEjgM/initiate',
            'content' => [
                'account_type' => 'vpa'
            ]
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The token field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testInitiateApiWithInvalidTokenRaiseException' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payout-links/poutlk_DnhDjMDHlQEjgM/initiate',
            'content' => [
                'token'        => 'random token string',
                'account_type' => 'vpa',
                'vpa'          => [
                    'address' => 'test@okhdfcbank'
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_OTP_AUTH_TOKEN,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_OTP_AUTH_TOKEN,
        ]
    ],

    'testInitiateApiWithInvalidFundAccountIdThrowException' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payout-links/poutlk_DnhDjMDHlQEjgM/initiate',
            'content' => [
                'token'        => 'random token string',
                'fund_account_id' => 'invalid_id_123'
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_ID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ]
    ],

    'testInitiateApiSuccessWhenValidBankAccountPassed' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payout-links/poutlk_DnhDjMDHlQEjgM/initiate',
            'content' => [
                'token'        => 'random token string',
                'fund_account_id' => '100000000003fa'
            ]
        ],
        'response'  => [
            'content'     => [
            ]
    ]],

    'testInitiateApiSuccessWhenValidVpaPassed' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payout-links/poutlk_DnhDjMDHlQEjgM/initiate',
            'content' => [
                'token'        => 'random token string',
                'fund_account_id' => '100000000003fa'
            ]
        ],
        'response'  => [
            'content'     => [
            ],
        ],
    ],

    'testInitiateApiFailsWhenFundAccountIdPassedBelongsToAnotherContact' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payout-links/poutlk_DnhDjMDHlQEjgM/initiate',
            'content' => [
                'token'        => 'random token string',
                'fund_account_id' => '100000000003fa'
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_FUND_ACCOUNT_DOESNT_BELONG_TO_INTENDED_CONTACT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FUND_ACCOUNT_DOESNT_BELONG_TO_INTENDED_CONTACT,
        ]
    ],
];
