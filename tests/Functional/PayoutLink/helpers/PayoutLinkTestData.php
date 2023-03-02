<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;

return [
    'testWebhooksEnabled' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/webhooks',
            'content' => [
                'url'    => 'https://www.example.com',
                'events' => [
                    'payout.created'         => '1',
                    'payout_link.attempted'  => '1',
                    'payout_link.issued'     => '1',
                    'payout_link.cancelled'  => '1',
                    'payout_link.processed'  => '1',
                    'payout_link.processing' => '1'
                ]
            ]
        ],
        'response' => [
            'content' => [
                'url'    => 'https://www.example.com',
                'events' => [
                    'payout_link.issued'     => true,
                    'payout_link.processing' => true,
                    'payout_link.processed'  => true,
                    'payout_link.attempted'  => true,
                    'payout_link.cancelled'  => true
                ]
            ]
        ]
    ],

    'testPayoutLinkFetchExpandsByUser' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payout-links?expand[]=user',
            'content' => [
            ]
        ],
        'response' => [
            'content' => []]

    ],

    'testWebhooksUpdate' => [
        'request'  => [
            'method'  => 'PUT',
            'url'     => '/webhooks/{id}',
            'content' => [
                'url'    => 'https://www.example.com',
                'events' => [
                    'payout.created'         => '1',
                    'payout_link.attempted'  => '0',
                    'payout_link.issued'     => '0',
                    'payout_link.processing' => '1',
                    'payout_link.processed'  => '1',
                    'payout_link.cancelled'  => '1',
                ]
            ]
        ],
        'response' => [
            'content' => [
                'url'    => 'https://www.example.com',
                'events' => [
                    'payout.created'         => true,
                    'payout_link.issued'     => false,
                    'payout_link.processing' => true,
                    'payout_link.processed'  => true,
                    'payout_link.attempted'  => false,
                    'payout_link.cancelled'  => true
                ]
            ]
        ]
    ],

    'testWebhooksEnabledPartial' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/webhooks',
            'content' => [
                'url'    => 'https://www.example.com',
                'events' => [
                    'payout.created' => '1',
                    'payout_link.attempted'  => '1',
                    'payout_link.issued'     => '0',
                    'payout_link.cancelled'  => '1',
                    'payout_link.processed'  => '0',
                    'payout_link.processing' => '1'
                ]
            ]
        ],
        'response' => [
            'content' => [
                'url'    => 'https://www.example.com',
                'events' => [
                    'payout_link.issued'     => false,
                    'payout_link.processing' => true,
                    'payout_link.processed'  => false,
                    'payout_link.attempted'  => true,
                    'payout_link.cancelled'  => true
                ]
            ]
        ]
    ],

    'testPostRequestForCreatingPayoutLink' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
                'amount'      => 1000,
                'currency'    => 'INR',
                'description' => 'This is a test payout',
                'purpose'     => 'refund',
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

    'testExceptionOnCreatePayoutLinkWithoutOtpOnProxyAuth' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
                'amount'      => 1000,
                'currency'    => 'INR',
                'description' => 'This is a test payout',
                'purpose'     => 'refund',
                'contact'     => [
                    'name'       => 'cskdsds',
                    'email'      => 'dsknlds@gmail.com',
                    'contact'    => ''
                ],
                'notes'       => ['hi' => 'hello'],
                'receipt'     => 'Test Payout Receipt'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The otp field is required.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testMerchantSettingsUpdateApiForIMPSDisabled' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/merchant/dashboardsettings',
            'content' => [
                'UPI'             => true,
                'IMPS'            => '0',
                'support_email'   => 'anubhav@f.com',
                'support_url'     => 'http://dsjsd',
                'support_contact' => '1212121212',
                'ticket_id'       => '128'
            ],
        ],
        'response' => [
            'content' => [
                'UPI'             => '1',
                'IMPS'            => '0',
                'support_email'   => 'anubhav@f.com',
                'support_url'     => 'http://dsjsd',
                'support_contact' => '1212121212',
                'ticket_id'       => '128'
            ]
        ]
    ],

    'testMerchantSettingsUpdateApiForNonPayoutModeSettings' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/merchant/dashboardsettings',
            'content' => [
                'support_email'   => 'amit@gmail.com',
                'support_url'     => 'http://dsjsd',
                'support_contact' => '1212121212',
                'ticket_id'       => '128'
            ],
        ],
        'response' => [
            'content' => [
                'support_email'   => 'amit@gmail.com',
                'support_url'     => 'http://dsjsd',
                'support_contact' => '1212121212',
                'ticket_id'       => '128'
            ]
        ]
    ],

    'testMerchantSettingsUpdateApiForUPIDisabled' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/merchant/dashboardsettings',
            'content' => [
                'UPI'             => "0",
                'IMPS'            => '1',
                'support_email'   => 'amit@gmail.com',
                'support_url'     => 'http://dsjsd',
                'support_contact' => '1212121212',
                'ticket_id'       => '128'
            ],
        ],
        'response' => [
            'content' => [
                'UPI'             => '0',
                'IMPS'            => '1',
                'support_email'   => 'amit@gmail.com',
                'support_url'     => 'http://dsjsd',
                'support_contact' => '1212121212',
                'ticket_id'       => '128'
            ]
        ]
    ],

    'testMerchantSettingsGetApi' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payout-links/merchant/dashboardsettings',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'UPI'             => '1',
                'IMPS'            => '0',
                'support_email'   => 'anubhav@f.com',
                'support_url'     => 'http://dsjsd',
                'support_contact' => '1212121212',
                'ticket_id'       => '128'
            ]
        ]
    ],

    'testExceptionOnCreatePayoutLinkWithInvalidOtpOnProxyAuth' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
                'amount'      => 1000,
                'currency'    => 'INR',
                'description' => 'This is a test payout',
                'purpose'     => 'refund',
                'contact'     => [
                    'name'       => 'cskdsds',
                    'email'      => 'dsknlds@gmail.com',
                    'contact'    => ''
                ],
                'notes'       => ['hi' => 'hello'],
                'receipt'     => 'Test Payout Receipt',
                'otp'         => '123123',
                'token'       => 'EFMCRjw1Dq8oHn'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Verification failed because of incorrect OTP.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INCORRECT_OTP,
        ]
    ],

    'testPostRequestForCreatingPayoutLinkOnProxyAuth' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
                'amount'      => 1000,
                'currency'    => 'INR',
                'description' => 'This is a test payout',
                'purpose'     => 'refund',
                'contact'     => [
                    'name'       => 'cskdsds',
                    'email'      => 'dsknlds@gmail.com',
                    'contact'    => '1231231231'
                ],
                'notes'       => ['hi' => 'hello'],
                'receipt'     => 'Test Payout Receipt',
                'otp'         => '0007',
                'token'       => 'EFMCRjw1Dq8oHn'
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

    'testExceptionOnCreatePayoutLinkWithoutTokenOnProxyAuth' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
                'amount'      => 1000,
                'currency'    => 'INR',
                'description' => 'This is a test payout',
                'purpose'     => 'refund',
                'contact'     => [
                    'name'       => 'cskdsds',
                    'email'      => 'dsknlds@gmail.com',
                    'contact'    => ''
                ],
                'notes'       => ['hi' => 'hello'],
                'receipt'     => 'Test Payout Receipt',
                'otp'         => '12312'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The token field is required.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testCreatePayoutLinkPassesWithoutOtpWhenPrivateAuth' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
                'amount'      => 1000,
                'currency'    => 'INR',
                'description' => 'This is a test payout',
                'purpose'     => 'refund',
                'contact'     => [
                    'name'       => 'cskdsds',
                    'email'      => 'dsknlds@gmail.com',
                    'contact'    => '1231231231'
                ],
                'notes'       => ['hi' => 'hello'],
                'receipt'     => 'Test Payout Receipt',
                'send_email' => 1,
                'send_sms' => 1
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testExceptionWhenSendSmsEnabledWithNoPhoneInContact' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
                'amount'      => 1000,
                'currency'    => 'INR',
                'description' => 'This is a test payout',
                'purpose'     => 'refund',
                'contact'     => [
                    'name'       => 'cskdsds',
                    'email'      => 'dsknlds@gmail.com',
                    'contact'    => ''
                ],
                'notes'       => ['hi' => 'hello'],
                'receipt'     => 'Test Payout Receipt',
                'send_sms'    => true
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_SMS_NOTIFICATION_WITH_EMPTY_PHONE
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_SMS_NOTIFICATION_WITH_EMPTY_PHONE,
        ]
    ],

    'testExceptionWhenSendEmailEnabledWithNoEmailInContact' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
                'amount'      => 1000,
                'currency'    => 'INR',
                'description' => 'This is a test payout',
                'purpose'     => 'refund',
                'contact'     => [
                    'name'       => 'cskdsds',
                    'email'      => '',
                    'contact'    => '123123123'
                ],
                'notes'       => ['hi' => 'hello'],
                'receipt'     => 'Test Payout Receipt',
                'send_email'    => true
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_EMAIL_NOTIFICATION_WITH_EMPTY_EMAIL
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EMAIL_NOTIFICATION_WITH_EMPTY_EMAIL,
        ]
    ],

    'testSendLinkEmailQueuedWhenOnPayoutLinkCreate' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
                'amount'      => 1000,
                'currency'    => 'INR',
                'description' => 'This is a test payout',
                'purpose'     => 'refund',
                'contact'     => [
                    'name'       => 'cskdsds',
                    'email'      => 'test@r.com',
                ],
                'notes'       => ['hi' => 'hello'],
                'receipt'     => 'Test Payout Receipt',
                'send_email'    => true
            ]
        ],
        'response' => [
            'content' => [
            ],
        ]
    ],

    'testBoolCastingInPayoutLinkNotification' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payout-links/',
            'content' => []
        ],
        'response' => [
            'content' => [
                'send_sms'       => true,
                'send_email'     => true
            ]
        ]
    ],

    'testOnBoardingApiBrandingTrue' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payout-links/_meta/onboarding',
            'content' => []
        ],
        'response' => [
            'content' => [
                'branding_completed' => true,
                'link_created'       => false,
                'link_processed'     => false
            ]
        ]
    ],

    'testOnBoardingApiPayoutLinkCreatedTrue' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payout-links/_meta/onboarding',
            'content' => []
        ],
        'response' => [
            'content' => [
                'branding_completed' => false,
                'link_created'       => true,
                'link_processed'     => false,
            ]
        ]
    ],

    'testOnBoardingApiPayoutLinkProcessedTrue' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payout-links/_meta/onboarding',
            'content' => []
        ],
        'response' => [
            'content' => [
                'branding_completed' => false,
                'link_created'       => true,
                'link_processed'     => true,
            ]
        ]
    ],

    'testOnBoardingApiAllFalseInDefaultState' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payout-links/_meta/onboarding',
            'content' => []
        ],
        'response' => [
            'content' => [
                'branding_completed'    => false,
                'link_created'          => false,
                'link_processed'        => false,
            ]
        ]
    ],

    'testPayoutLinkStatusApi' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payout-links/',
            'content' => [
            ]
        ],
        'response' => [
            'content' => [
                'status'      => 'issued'
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
                'purpose'     => 'refund',
                'contact'     => [
                    'id' => 'cont_1000010contact',
                ],
                'notes'       => ['hi' => 'hello'],
                'receipt'     => 'Test Payout Receipt'
            ]
        ],
        'response' => [
            'content' => [
                'amount'      => 1000,
                'contact_id'  => 'cont_1000010contact',
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
                'purpose'     => 'refund',
                'contact'     => [
                    'id' => 'cont_1000010contact',
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
                'purpose'     => 'refund',
                'contact'     => [
                    'name'    => 'cskdsdssdklifnjs',
                    'email'   => 'this_is_invalid@com',
                    'contact' => '1231231231'
                ],
                'notes'       => ['hi' => 'hello'],
                'receipt'     => 'Test Payout Receipt'
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
                'purpose'     => 'refund',
                'contact'     => [
                    'name'       => 'Test Name'
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
                'purpose'     => 'refund',
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
                    'description' => PublicErrorDescription::BAD_REQUEST_CONTACT_ID_EMAIL_AND_PHONE_NUMBER_MISSING,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CONTACT_ID_EMAIL_AND_PHONE_NUMBER_MISSING,
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
                'purpose'     => 'refund',
                'contact'     => [
                    'id' => 'cont_1000010contact',
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
                'amount'      => 1000,
                'contact_id'  => 'cont_1000010contact',
                'currency'    => 'INR',
                'description' => 'This is a test payout',
                'purpose'     => 'refund',
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
            'url'     => '',

        ],
        'response' => [
            'content' => ['success' => 'OK']
        ]
    ],

    'testPayoutAmountAboveLimitFailsCreation' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/',
            'content' => [
                'amount'      => 1000003928379,
                'currency'    => 'INR',
                'description' => 'This is a test payout',
                'purpose'     => 'refund',
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
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The amount may not be greater than 10000000000.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            'field'               => 'amount'
        ]
    ],

    'testGenerateOtpForOnlyEmailContact' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '',
            'content' => []
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testOtpVerificationWithContext' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '',
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
            'url'     => '',
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
            'url'     => '',
            'content' => ['otp' => '0007']
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testVerifyOtpFailedByInvalidOtp' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '',
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
            'url'    => '',
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
            'url'    => '',
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
            'url'    => '',
        ],
        'response' => [
            'content' => [
                'status'      => 'cancelled',
            ]
        ]
    ],

    'testCancellingPayoutLinkFromProcessingStatusShouldThrowException' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '',
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYOUT_LINK_CANNOT_BE_CANCELLED_IN_THIS_STATE,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_LINK_CANNOT_BE_CANCELLED_IN_THIS_STATE,
        ]
    ],

    'testGenerateOtpOnCancelledLinkThrowsException' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '',
            'content' => [
                'context' => '12345'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_STATE_FOR_OTP_GENERATION,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_STATE_FOR_OTP_GENERATION,
        ]
    ],

    'testVerifyOtpOnCancelledLinkThrowsException' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '',
            'content' => [
                'context' => '12345',
                'otp'     => '0007'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_STATE_FOR_OTP_VERIFICATION,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_STATE_FOR_OTP_VERIFICATION,
        ]
    ],

    'testWhenRavenFailsWhileOtpGenerationExceptionIsThrown' => [
        'request'   => [
            'method' => 'POST',
            'url'    => '',
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
            'url'    => '',
        ],
        'response' => [
            'content' => [
                'status'      => 'cancelled',
            ]
        ]
    ],

    'testGetFundAccountWithValidTokenReturnsFundAccountArray' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '',
            'content' => ['token' => 'some-random-token']
        ],
        'response' => [
            'content' => [
                'items' =>
                      [
                          [
                              'bank_account' => [
                                  'name' => 'test'
                              ]
                          ]
                    ]
            ],
        ]
    ],

    'testInitiateApiHasPayoutInfo' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '',
            'content' => [
                'token'         => 'random token string',
                "account_type"  => "bank_account",
                "bank_account"  =>[
                    "name"          => "Test",
                    "ifsc"          => "UTIB0002953",
                    "account_number"=> "916010007268867"
                ]

            ]

        ],
        'response' => [
            'content' => [
                'payouts' =>
                    [
                        'entity'=> 'collection',
                        'count' => 1
                    ]
            ],
        ]
    ],

    'testGetFundAccountWithInvalidTokenRaisesException' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '',
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
            'url'     => '',
            'content' => [
                'account_type' => 'bank_account',
                'token'        => 'random token string',
            ]
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Only one of card, vpa, bank_account or wallet can be present',
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
            'url'     => '',
            'content' => [
                'token'        => 'random token string',
                'account_type' => 'vpa'
            ]
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Only one of card, vpa, bank_account or wallet can be present',
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
            'url'     => '',
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
            'url'     => '',
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
            'url'     => '',
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
            'url'     => '',
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
            'url'     => '',
            'content' => [
                'token'        => 'random token string',
                'fund_account_id' => 'fa_100000000003fa'
            ]
        ],
        'response'  => [
            'content'     => [
            ]
        ]],

    'testInitiateApiSuccessWhenValidVpaPassed' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '',
            'content' => [
                'token'        => 'random token string',
                'fund_account_id' => 'fa_100000000003fa'
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
            'url'     => '',
            'content' => [
                'token'        => 'random token string',
                'fund_account_id' => 'fa_100000000003fa'
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

    'testPayoutStatusCreatedMakesLinkStatusProcessing' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '',
            'content' => [
                'token'        => 'random token string',
                'fund_account_id' => 'fa_100000000003fa'
            ]
        ],
        'response'  => [
            'content'     => [
            ]
        ]],

    'testPayoutStatusProcessedMakesLinkStatusProcessed' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '',
            'content' => [
                'token'        => 'random token string',
                'fund_account_id' => 'fa_100000000003fa'
            ]
        ],
        'response'  => [
            'content'     => [
            ]
        ]
    ],

    'testPayoutLinkSettingsGetApiSuccess' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payout-links/10000000000000/settings',
            'content' => []
        ],
        'response' => [
            'content' => [
                'UPI'  => '1',
                'IMPS' => '0',
            ]
        ]
    ],

    'testPayoutStatusReversedMakesLinkStatusAttempted' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '',
            'content' => [
                'token'        => 'random token string',
                'fund_account_id' => 'fa_100000000003fa'
            ]
        ],
        'response'  => [
            'content'     => [
            ]
        ]],

    'testPayoutLinkSettingsApiSuccess' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payout-links/10000000000000/settings',
            'content' => [
                'UPI'  => 1,
                'IMPS' => 1,
            ]],
        'response'  => [
            'content'     => [
                'UPI'  => '1',
                'IMPS' => '1',
            ]
        ]
    ],

    'testPayoutLinkThrowsExceptionWhenInitiateCalledWithInvalidState' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '',
            'content' => [
                'token'           => 'test_otp_auth_token',
                'fund_account_id' => 'fa_100000000003fa'
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYOUT_LINK_INVALID_STATE_FOR_INITIATE_REQUEST,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_LINK_INVALID_STATE_FOR_INITIATE_REQUEST,
        ]
    ],

    'testInitiateApiWithInvalidFundAccountTypeThrowsException' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '',
            'content' => [
                'token'           => '1234',
                'fund_account_id' => 'fa_100000000003fa'
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ONLY_VPA_AND_BANK_ACCOUNT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ONLY_VPA_AND_BANK_ACCOUNT_SUPPORTED,
        ]
    ],

    'testInvalidPurposeThrowsException' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
                'amount'      => 1000,
                'currency'    => 'INR',
                'description' => 'This is a test payout',
                'purpose'     => 'an invalid purpose',
                'contact'     => [
                    'name'    => 'Test Contact Name',
                    'email'   => 'testemail@test.com',
                    'contact' => '1231231231'
                ],
                'notes'       => ['hi' => 'hello'],
                'receipt'     => 'Test Payout Receipt'
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid purpose: an invalid purpose',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testUpiPayoutModeWhenVpaFundAccountAdded' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '',
            'content' => [
                'account_type' => 'vpa',
                'vpa'          => [
                    'address' => 'test@okhdfcbank'
                ],
                'token'        => 'random token string'
            ]
        ],
        'response' => [
            'content' => [
            ]
        ]],

    'testImpsPayoutModeWhenBankFundAccountAndAmountLessThanTwoLacs' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '',
            'content' => [
                'account_type'    => 'bank_account',
                'fund_account_id' => 'fa_100000000003fa',
                'token'           => 'random token string'
            ]
        ],
        'response' => [
            'content' => [
            ]
        ]],

    'testNeftPayoutModeWhenBankFundAccountAndAmountMoreThanTwoLacs' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '',
            'content' => [
                'account_type'    => 'bank_account',
                'fund_account_id' => 'fa_100000000003fa',
                'token'           => 'random token string'
            ]
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testPayoutLinkIssuedWebhookTriggered' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
                'amount'      => 1000,
                'currency'    => 'INR',
                'description' => 'This is a test payout',
                'purpose'     => 'refund',
                'contact'     => [
                    'name'    => 'Test Contact Name',
                    'email'   => 'testemail@test.com',
                    'contact' => '1231231231'
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

    'testExceptionWhenSendSmsEnabledWithNoPhoneInContact' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
                'amount'      => 1000,
                'currency'    => 'INR',
                'description' => 'This is a test payout',
                'purpose'     => 'refund',
                'contact'     => [
                    'name'       => 'cskdsds',
                    'email'      => 'dsknlds@gmail.com',
                    'contact'    => ''
                ],
                'notes'       => ['hi' => 'hello'],
                'receipt'     => 'Test Payout Receipt',
                'send_sms'    => true
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_SMS_NOTIFICATION_WITH_EMPTY_PHONE
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_SMS_NOTIFICATION_WITH_EMPTY_PHONE,
        ]
    ],

    'testExceptionWhenSendEmailEnabledWithNoEmailInContact' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
                'amount'      => 1000,
                'currency'    => 'INR',
                'description' => 'This is a test payout',
                'purpose'     => 'refund',
                'contact'     => [
                    'name'       => 'cskdsds',
                    'email'      => '',
                    'contact'    => '123123123'
                ],
                'notes'       => ['hi' => 'hello'],
                'receipt'     => 'Test Payout Receipt',
                'send_email'    => true
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_EMAIL_NOTIFICATION_WITH_EMPTY_EMAIL
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EMAIL_NOTIFICATION_WITH_EMPTY_EMAIL,
        ]
    ],

    'testResendApiQueuesEmail' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
            ]
        ],
        'response' => [
            'content' => [
            ],
        ]
    ],

    'testResendApiThrowsErrorForSendSmsWithoutContact' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
                'send_sms' => true
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_SMS_NOTIFICATION_WITH_EMPTY_PHONE,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_SMS_NOTIFICATION_WITH_EMPTY_PHONE,
        ]
    ],

    'testResendApiThrowsErrorForSendEmailWithoutEmail' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
                'send_email' => true
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_EMAIL_NOTIFICATION_WITH_EMPTY_EMAIL,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EMAIL_NOTIFICATION_WITH_EMPTY_EMAIL,
        ]
    ],

    'testResendApiSendSmsWithSmsPassed' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
                'send_sms' => true,
                'contact_phone_number' => 1231231231
            ]
        ],
        'response' => [
            'content' => [
            ],
        ]
    ],

    'testResendApiSendEmailWithEmailPassed' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
                'send_email' => true,
                'contact_email' => 'test@rzp.com'
            ]
        ],
        'response' => [
            'content' => [
            ],
        ]
    ],

    'testResendApiUpdateContactThrowExceptionWhenContactPresent' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
                'contact_phone_number' => 1234123412
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_REWRITING_PHONE_NUMBER_NOT_PERMITTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_REWRITING_PHONE_NUMBER_NOT_PERMITTED,
        ]
    ],

    'testResendApiUpdateEmailThrowExceptionWhenEmailPresent' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
                'contact_email' => 'test@rzp.com'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_REWRITING_EMAIL_NOT_PERMITTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_REWRITING_EMAIL_NOT_PERMITTED,
        ]
    ],

    'PayoutLinkIssuedWebHook' => [
        'entity'   => 'event',
        'event'    => 'payout_link.issued',
        'contains' => [
            'payout_link',
        ],
        'payload'  => [
            'payout_link' => [
                'entity' => [
                    'contact'     => [
                        'name'    => 'Test Contact Name',
                        'email'   => 'testemail@test.com',
                        'contact' => '1231231231'
                    ],
                    'fund_account_id'      => null,
                    'status'               => 'issued',
                    'amount'               => 1000,
                    'currency'             => 'INR',
                    'description'          => 'This is a test payout',
                    'receipt'              => 'Test Payout Receipt',
                ],
            ],
        ],
    ],

    'PayoutLinkAttemptedWebHook' => [
        'entity'   => 'event',
        'event'    => 'payout_link.attempted',
        'contains' => [
            'payout_link',
        ],
        'payload'  => [
            'payout_link' => [
                'entity' => [
                    'contact'     => [
                        'name'    => '1000010contact',
                        'email'   => 'test@rzp.com',
                        'contact' => '1231231231'
                    ],
                    'fund_account_id'      => 'fa_100000000003fa',
                    'status'               => 'issued',
                    'amount'               => 1000,
                    'currency'             => 'INR',
                    'description'          => 'This is a test payout',
                    'receipt'              => 'Test Payout Receipt',
                ],
            ],
        ],
    ],

    'PayoutLinkProcessedWebHook' => [
        'entity'   => 'event',
        'event'    => 'payout_link.processed',
        'contains' => [
            'payout_link',
        ],
        'payload'  => [
            'payout_link' => [
                'entity' => [
                    'contact'     => [
                        'name'    => '1000010contact',
                        'email'   => 'test@rzp.com',
                        'contact' => '1231231231'
                    ],
                    'fund_account_id'      => 'fa_100000000003fa',
                    'status'               => 'processed',
                    'amount'               => 1000,
                    'currency'             => 'INR',
                    'description'          => 'This is a test payout',
                    'receipt'              => 'Test Payout Receipt',
                ],
            ],
        ],
    ],

    'PayoutLinkProcessingWebHook' => [
        'entity'   => 'event',
        'event'    => 'payout_link.processing',
        'contains' => [
            'payout_link',
        ],
        'payload'  => [
            'payout_link' => [
                'entity' => [
                    'contact'     => [
                        'name'    => '1000010contact',
                        'email'   => 'test@rzp.com',
                        'contact' => '1231231231'
                    ],
                    'fund_account_id'      => 'fa_100000000003fa',
                    'status'               => 'processing',
                    'amount'               => 1000,
                    'currency'             => 'INR',
                    'description'          => 'This is a test payout',
                    'receipt'              => 'Test Payout Receipt',
                ],
            ],
        ],
    ],

    'PayoutLinkCancelledWebHook' => [
        'entity'   => 'event',
        'event'    => 'payout_link.cancelled',
        'contains' => [
            'payout_link',
        ],
        'payload'  => [
            'payout_link' => [
                'entity' => [
                    'contact'     => [
                        'name'    => '1000010contact',
                        'email'   => 'test@rzp.com',
                        'contact' => '1231231231'
                    ],
                    'fund_account_id'      => null,
                    'status'               => 'cancelled',
                    'amount'               => 1000,
                    'currency'             => 'INR',
                    'description'          => 'This is a test payout',
                    'receipt'              => 'Test Payout Receipt',
                ],
            ],
        ],
    ],

    'testGetFundAccountsOfContact' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '',
        ],
        'response' => [
            'content' => [
                'entity'  => 'collection',
                'count'   => 3,
                'items'   => [
                    [
                        "id" => "fa_100000000010fa",
                        "account_type" => "bank_account",
                        "bank_account" => [
                            "ifsc" => "RZPB0000000",
                            "bank_name" => "Razorpay",
                            "name" => "random_name",
                            "notes" => [],
                            "account_number" => "XXXXXXX1011"
                        ]
                    ],
                    [
                        "id" => "fa_100000000011fa",
                        "account_type" => "bank_account",
                        "bank_account" => [
                            "ifsc" => "RZPB0000000",
                            "bank_name" => "Razorpay",
                            "name" => "random_name",
                            "notes" => [],
                            "account_number" => "XXXXXXX1011"
                        ]
                    ],
                    [
                        "id" => "fa_100000000012fa",
                        "account_type" => "bank_account",
                        "bank_account" => [
                            "ifsc" => "RZPB0000000",
                            "bank_name" => "Razorpay",
                            "name" => "random_name",
                            "notes" => [],
                            "account_number" => "XXXXXXX1011"
                        ]
                    ],
                ],
            ],
        ]
    ],

    'testGetFundAccountsOfContactWithInactiveFundAccount' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '',
        ],
        'response' => [
            'content' => [
                'entity'  => 'collection',
                'count'   => 2,
                'items'   => [
                    [
                        "id" => "fa_100000000010fa",
                        "account_type" => "bank_account",
                        "bank_account" => [
                            "ifsc" => "RZPB0000000",
                            "bank_name" => "Razorpay",
                            "name" => "random_name",
                            "notes" => [],
                            "account_number" => "XXXXXXX1011"
                        ]
                    ],
                    [
                        "id" => "fa_100000000012fa",
                        "account_type" => "bank_account",
                        "bank_account" => [
                            "ifsc" => "RZPB0000000",
                            "bank_name" => "Razorpay",
                            "name" => "random_name",
                            "notes" => [],
                            "account_number" => "XXXXXXX1011"
                        ]
                    ],
                ],
            ],
        ]
    ],

    'testBccEmailForSendLinkInternal' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/send-email',
            'content' => [
                'merchant_id' => '10000000000000',
                'payoutlinkresponse' => [
                    'purpose' => 'refund',
                    'short_url' => 'https://552a05fe.ngrok.io/i/aYlKRm5',
                    'amount' => '1000',
                    'description' => 'testing',
                    'contact_name' => 'test',
                    'contact_email' => 'mail.testing@razorpay.com',
                    'contact_phone_number' => '9876543210',
                ],
                'to_email' => 'test@razorpay.com',
                'email_type' => 'link'
            ]
        ],
        'response' => [
            'content' => [
                'success'  => 'OK'
            ],
        ]
    ],

    'testNoBccEmailForSendLinkInternal' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/send-email',
            'content' => [
                'merchant_id' => '100000Razorpay',
                'payoutlinkresponse' => [
                    'purpose' => 'refund',
                    'short_url' => 'https://552a05fe.ngrok.io/i/aYlKRm5',
                    'amount' => '1000',
                    'description' => 'testing',
                    'contact_name' => 'test',
                    'contact_email' => 'mail.testing@razorpay.com',
                    'contact_phone_number' => '9876543210',
                ],
                'to_email' => 'test@razorpay.com',
                'email_type' => 'link'
            ]
        ],
        'response' => [
            'content' => [
                'success'  => 'OK'
            ],
        ]
    ],

    'testBccEmailForSuccessInternal' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/send-email',
            'content' => [
                'merchant_id' => '10000000000000',
                'payoutlinkresponse' => [
                    'purpose' => 'refund',
                    'short_url' => 'https://552a05fe.ngrok.io/i/aYlKRm5',
                    'amount' => '1000',
                    'description' => 'testing',
                    'contact_name' => 'test',
                    'contact_email' => 'mail.testing@razorpay.com',
                    'contact_phone_number' => '9876543210',
                ],
                'settings' => [
                    'support_contact' => '1234567890',
                    'support_email' => 'support@rzp.com',
                    'support_url' => 'some-test-url'
                ],
                'to_email' => 'test@razorpay.com',
                'email_type' => 'success'
            ]
        ],
        'response' => [
            'content' => [
                'success'  => 'OK'
            ],
        ]
    ],

    'testNoBccEmailForSuccessInternal' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/send-email',
            'content' => [
                'merchant_id' => '100000Razorpay',
                'payoutlinkresponse' => [
                    'purpose' => 'refund',
                    'short_url' => 'https://552a05fe.ngrok.io/i/aYlKRm5',
                    'amount' => '1000',
                    'description' => 'testing',
                    'contact_name' => 'test',
                    'contact_email' => 'mail.testing@razorpay.com',
                    'contact_phone_number' => '9876543210',
                ],
                'settings' => [
                    'support_contact' => '1234567890',
                    'support_email' => 'support@rzp.com',
                    'support_url' => 'some-test-url'
                ],
                'to_email' => 'test@razorpay.com',
                'email_type' => 'success'
            ]
        ],
        'response' => [
            'content' => [
                'success'  => 'OK'
            ],
        ]
    ],

    'testCreatePLForMissingContactDetails' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links',
            'content' => []
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'Invalid request payload',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_LINK_MICRO_SERVICE_FAILED,
        ],
    ],

    'testBulkResendNotification' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/bulk-resend-notification',
            'content' => [
                'merchant_id' => '10000000000000',
                'payout_link_ids' => 'poutlk_4eWc1vLJKgR2hE,poutlk_8QmI1vLJKgQBgq,poutlk_8bLC1vLJKfYTMq,poutlk_8gcr1vLJKftwqO,poutlk_4p841vLJKhV7qy,poutlk_4KGz1vLJNkQ79k,poutlk_4ZBX1vLJP4B03I,poutlk_8Rjb1vLJYAOBZg'
            ]
        ],
        'response' => [
            'content' => [
                'success_payout_link_ids' => 'poutlk_4eWc1vLJKgR2hE,poutlk_8QmI1vLJKgQBgq,poutlk_8bLC1vLJKfYTMq,poutlk_8gcr1vLJKftwqO,poutlk_4p841vLJKhV7qy',
                'failed_payout_link_ids' => 'poutlk_4KGz1vLJNkQ79k,poutlk_4ZBX1vLJP4B03I,poutlk_8Rjb1vLJYAOBZg'
            ],
        ],
    ],

    'testIntegrateApp' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/integrate-app',
            'content' => [
                'source' => 'shopify',
                'source_identifier' => 'test.myshopify.com',
                'code' => 'some-code',
                'timestamp' => 'some-code',
                'state' => 'some-state',
                'host' => 'some-host',
            ]
        ],
        'response' => [
            'content' => [
                'is_integration_success' => true
            ],
        ],
    ],

    'testFetchShopifyOrderDetails' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payout-links/shopify/orders',
            'content' => [
                'shop' => 'test.myshopify.com',
                'order_id' => '123456789',
            ]
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testFetchIntegrationDetails' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payout-links/_meta/integration-details',
            'content' => [
                'source' => 'shopify',
            ]
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testCreateWithUserIdInInputPrivateAuth' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links',
            'content' => [
                'amount' => 10,
                'user_id' => 'some-id',
            ]
        ],
        'response' => [
            'content' => [
                'id' => 'poutlk_DWivysHLcspTNI',
                'amount' => 10,
            ],
            'status_code' => 200,
        ],
    ],

    'testCreateWithUserIdInInputProxyAuth' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links',
            'content' => [
                'amount' => 10,
                'user_id' => 'some-id',
                'token' => 'EFMCRjw1Dq8oHn',
                'otp' => '0007',
            ]
        ],
        'response' => [
            'content' => [
                'id' => 'poutlk_DWivysHLcspTNI',
                'user_id' => '20000000000000',
                'amount' => 10,
            ],
            'status_code' => 200,
        ],
    ],

    'testCreateAdminBatchWithoutRequiredPermission' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/admin/batches',
            'content' => [
                'type' => 'payout_link_bulk',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'Required permission not found',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_REQUIRED_PERMISSION_NOT_FOUND
        ],
    ],

    'testCreateAdminBatchWithPermission' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/admin/batches',
            'content' => [
                'type' => 'payout_link_bulk',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'The file field is required when file id is not present.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testSendReminderCallbackForCancellingReminder' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/send-reminder-callback/1234',
        ],
        'response' => [
            'content' => [
                'error' => [
                    '_internal' => [
                        'error_code' => 'BAD_REQUEST_REMINDER_NOT_APPLICABLE',
                    ]
                ]
            ],
            'status_code' => 400,
        ],
    ],

    'testSendReminderCallbackForContinueReminder' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/send-reminder-callback/1234',
        ],
        'response' => [
            'content' => [
                'success' => true
            ],
            'status_code' => 200,
        ],
    ],

    'testExpireCallbackForCancellingReminder' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/expire-callback/1234',
        ],
        'response' => [
            'content' => [
                'error' => [
                    '_internal' => [
                        'error_code' => 'BAD_REQUEST_REMINDER_NOT_APPLICABLE',
                    ]
                ]
            ],
            'status_code' => 400,
        ],
    ],

    'testExpireCallbackForContinueReminder' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/expire-callback/1234',
        ],
        'response' => [
            'content' => [
                'success' => true
            ],
            'status_code' => 200,
        ],
    ],

    'testUpdatePayoutLink' => [
        'request'  => [
            'method' => 'PATCH',
            'url'    => '/payout-links/1234',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testSendReminderMail' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/send-email',
            'content' => [
                'merchant_id' => '10000000000000',
                'payout_link_details' => [
                    'purpose' => 'refund',
                    'short_url' => 'https://552a05fe.ngrok.io/i/aYlKRm5',
                    'amount' => '1000',
                    'description' => 'testing',
                    'contact_name' => 'test',
                    'contact_email' => 'mail.testing@razorpay.com',
                    'contact_phone_number' => '9876543210',
                    'expire_by' => 1626756010,
                ],
                'to_email' => 'test@razorpay.com',
                'email_type' => 'reminder'
            ]
        ],
        'response' => [
            'content' => [
                'success'  => 'OK'
            ],
        ]
    ],

    'testSendProcessingExpiredMail' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/send-email',
            'content' => [
                'merchant_id' => '10000000000000',
                'payout_link_details' => [
                    'purpose' => 'refund',
                    'short_url' => 'https://552a05fe.ngrok.io/i/aYlKRm5',
                    'amount' => '1000',
                    'description' => 'testing',
                    'contact_name' => 'test',
                    'contact_email' => 'mail.testing@razorpay.com',
                    'contact_phone_number' => '9876543210',
                    'expire_by' => 1626756010,
                ],
                'settings' => [
                    'support_contact' => '9876543210',
                    'support_email' => 'test@support.com',
                    'support_url' => 'support-url',
                ],
                'to_email' => 'test@razorpay.com',
                'email_type' => 'processing_expired'
            ]
        ],
        'response' => [
            'content' => [
                'success'  => 'OK'
            ],
        ]
    ],

    'testGetHostedPageDataForAppAuth' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payout-links/poutlk_12345/view-data',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [],
        ]
    ],

    'testGetHostedPageDataWithEmptySupportEmailForAppAuth' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payout-links/poutlk_12345/view-data',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'status' => 'expired',
                'userDetails' => [
                    'name' => 't***',
                    'maskedEmail' => 't***@g***l.com'
                ],
                'amount' => '100',
                'supportDetails' => [
                    'supportEmail' => '',
                ],
            ],
        ]
    ],

    'testGetHostedPageDataWithIssuedLinkForAppAuth' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payout-links/poutlk_12345/view-data',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'status' => 'issued',
                'userDetails' => [
                    'name' => 't***',
                    'maskedEmail' => 't***@g***l.com'
                ],
                'amount' => '100',
                'supportDetails' => [
                    'supportEmail' => '',
                ],
            ],
        ]
    ],

    'testGetHostedPageDataWithProcessingLinkForAppAuth' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payout-links/poutlk_12345/view-data',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'status' => 'processing',
                'userDetails' => [
                    'name' => 't***',
                    'maskedEmail' => 't***@g***l.com'
                ],
                'amount' => '100',
                'supportDetails' => [
                    'supportEmail' => '',
                ],
            ],
        ]
    ],

    'testGetHostedPageDataWithProcessedLinkForAppAuth' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payout-links/poutlk_12345/view-data',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'status' => 'processed',
                'userDetails' => [
                    'name' => 't***',
                    'maskedEmail' => 't***@g***l.com'
                ],
                'amount' => '100',
                'supportDetails' => [
                    'supportEmail' => '',
                ],
            ],
        ]
    ],

    'testGetHostedPageDataWithCancelledLinkForAppAuth' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payout-links/poutlk_12345/view-data',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'status' => 'cancelled',
                'userDetails' => [
                    'name' => 't***',
                    'maskedEmail' => 't***@g***l.com'
                ],
                'amount' => '100',
                'supportDetails' => [
                    'supportEmail' => '',
                ],
            ],
        ]
    ],

    'testGetHostedPageDataForPendingLinkForAppAuth' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payout-links/poutlk_12345/view-data',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'status' => 'pending',
                'userDetails' => [
                    'name' => 't***',
                    'maskedEmail' => 't***@g***l.com'
                ],
                'amount' => '100',
                'supportDetails' => [
                    'supportEmail' => 'support@gmail.com',
                ],
            ],
        ]
    ],

    'testGetHostedPageDataForExpiredLinkForAppAuth' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payout-links/poutlk_12345/view-data',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'status' => 'expired',
                'userDetails' => [
                    'name' => 't***',
                    'maskedEmail' => 't***@g***l.com'
                ],
                'amount' => '100',
                'supportDetails' => [
                    'supportEmail' => 'support@gmail.com',
                ],
            ],
        ]
    ],

    'testGetHostedPageDataForRejectedLinkForAppAuth' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payout-links/poutlk_12345/view-data',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'status' => 'rejected',
                'userDetails' => [
                    'name' => 't***',
                    'maskedEmail' => 't***@g***l.com'
                ],
                'amount' => '100',
                'supportDetails' => [
                    'supportEmail' => 'support@gmail.com',
                ],
            ],
        ]
    ],

    'testGetDemoHostedPageDataForAppAuth' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/demo/payout-links/poutlk_12345/view-data',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [],
        ]
    ],

    'testApprovePayoutLinkInvalidPayoutLinkId' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/poutl_12345/approve',
        ],
        'response' => [
            'status_code' => 400,
            'content' => [
                'error' => [
                    'description' => 'payout_link_id: Public id format is incorrect : poutl_12345.'
                ]
            ],
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_LINK_MICRO_SERVICE_FAILED,
        ]
    ],

    'testApprovePayoutLinkWorkflowAlreadyProcessed' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/poutlk_12345/approve',
        ],
        'response' => [
            'status_code' => 400,
            'content' => [
                'error' => [
                    'description' => 'workflow-already-processed'
                ]
            ],
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_LINK_MICRO_SERVICE_FAILED,
        ]
    ],

    'testApprovePayoutLinkPayoutLinkNotPendingOnCurrentUser' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/poutlk_12345/approve',
        ],
        'response' => [
            'status_code' => 400,
            'content' => [
                'error' => [
                    'description' => 'workflow-not-pending-on-current-user'
                ]
            ],
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_LINK_MICRO_SERVICE_FAILED,
        ]
    ],

    'testApprovePayoutLinkUserAlreadyActedInSameGroup' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/poutlk_12345/approve',
        ],
        'response' => [
            'status_code' => 400,
            'content' => [
                'error' => [
                    'description' => 'USER_ALREADY_TAKEN_ACTION_ON_STATE_GROUP'
                ]
            ],
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_LINK_MICRO_SERVICE_FAILED,
        ]
    ],

    'testApprovePayoutLinkSuccess' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/poutlk_12345/approve',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [],
        ]
    ],

    'testApprovePayoutLinkInternalServerError' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/poutlk_12345/approve',
        ],
        'response' => [
            'status_code' => 400,
            'content' => [
                'error' => [
                    'description' => 'The server encountered an unexpected condition which prevented it from fulfilling the request.'
                ]
            ],
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_LINK_MICRO_SERVICE_FAILED,
        ]
    ],

    'testApprovePayoutLinkNoWorkflowForPayoutLink' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/poutlk_12345/approve',
        ],
        'response' => [
            'status_code' => 400,
            'content' => [
                'error' => [
                    'description' => 'no-workflow-for-payout-link'
                ]
            ],
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_LINK_MICRO_SERVICE_FAILED,
        ]
    ],

    'testWorkflowSummaryInternalServerError' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payout-links/_meta/workflow/summary',
        ],
        'response' => [
            'status_code' => 400,
            'content' => [
                'error' => [
                    'description' => 'The server encountered an unexpected condition which prevented it from fulfilling the request.'
                ]
            ],
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_LINK_MICRO_SERVICE_FAILED,
        ]
    ],

    'testWorkflowSummaryZeroPendingPLs' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payout-links/_meta/workflow/summary',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'count'        => 0,
                'total_amount' => 0,
            ],
        ]
    ],

    'testWorkflowSummaryWithPendingPLsLiveMode' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payout-links/_meta/workflow/summary',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'count'        => 2,
                'total_amount' => 1000,
            ],
        ]
    ],

    'testWorkflowSummaryTestMode' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payout-links/_meta/workflow/summary',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'count'        => 0,
                'total_amount' => 0,
            ],
        ]
    ],

    'testApproveOtpSuccess' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/poutlk_12345/approve/otp',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'token'   => 'test.token'
            ],
        ]
    ],

    'testRejectPayoutLinkSuccess' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/poutlk_12345/reject',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [],
        ]
    ],

    'testBulkApproveSuccess' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/approve/bulk',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [],
        ]
    ],

    'testBulkApproveOtpSuccess' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/approve/bulk/otp',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [],
        ]
    ],

    'testBulkRejectSuccess' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/reject/bulk',
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [],
        ]
    ],

    'testBulkRejectTestMode' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/reject/bulk',
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'description' => 'Test Mode is currently not supported for Payout Links'
                ]
            ],
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_LINK_NOT_SUPPORTED_FOR_TEST_MODE,
        ]
    ],

    'testBulkApproveOtpTestMode' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/approve/bulk/otp',
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'description' => 'Test Mode is currently not supported for Payout Links'
                ]
            ],
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_LINK_NOT_SUPPORTED_FOR_TEST_MODE,
        ]
    ],

    'testBulkApproveTestMode' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/approve/bulk',
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'description' => 'Test Mode is currently not supported for Payout Links'
                ]
            ],
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_LINK_NOT_SUPPORTED_FOR_TEST_MODE,
        ]
    ],

    'testApprovePayoutLinkTestMode' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/poutlk_12345/approve',
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'description' => 'Test Mode is currently not supported for Payout Links'
                ]
            ],
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_LINK_NOT_SUPPORTED_FOR_TEST_MODE,
        ]
    ],

    'testRejectPayoutLinkTestMode' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/poutlk_12345/reject',
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'description' => 'Test Mode is currently not supported for Payout Links'
                ]
            ],
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_LINK_NOT_SUPPORTED_FOR_TEST_MODE,
        ]
    ],

    'testApproveOtpTestMode' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/poutlk_12345/approve/otp',
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'description' => 'Test Mode is currently not supported for Payout Links'
                ]
            ]
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_LINK_NOT_SUPPORTED_FOR_TEST_MODE,
        ]
    ],

    'testGetSettingsTestMode' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payout-links/10000000000000/settings',
            'content' => []
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'IMPS' => false,
                'UPI' => true,
                'AMAZONPAY' => true,
            ],
        ]
    ],

    'testUpdateSettingsTestMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/10000000000000/settings',
            'content' => []
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'IMPS' => true,
                'UPI' => true,
                'AMAZONPAY' => true,
            ],
        ]
    ],

    'testCancelTestModeWithProxyAuth' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/poutlk_1234/cancel',
            'content' => []
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'contact_id'   => '1000010contact',
                'amount'       => 1000,
                'user_id'      => null,
                'currency'     => 'INR',
                'description'  => 'This is a test payout',
                'purpose'      => 'refund',
                'receipt'      => 'Test Payout Receipt',
                'notes'        => [
                    'hi' => 'hello'
                ],
                'short_url'    => 'http=>//76594130.ngrok.io/i/mGs4ehe',
                'status'       => 'cancelled',
                'created_at'   => 1575367399,
                'cancelled_at' => 1575367499,
            ],
        ]
    ],

    'testFetchPayoutLinkByIdTestMode' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payout-links/poutlk_1234',
            'content' => []
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'contact_id'   => '1000010contact',
                'amount'       => 1000,
                'user_id'      => null,
                'currency'     => 'INR',
                'description'  => 'This is a test payout',
                'purpose'      => 'refund',
                'receipt'      => 'Test Payout Receipt',
                'notes'        => [
                    'hi' => 'hello'
                ],
                'short_url'    => 'http=>//76594130.ngrok.io/i/mGs4ehe',
                'status'       => 'issued',
                'created_at'   => 1575367399,
                'cancelled_at' => null,
            ],
        ]
    ],

    'testBulkResendNotificationTestMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/bulk-resend-notification',
            'content' => []
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'description' => 'Test Mode is currently not supported for Payout Links'
                ]
            ],
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_LINK_NOT_SUPPORTED_FOR_TEST_MODE,
        ]
    ],

    'testBulkResendNotificationLiveMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/bulk-resend-notification',
            'content' => [
                'merchant_id' => '10000000000000',
                'payout_link_ids' => 'poutlk_4eWc1vLJKgR2hE,poutlk_4KGz1vLJNkQ79k',
            ]
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                "success_payout_link_ids" => "poutlk_4eWc1vLJKgR2hE",
                "failed_payout_link_ids" => "poutlk_4KGz1vLJNkQ79k"
            ],
        ]
    ],

    'testCancelTestModeWithPrivateAuth' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/poutlk_1234/cancel',
            'content' => []
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'contact_id'   => '1000010contact',
                'amount'       => 1000,
                'user_id'      => null,
                'currency'     => 'INR',
                'description'  => 'This is a test payout',
                'purpose'      => 'refund',
                'receipt'      => 'Test Payout Receipt',
                'notes'        => [
                    'hi' => 'hello'
                ],
                'short_url'    => 'http=>//76594130.ngrok.io/i/mGs4ehe',
                'status'       => 'cancelled',
                'created_at'   => 1575367399,
                'cancelled_at' => 1575367499,
            ],
        ]
    ],

    'testGenerateAndSendCustomerOtpTestMode' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/poutlk_12345678912345/generate-customer-otp',
            'content' => [
                'context' => 'some-context',
            ],
            'headers' => [
                'X-Razorpay-Mode' => 'test',
            ]
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'description' => 'Test Mode is currently not supported for Payout Links'
                ]
            ],
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_LINK_NOT_SUPPORTED_FOR_TEST_MODE,
        ]
    ],

    'testGenerateAndSendCustomerOtpLiveModeWithOutModeHeader' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/poutlk_12345678912345/generate-customer-otp',
            'content' => [
                'context' => 'some-context',
            ]
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'success' => 'ok'
            ],
        ]
    ],

    'testGenerateAndSendCustomerOtpLiveModeWithModeHeader' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/poutlk_12345678912345/generate-customer-otp',
            'content' => [
                'context' => 'some-context',
            ],
            'headers' => [
                'X-Razorpay-Mode' => 'live',
            ]
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'success' => 'ok'
            ],
        ]
    ],

    'testGetHostedPageDataWithIssuedLinkForAppAuthInTestMode' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payout-links/poutlk_12345/view-data',
            'headers' => [
                'X-Razorpay-Mode' => 'test',
            ]
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'status' => 'issued',
                'userDetails' => [
                    'name' => 't***',
                    'maskedEmail' => 't***@g***l.com'
                ],
                'amount' => '100',
                'supportDetails' => [
                    'supportEmail' => '',
                ],
            ],
        ]
    ],

    'testGetStatusPayoutLinkTestMode' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payout-links/poutlk_12345678954321/status',
            'content' => [
            ]
        ],
        'response' => [
            'status_code' => 200,
            'content' => [
                'status'    => 'issued'
            ]
        ]
    ],

    'testGetStatusPayoutLinkLiveMode' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/payout-links/poutlk_12345678954321/status',
            'content' => [
            ]
        ],
        'response' => [
            'status_code' => 200,
            'content' => [
                'status'    => 'issued'
            ]
        ]
    ],

    'testExpireCallbackForCancellingReminderTestMode' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/test/expire-callback/1234',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'BAD_REQUEST_REMINDER_NOT_APPLICABLE'
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_LINK_MICRO_SERVICE_FAILED,
        ]
    ],

    'testExpireCallbackForContinueReminderTestMode' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/test/expire-callback/1234',
        ],
        'response' => [
            'content' => [
                'success' => true
            ],
            'status_code' => 200,
        ],
    ],

    'testFetchIntegrationDetailsTestMode' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payout-links/_meta/integration-details',
            'content' => [
                'source' => 'shopify',
            ]
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'integration_status' => 'not-initiated',
            ],
            'status_code' => 200,
        ],
    ],

    'testUpdateAttachmentsForPayoutLinkSuccessProxyAuth' => [
        'request'  => [
            'method' => 'PATCH',
            'url'    => '/payout-links/poutlk_12345678912345/attachments',
            'content' => [
                'attachments' => [
                    [
                      "file_id" => "file_test",
                      "file_name" => "test.pdf"
                    ],
                ]
            ]
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testUpdateAttachmentsForPayoutLinkFailPrivateAuth' => [
        'request'  => [
            'method' => 'PATCH',
            'url'    => '/payout-links/poutlk_12345678912345/attachments',
            'content' => [
                'attachments' => [
                    [
                        "file_id" => "file_test",
                        "file_name" => "test.pdf"
                    ],
                ]
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    "code" => "BAD_REQUEST_ERROR",
                    "description" => "The requested URL was not found on the server."
                ]
            ],
            'status_code' => 400,
        ],
    ],

    'testUploadAttachmentFailPrivateAuth' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payout-links/attachment',
            'content' => []
        ],
        'response' => [
            'content' => [
                'error' => [
                    "code" => "BAD_REQUEST_ERROR",
                    "description" => "The requested URL was not found on the server."
                ]
            ],
            'status_code' => 400,
        ],
    ],

    'testGetSignedUrlSuccessProxyAuth' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payout-links/poutlk_12345678912345/file/file_test/get-signed-url',
        ],
        'response' => [
            'content' => [
                'signed_url' => 'https://ufh-deafult/ndckd',
                'mime' => 'application/pdf'
            ],
            'status_code' => 200,
        ],
    ],

    'testGetSignedUrlFailPrivateAuth' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/payout-links/poutlk_12345678912345/file/file_test/get-signed-url',
        ],
        'response' => [
            'content' => [
                'error' => [
                    "code" => "BAD_REQUEST_ERROR",
                    "description" => "The requested URL was not found on the server."
                ]
            ],
            'status_code' => 400,
        ],
    ],

    'testGenerateAndSendCustomerOtpSuccessForCAActivatedMerchant' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/poutlk_12345678912345/generate-customer-otp',
            'content' => [
                'context' => 'some-context',
            ]
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'success' => 'ok'
            ],
        ]
    ],

    'testGenerateOtpForCreatePayoutLinkWithSecureOtpContext' => [
        'request' => [
            'url' => '/users/otp/send',
            'method' => 'POST',
            'content' => [
                'action' => 'create_payout_link',
                'purpose' => 'refund',
                'account_number' => '4564563559247998',
                'amount' => 100,
                'contact' => [
                    'name' => 'testing',
                    'contact' => '9090909090',
                    'email' => 'test@razorpay.com',
                ]
            ]
        ],
        'response' => [
            'content' => [
                'otp' => '0007'
            ]
        ]
    ],

    'testMetricSentInGenerateOtpFailedForCreatePayoutLinkWithSecureOtpContext' => [
        'request' => [
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Something went wrong, please try again after sometime'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_RESPONSE_OTP_GENERATE_RAVEN,
        ]
    ],

    'testPayoutLinkCreationWithSecureOtpContext' => [
        'request' => [
            'url' => '/payout-links',
            'method' => 'POST',
            'content' => [
                'otp' => '0007',
                'token' => 'BUIj3m2Nx2VvVj',
                'account_number' => '4564563559247998',
                'amount' => 100,
                'contact' => [
                    'name' => 'testing',
                    'contact' => '9090909090',
                    'email' => 'test@razorpay.com',
                ]
            ]
        ],
        'response' => [
            'content' => [
                'id' => 'poutlk_ABCDE12345'
            ]
        ]
    ],

    'testFetchPendingPayoutLinksAsOwnerSSWF' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/pending/summary',
            'content' => [
                'account_numbers' => ['4564563559247990']
            ],
            'server' => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
                'HTTP_X-Request-Origin'   => config('applications.banking_service_url'),
            ]
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testFetchPendingPayoutLinksAsOwnerSSWFWithPLResponse' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/pending/summary',
            'content' => [
                'account_numbers' => ['4564563559247990']
            ],
            'server' => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
                'HTTP_X-Request-Origin'   => config('applications.banking_service_url'),
            ]
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testFetchPendingPayoutLinksAsOwnerSSWFValidationError' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/pending/summary',
            'content' => [
            ],
            'server' => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
                'HTTP_X-Request-Origin'   => config('applications.banking_service_url'),
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The account numbers field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testBulkRejectPayoutLinksAsOwner' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/reject/bulk/owner',
            'content' => [
                'payout_link_ids' => ['poutlk_4Dx41ywjzsda6u'],
                'bulk_reject_as_owner' => true,
                'user_comment' => ''
            ],
            'server' => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
                'HTTP_X-Request-Origin'   => config('applications.banking_service_url'),
            ]
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testBulkRejectPayoutLinksAsOwnerError' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links/reject/bulk/owner',
            'content' => [
                'payout_link_ids' => ['poutlk_4Dx41ywjzsda6u'],
                'user_comment' => ''
            ],
            'server' => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
                'HTTP_X-Request-Origin'   => config('applications.banking_service_url'),
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The bulk reject as owner field is required.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testPayoutLinkVerifyOtpWithoutOtp' => [
        'request' => [
            'url' => '/payout-links',
            'method' => 'POST',
            'content' => [
                'token' => 'BUIj3m2Nx2VvVj',
                'account_number' => '4564563559247998',
                'amount' => 100,
                'contact' => [
                    'name' => 'testing',
                    'contact' => '9090909090',
                    'email' => 'test@razorpay.com',
                ]
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The otp field is required.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testPayoutLinkVerifyOtpWithInvalidOtp' => [
        'request' => [
            'url' => '/payout-links',
            'method' => 'POST',
            'content' => [
                'otp' => '1234',
                'token' => 'BUIj3m2Nx2VvVj',
                'account_number' => '4564563559247998',
                'amount' => 100,
                'contact' => [
                    'name' => 'testing',
                    'contact' => '9090909090',
                    'email' => 'test@razorpay.com',
                ]
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Verification failed because of incorrect OTP.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INCORRECT_OTP,
        ]
    ],

    'testPayoutLinkVerifyOtpWithoutToken' => [
        'request' => [
            'url' => '/payout-links',
            'method' => 'POST',
            'content' => [
                'otp' => '0007',
                'account_number' => '4564563559247998',
                'amount' => 100,
                'contact' => [
                    'name' => 'testing',
                    'contact' => '9090909090',
                    'email' => 'test@razorpay.com',
                ]
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The token field is required.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testPayoutLinkVerifyOtpWithUserValidator' => [
        'request' => [
            'url' => '/payout-links',
            'method' => 'POST',
            'content' => [
                'otp' => '0007',
                'token' => 'BUIj3m2Nx2VvVj',
                'account_number' => '4564563559247998',
                'amount' => 100,
                'contact' => [
                    'name' => 'testing',
                    'contact' => '9090909090',
                    'email' => 'test@razorpay.com',
                ]
            ]
        ],
        'response' => [
            'content' => [
                'id' => 'poutlk_ABCDE12345'
            ]
        ]
    ],

    'testPayoutLinkVerifyOtpWithValidAction' => [
        'request' => [
            'url' => '/payout-links',
            'method' => 'POST',
            'content' => [
                'otp' => '0007',
                'token' => 'BUIj3m2Nx2VvVj',
                'account_number' => '4564563559247998',
                'amount' => 100,
                'contact' => [
                    'name' => 'testing',
                    'contact' => '9090909090',
                    'email' => 'test@razorpay.com',
                ]
            ]
        ],
        'response' => [
            'content' => [
                'id' => 'poutlk_ABCDE12345'
            ]
        ]
    ],

    'testPayoutLinkGenerateOtpWithValidActionAndWithToken' => [
        'request' => [
            'url' => '/users/otp/send',
            'method' => 'POST',
            'content' => [
                'action' => 'create_payout_link',
                'token' => 'BUIj3m2Nx2VvVj',
                'purpose' => 'refund',
                'account_number' => '4564563559247998',
                'amount' => 100,
                'contact' => [
                    'name' => 'testing',
                    'contact' => '9090909090',
                    'email' => 'test@razorpay.com',
                ]
            ]
        ],
        'response' => [
            'content' => [
                'token' => 'BUIj3m2Nx2VvVj'
            ]
        ]
    ],

    'testPayoutLinkGenerateOtpWithValidActionAndDynamicToken' => [
        'request' => [
            'url' => '/users/otp/send',
            'method' => 'POST',
            'content' => [
                'action' => 'create_payout_link',
                'token' => 'BUIj3m2Nx2VvVj',
                'purpose' => 'refund',
                'account_number' => '4564563559247998',
                'amount' => 100,
                'contact' => [
                    'name' => 'testing',
                    'contact' => '9090909090',
                    'email' => 'test@razorpay.com',
                ]
            ]
        ],
        'response' => [
            'content' => [
                'token' => 'BUIj3m2Nx2VvVj'
            ]
        ]
    ],

    'testPayoutLinkGenerateOtpWithValidActionRavenSuccess' => [
        'request' => [
            'url' => '/users/otp/send',
            'method' => 'POST',
            'content' => [
                'action' => 'create_payout_link',
                'token' => 'BUIj3m2Nx2VvVj',
                'purpose' => 'refund',
                'account_number' => '4564563559247998',
                'amount' => 100,
                'contact' => [
                    'name' => 'testing',
                    'contact' => '9090909090',
                    'email' => 'test@razorpay.com',
                ]
            ]
        ],
        'response' => [
            'content' => [
                'token' => 'BUIj3m2Nx2VvVj'
            ]
        ]
    ],

    'testPayoutLinkSendOtpValidatePayload' => [
        'request' => [
            'url' => '/users/otp/send',
            'method' => 'POST',
            'content' => [
                'action' => 'create_payout_link',
                'token' => 'BUIj3m2Nx2VvVj',
                'purpose' => 'refund',
                'account_number' => '4564563559247998',
                'amount' => 100,
                'contact' => [
                    'name' => 'testing',
                    'contact' => '9090909090',
                    'email' => 'test@razorpay.com',
                ]
            ]
        ],
        'response' => [
            'content' => [
                'token' => 'BUIj3m2Nx2VvVj'
            ]
        ]
    ],
];
