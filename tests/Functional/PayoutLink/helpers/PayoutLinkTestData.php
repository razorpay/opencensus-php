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
];
