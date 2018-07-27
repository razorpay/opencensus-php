<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateMerchantWithDuplicateEmail' => [
        'request'   => [
            'content' => [
                'id'    => 'randommerchant',
                'name'  => 'Random Name',
                'email' => 'test@razorpay.com',
            ],
            'url'     => '/merchants',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The email has already been taken.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateMerchantWithDuplicateId' => [
        'request'   => [
            'content' => [
                'id'    => '10000000000000',
                'name'  => 'Random Merchant Name',
                'email' => 'test2@razorpay.com',
            ],
            'url'     => '/merchants',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id has already been taken.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateMerchant' => [
        'request'  => [
            'content' => [
                'id'    => '1X4hRFHFx4UiXt',
                'name'  => 'Tester',
                'email' => 'test@localhost.com',
            ],
            'url'     => '/merchants',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'id'                       => '1X4hRFHFx4UiXt',
                'name'                     => 'Tester',
                'email'                    => 'test@localhost.com',
                'pricing_plan_id'          => '1In3Yh5Mluj605',
                'live'                     => false,
                'activated'                => false,
                'hold_funds'               => false,
                'brand_color'              => null,
                'activated_at'             => null,
                'receipt_email_enabled'    => true,
                'transaction_report_email' => [
                    'test@localhost.com'
                ]
            ],
        ],
    ],

    'testGetTerminalsInTestForCreatedMerchant' => [
        'request'  => [
            'url'    => '/merchants/1X4hRFHFx4UiXt/terminals',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'count'  => 0,
                'entity' => 'collection',
                'items'  => [
                ]
            ]
        ],
    ],

    'testGetTerminalsInLiveForCreatedMerchant' => [
        'request'  => [
            'url'    => '/merchants/1X4hRFHFx4UiXt/terminals',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'count'  => 0,
                'entity' => 'collection',
                'items'  => []
            ]
        ],
    ],

    'testBalanceInTestAfterCreatedMerchant' => [
        'request'  => [
            'url'    => '/balance',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'id'      => '1X4hRFHFx4UiXt',
                'balance' => 0
            ]
        ]
    ],

    'testBalanceInLiveAfterCreatedMerchant' => [
        'request'  => [
            'url'    => '/balance',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'id'      => '1X4hRFHFx4UiXt',
                'balance' => 0
            ]
        ],
    ],

    'testGetBankAccountsAfterCreatedMerchant' => [
        'request'  => [
            'url'    => '/merchants/1X4hRFHFx4UiXt/banks',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'enabled'  => [
                    'HDFC' => 'HDFC Bank',
                    'UTIB' => 'Axis Bank',
                ],
                'disabled' => [
                ],
            ]
        ]
    ],

    'testCreateSubMerchant' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'   => 'NewSubmerchant',
                'name' => 'Submerchant',
            ],
        ],
        'response' => [
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'Submerchant',
                // Email is same as the test merchant
                'email' => 'test@razorpay.com',
            ],
        ],
    ],

    'testCreateSubMerchantWithoutFeatureMarketplaceOrPartner' => [
        'request'   => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id' => 'NewSubmerchant',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CANNOT_ADD_SUBMERCHANT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CANNOT_ADD_SUBMERCHANT,
        ],
    ],

    'testCreateSubMerchantWithoutName' => [
        'request'   => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id' => 'NewSubmerchant',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The name field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateSubMerchantWrongUserRole' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'   => 'NewSubmerchant',
                'name' => 'new name',
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Authentication failed',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testCreateSubMerchantWithEmail' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'Submerchant 2',
                'email' => 'submerchant@razorpay.com'
            ],
        ],
        'response' => [
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'Submerchant 2',
                'email' => 'submerchant@razorpay.com',
            ],
        ],
    ],

    'testCreateSubMerchantWithEmailUserExists' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'Submerchant 2',
                'email' => 'submerchant@razorpay.com'
            ],
        ],
        'response' => [
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'Submerchant 2',
                'email' => 'submerchant@razorpay.com',
            ],
        ],
    ],

    'testCreateSubMerchantWithDuplicateEmail' => [
        'request'   => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'Submerchant 2',
                'email' => 'test2@razorpay.com'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The email has already been taken.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateSubMerchantByFullyManagedWOEmail' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'   => 'NewSubmerchant',
                'name' => 'Submerchant',
            ],
        ],
        'response' => [
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'Submerchant',
                'email' => 'test@razorpay.com',
            ],
        ],
    ],

    'testCreateSubMerchantByFullyManagedWithEmail' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'Submerchant',
                'email' => 'testsub@razorpay.com'
            ],
        ],
        'response' => [
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'Submerchant',
                'email' => 'testsub@razorpay.com',
            ],
        ],
    ],

    'testCreateSubMerchantByFullyManagedWithEmailUserExists' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'Submerchant',
                'email' => 'testsub@razorpay.com'
            ],
        ],
        'response' => [
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'Submerchant',
                'email' => 'testsub@razorpay.com',
            ],
        ],
    ],

    'testCreateSubMerchantByAggregatorWithEmail' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'Submerchant',
                'email' => 'testsub@razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'Submerchant',
                'email' => 'testsub@razorpay.com',
            ],
        ],
    ],

    'testCreateSubMerchantByAggregatorWithoutEmail' => [
        'request'   => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'   => 'NewSubmerchant',
                'name' => 'Submerchant',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_SUBMERCHANT_WITHOUT_EMAIL_NOT_ALLOWED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_SUBMERCHANT_WITHOUT_EMAIL_NOT_ALLOWED,
        ],
    ],

    'testCreateSubMerchantByAggregatorWithoutApp' => [
        'request'   => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'Submerchant',
                'email' => 'testsub@razorpay.com',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => RZP\Exception\LogicException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_PARTNER_APP_NOT_FOUND,
            'message'             => 'Server error app not found',
        ],
    ],

    'testCreateSubMerchantByAggregatorExceptionWithoutEmail' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'   => 'NewSubmerchant',
                'name' => 'Submerchant',
            ],
        ],
        'response' => [
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'Submerchant',
                'email' => 'test@razorpay.com',
            ],
        ],
    ],

    'testCreateMarketplaceLinkedAccount' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'      => '7gcKngYfqyDMjN',
                'name'    => 'Linked Account 2',
                'email'   => 'linkedaccount@razorpay.com',
                'account' => true,
            ],
        ],
        'response' => [
            'content' => [
                'id'    => '7gcKngYfqyDMjN',
                'name'  => 'Linked Account 2',
                'email' => 'linkedaccount@razorpay.com',
            ],
        ],
    ],

    'testCreateMarketplaceLinkedAccountWithoutEmail' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'      => '7gcKngYfqyDMjN',
                'name'    => 'Linked Account 2',
                'account' => true,
            ],
        ],
        'response' => [
            'content' => [
                'id'    => '7gcKngYfqyDMjN',
                'name'  => 'Linked Account 2',
                'email' => 'test@razorpay.com',
            ],
        ],
    ],

    'testCreateLinkedAccountMaxPaymentLimit' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'      => '7gcKngYfqyDMjN',
                'name'    => 'Linked Account 4',
                'email'   => 'linkedaccount@razorpay.com',
                'account' => true,
            ],
        ],
        'response' => [
            'content' => [
                'id'                 => '7gcKngYfqyDMjN',
                'name'               => 'Linked Account 4',
                'email'              => 'linkedaccount@razorpay.com',
                'max_payment_amount' => 6000
            ],
        ],
    ],

    'testCreateMarketplaceLAWithoutEmailWithPartnerBank' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'      => '7gcKngYfqyDMjN',
                'name'    => 'Linked Account 2',
                'account' => true,
            ],
        ],
        'response' => [
            'content' => [
                'id'   => '7gcKngYfqyDMjN',
                'name' => 'Linked Account 2',
            ],
        ],
    ],

    'testCreateSubMerchantWithoutEmailWithPartnerFMAndMarketplace' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'   => '7gcKngYfqyDMjN',
                'name' => 'Linked Account 2',
            ],
        ],
        'response' => [
            'content' => [
                'id'   => '7gcKngYfqyDMjN',
                'name' => 'Linked Account 2',
            ],
        ],
    ],

    'testCreateMarketplaceLAWithoutEmailWithPartnerFM' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'      => '7gcKngYfqyDMjN',
                'name'    => 'Linked Account 2',
                'account' => true,
            ],
        ],
        'response' => [
            'content' => [
                'id'   => '7gcKngYfqyDMjN',
                'name' => 'Linked Account 2',
            ],
        ],
    ],

    'testLinkedAccountDefaultSchedule'   => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'      => '7gbqextd68Co4t',
                'name'    => 'Linked Account 3',
                'email'   => 'linkedaccount@razorpay.com',
                'account' => true,
            ],
        ],
        'response' => [
            'content' => [
                'id'    => '7gbqextd68Co4t',
                'name'  => 'Linked Account 3',
                'email' => 'linkedaccount@razorpay.com',
            ],
        ],
    ],

    'testCreateLinkedAccountBatch' => [
        'request'  => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'linked_account',
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'linked_account',
                'status'           => 'created',
                'total_count'      => 2,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'amount'           => 0,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],
];
