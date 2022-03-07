<?php

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateOneTimePromotion' => [
        'request' => [
            'content' => [
                'name'              => 'Test-Promotion',
                'credit_amount'     => 100,
                'credit_type'       => 'amount',
                'iterations'        => 1,
                'credits_expire'    => false,
                'purpose'           => 'Promotion Testing',
            ],
            'url'    => '/promotions',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'name'              => 'Test-Promotion',
                'credit_amount'     => 100,
                'credits_expire'    => false,
                'creator_email'     => 'superadmin@razorpay.com',
            ]
        ]
    ],

    'testCreateOneTimePromotionWithPartnerId' => [
        'request' => [
            'content' => [
                'name'              => 'Test-Promotion',
                'credit_amount'     => 100,
                'credit_type'       => 'amount',
                'iterations'        => 1,
                'credits_expire'    => false,
                'purpose'           => 'Promotion Testing',
                'partner_id'        => '10000000000000'
            ],
            'url'    => '/promotions',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'name'              => 'Test-Promotion',
                'credit_amount'     => 100,
                'credits_expire'    => false,
                'partner_id'        => '10000000000000'
            ]
        ]
    ],

    'testCreateOneTimePromotionWithInvalidPartnerId' => [
        'request' => [
            'content' => [
                'name'              => 'Test-Promotion',
                'credit_amount'     => 100,
                'credit_type'       => 'amount',
                'iterations'        => 1,
                'credits_expire'    => false,
                'purpose'           => 'Promotion Testing',
                'partner_id'        => '10000000000000'
            ],
            'url'    => '/promotions',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_PARTNER_ACTION
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION
        ]
    ],

    'testCreateOneTimePromotionWithNonPartner' => [
        'request' => [
            'content' => [
                'name'              => 'Test-Promotion',
                'credit_amount'     => 100,
                'credit_type'       => 'amount',
                'iterations'        => 1,
                'credits_expire'    => false,
                'purpose'           => 'Promotion Testing',
                'partner_id'        => '10000000000000'
            ],
            'url'    => '/promotions',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_PARTNER_ACTION
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION
        ]
    ],

    'testCreateRecurringPromotion' => [
        'request' => [
            'content' => [
                'name'                    => 'Test-Promotion',
                'credit_amount'           => 100,
                'credit_type'             => 'amount',
                'iterations'              => 2,
                'credits_expire'          => true,
                'credits_expiry_period'   => 'monthly',
                'credits_expiry_interval' => 1,
                'purpose'                 => 'Testing Promotion',
            ],
            'url'    => '/promotions',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'name'              => 'Test-Promotion',
                'credit_amount'     => 100,
                'credits_expire'    => true,
            ]
        ]
    ],

    'testUpdateExistingOnetimePromotion' => [
        'request' => [
            'url'      => '',
            'method'   => 'PATCH',
            'content'  => [
                'name'  => 'Updated_Name'
            ]
        ],
        'response' => [
            'content' => [
                'id'                => null,
                'name'              => 'Updated_Name',
                'credit_amount'     => 100,
                'credits_expire'    => false,
            ]
        ]
    ],

    'testUpdateExistingRecurringPromotion' => [
        'request' => [
            'url'      => '',
            'method'   => 'PATCH',
            'content'  => [
                'credits_expire'           => true,
                'credits_expiry_interval'  => '3',
                'credits_expiry_period'    => 'monthly'
            ]
        ],
        'response' => [
            'content' => [
                'id'                => null,
                'name'              => 'Test-Promotion',
                'credit_amount'     => 100,
            ]
        ]
    ],

    'testUpdateUsedPromotion' => [
        'request' => [
            'url'      => '',
            'method'   => 'PATCH',
            'content'  => [
                'name'  => 'Updated name'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Editing a used promotion is not allowed'
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testFetchPromotionById' => [
        'request' => [
            'url'      => '',
            'method'   => 'GET'
        ],
        'response' => [
            'content' => [
                'id'                => null,
                'name'              => 'Test-Promotion',
                'credit_amount'     => 100,
                'credits_expire'    => false,
            ]
        ]
    ],

    'testGetMultiplePromotions' => [
        'request' => [
            'url'      => '/promotions',
            'method'   => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [

                        'name'              => 'Test-Promotion',
                        'credit_amount'     => 100,
                        'credits_expire'    => false,
                    ]
                ]
            ]
        ]
    ],

    'testPromotionWithUnsupportedCreditType' => [
        'request' => [
            'content' => [
                'name'              => 'Test-Promotion',
                'credit_amount'     => 100,
                'credit_type'       => 'random',
                'iterations'        => 1,
                'credits_expire'    => false,
            ],
            'url'    => '/promotions',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The selected credit type is invalid.'
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testPromotionWithInvalidInterval' => [
        'request' => [
            'content' => [
                'name'                    => 'Test-Promotion',
                'credit_amount'           => 100,
                'credit_type'             => 'amount',
                'iterations'              => 1,
                'credits_expire'          => true,
                'credits_expiry_interval' => 'random',
                'credits_expiry_period'   => 'monthly'
            ],
            'url'    => '/promotions',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The credits expiry interval must be an integer.'
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testPromotionWithInvalidPeriod' => [
        'request' => [
            'content' => [
                'name'                    => 'Test-Promotion',
                'credit_amount'           => 100,
                'credit_type'             => 'amount',
                'iterations'              => 1,
                'credits_expire'          => true,
                'credits_expiry_interval' => 2,
                'credits_expiry_period'   => 'random'
            ],
            'url'    => '/promotions',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The credits expiry period is not valid'
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testPromotionWithMissingPeriod' => [
        'request' => [
            'content' => [
                'name'                    => 'Test-Promotion',
                'credit_amount'           => 100,
                'credit_type'             => 'amount',
                'iterations'              => 1,
                'credits_expire'          => true,
                'credits_expiry_interval' => 2,
            ],
            'url'    => '/promotions',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The credits expiry period field is required when credits expire is 1.'
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testPromotionWithMissingInterval' => [
        'request' => [
            'content' => [
                'name'                    => 'Test-Promotion',
                'credit_amount'           => 100,
                'credit_type'             => 'amount',
                'iterations'              => 1,
                'credits_expire'          => true,
                'credits_expiry_period'   => 'monthly',
            ],
            'url'    => '/promotions',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The credits expiry interval field is required when credits expire is 1.'
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'scheduleEntity' => [
        'interval' => 1,
        'period'   => 'monthly',
        'type'     => 'promotion',
    ],

    'testCreateBankingPromotion' => [
        'request' => [
            'content' => [
                'name'              => 'Test-Promotion',
                'credit_amount'     => 100,
                'credit_type'       => 'reward_fee',
                'purpose'           => 'Promotion Testing',
                'product'           => 'banking',
                'event_id'          => ''
            ],
            'url'    => '/event_promotions',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'name'              => 'Test-Promotion',
            ]
        ]
    ],

    'testCreateBankingPromotionOverlap' => [
        'request' => [
            'content' => [
                'name'              => 'Test-Promotion',
                'purpose'           => 'Promotion Testing',
                'product'           => 'banking',
                'event_id'          => '',
                'credit_type'       => 'reward_fee',
                'credit_amount'     => 100,
            ],
            'url'    => '/event_promotions',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'A promotion for given event already exists'
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACTIVE_PROMOTION_FOR_EVENT_ALREADY_EXISTS
        ]
    ],

    'testDeactivateBankingPromotion' => [
        'request' => [
            'content' => [
            ],
            'url'    => '/promotions',
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'status'              => 'deactivated',
            ]
        ]
    ],

    'testMerchantSignUpWithBankingPromotionWithEndAtNull' => [
        'request' => [
            'content' => [
                'business_type' => '2',
                'department'    => '7',
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
        ],
        'response' => [
            'content' => [
                'business_type'      => '2',
                'transaction_volume' => null,
                'department'         => '7',
                'contact_mobile'     => null,
                'role'               => null,
            ],
        ],
    ],

    'testMerchantSignUpWithBankingPromotionWithEndAtInPast' => [
        'request' => [
            'content' => [
                'business_type' => '2',
                'department'    => '7',
            ],
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'business_type'      => '2',
                'transaction_volume' => null,
                'department'         => '7',
                'contact_mobile'     => null,
                'role'               => null,
            ],
        ],
    ],

    'testMerchantSignUpWithBankingPromotionWithEndAtInFuture' => [
        'request' => [
            'content' => [
                'business_type' => '2',
                'department'    => '7',
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
        ],
        'response' => [
            'content' => [
                'business_type'      => '2',
                'transaction_volume' => null,
                'department'         => '7',
                'contact_mobile'     => null,
                'role'               => null,
            ],
        ],
    ],

    'testUpdatePromotionWithoutPermission' => [
        'request' => [
            'url'      => '',
            'method'   => 'PATCH',
            'content'  => [
                'name'  => 'Updated name'
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ACCESS_DENIED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED,
        ],
    ],

    'testUpdatePromotionWithPermission' => [
        'request' => [
            'url'      => '',
            'method'   => 'PATCH',
            'content'  => [
                'name'  => 'Updated name'
            ]
        ],
        'response'  => [
            'content' => [
                'name' => 'Updated name',
            ],
        ],
    ],

];
