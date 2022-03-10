<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

$defaultRequestAndResponse = [
        'request' => [
            'content' => [
                'code' => 'RANDOM-123'
            ],
            'url'    => '/coupons',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'entity_type' => 'promotion',
                'code' => 'RANDOM-123'
            ]
        ]
    ];

return [
    'testCouponWithUsage' => $defaultRequestAndResponse,

    'testCouponExceedingUsage' => $defaultRequestAndResponse,

    'testCreateCouponWithInvalidTime' => $defaultRequestAndResponse,

    'testCouponExceedingUsageExceptionData' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_COUPON_LIMIT_REACHED
        ],
    ],

    'testMissingParamsMissingCode' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testMissingParamsMissingMerchant' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID
        ],
    ],


    'testInvaliCouponApply' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_COUPON_CODE
        ],
    ],

    'testCreateCouponWithInvalidTimeExceptionData' => [
       'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Start date can not be greater than end date',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'createCoupon'     => $defaultRequestAndResponse,

    'testCreateCoupon' => $defaultRequestAndResponse,

    'testCreateCouponAndApplyOnMerchant' => $defaultRequestAndResponse,

    'testCreateCouponAndApplyOnMerchantInvalidPartner' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION
        ],
    ],

    'testMultiCouponApply' => $defaultRequestAndResponse,

    'testCreateMultipleCouponsPerPromotion' => [
        'request' => [
            'content' => [
                'entity_type' => 'promotion',
                'code'        => 'RANDOM-456',
                'entity_id'   => '',
            ],
            'url'    => '/coupons',
            'method' => 'POST'
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
                'class' => RZP\Exception\BadRequestException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_PROMOTION_ALREADY_HAS_COUPON
        ],
    ],

    'testValidateCouponProxyAuthWithMerchantId' => [
        'request' => [
            'content' => [
                'code'        => 'RANDOM-123',
                'merchant_id' => '10000000000000',
            ],
            'url'    => '/coupons/validate',
            'method' => 'POST'
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
                'class' => RZP\Exception\BadRequestException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_ID_NOT_REQUIRED
        ],
    ],


    'testUpdateCoupon' => [
        'request' => [
            'content' => [
                'start_at'    => 1545306492,
                'end_at'      => 1545306492,
            ],
            'url'    => '/coupons',
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'start_at'          => null,
                'end_at'            => null,
            ]
        ]
    ],

    'testUpdateCouponWithInvalidTime' => [
        'request' => [
            'content' => [
                'start_at'    => 1545306492,
                'end_at'      => 1545306492,
            ],
            'url'    => '/coupons',
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Start date can not be greater than end date',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testMerchantSignUpWithCoupon' => [
        'request' => [
            'content' => [
                'id'    => '1X4hRFHFx4UiXt',
                'name'  => 'Tester',
                'email' => 'test@localhost.com',
                'coupon_code' => 'RANDOM-123',
            ],
            'url'    => '/merchants',
            'method' => 'POST'
        ],
    ],

    'testMerchantSignUpWithCouponAndActivation' => [
        'request' => [
            'content' => [
                'id'    => '1X4hRFHFx4UiXt',
                'name'  => 'Tester',
                'email' => 'test@localhost.com',
                'coupon_code' => 'RANDOM-123',
            ],
            'url'    => '/merchants',
            'method' => 'POST'
        ],
    ],

    'testMerchantSignUpWithInValidCoupon' => [
        'request' => [
            'content' => [
                'id'    => '1X4hRFHFx4UiXt',
                'name'  => 'Tester',
                'email' => 'test@localhost.com',
                'coupon_code' => 'RANDOM-321',
            ],
            'url'    => '/merchants',
            'method' => 'POST'
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
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_COUPON_CODE
        ],
    ],

    'testMultiCouponApplyExceptionData' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_COUPON_ALREADY_USED
        ],
    ],

    'testGetCouponsByPromotionId' => [
        'request' => [
            'content' => [

            ],
            'url'    => '/coupons',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity_type' => 'promotion',
                        'code' => 'RANDOM'
                    ]
                ]
            ]
        ]
    ],

    'testDeleteCoupon' => [
        'request' => [
            'content' => [

            ],
            'url'    => '/coupons',
            'method' => 'DELETE'
        ],
        'response' => [
            'content' => [
                'deleted' => true
            ]
        ]
    ],

    'testApplyOnetimeCoupon' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'code'  => 'RANDOM'
            ],
            'url'    => '/coupons/apply',
            'method' => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ]
        ],
        'response' => [
            'content' => [
                'message' => 'Coupon Applied Successfully',
            ]
        ]
    ],

    'testApplyRecurringCoupon' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'code'  => 'RANDOM'
            ],
            'url'    => '/coupons/apply',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'message' => 'Coupon Applied Successfully',
            ]
        ]
    ],

    'testDeleteUsedCoupon' => [
        'request' => [
            'content' => [

            ],
            'url'    => '/coupons',
            'method' => 'DELETE'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Deleting a used coupon is not allowed'
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testApplyExpiredCoupon' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_COUPON_EXPIRED
        ],
    ],

    'testApplyNotApplicableCoupon' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_COUPON_NOT_APPLICABLE
        ],
    ],
    'testCouponExpiryAlert' => [
        'response' => [
            'content' => [
                'message' => 'SUCCESSFULLY Generated alerts',
                
                ]
        ]
    ],
];
