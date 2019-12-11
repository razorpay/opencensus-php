<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;

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
                'amount'      => 1000,
                'currency'    => 'INR',
                'description' => 'This is a test payout',
                'contact'     => [
                    'name'       => 'cskdsdssdklifnjs',
                    'email'      => 'dsknlds@gmail.com',
                    'contact'    => '1231231231'
                ],
                'notes'       => ['hi' => 'hello'],
                'receipt'     => 'Test Payout Receipt'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CONTACT_ADD_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CONTACT_ADD_FAILED,
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
                'id'          => 'pyol_DnhDjMDHlQEjgM',
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
            'url'     => '/payout-links/pyol_DnhDjMDHlQEjgM/generate-customer-otp',

        ],
        'response' => [
            'content' => ['success' => 'OK']
        ]
    ],

    'testGenerateOtpForOnlyEmailContact' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/pyol_DnhDjMDHlQEjgM/generate-customer-otp',

        ],
        'response' => [
            'content' => ['success' => 'OK']
        ]
    ],

    'testVerifyOtpSuccessful' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/pyol_DnhDjMDHlQEjgM/verify-customer-otp',
            'content' => ['otp' => '0007']
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testVerifyOtpFailedByInvalidOtp' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/pyol_DnhDjMDHlQEjgM/verify-customer-otp',
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
            'url'    => '/payout-links/pyol_DnhDjMDHlQEjgM/generate-customer-otp',
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
            'url'    => '/payout-links/pyol_DnhDjMDHlQEjgM/generate-customer-otp',
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
            'url'    => '/payout-links/pyol_DnhDjMDHlQEjgM/generate-customer-otp',
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
    ]
];
