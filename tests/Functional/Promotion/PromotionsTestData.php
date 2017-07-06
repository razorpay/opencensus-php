<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateOneTimePromotion' => [
        'request' => [
            'content' => [
                'name'              => 'Test-Promotion',
                'credit_amount'     => 100,
                'credit_type'       => 'credit_amount',
                'iterations'        => 1,
                'credits_expire'    => false,
            ],
            'url'    => '/promotions',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'name'              => 'Test-Promotion',
                'credit_amount'     => 100,
                'credits_expire'    => false,
            ]
        ]
    ],

    'testCreateRecurringPromotion' => [
        'request' => [
            'content' => [
                'name'                    => 'Test-Promotion',
                'credit_amount'           => 100,
                'credit_type'             => 'credit_amount',
                'iterations'              => 2,
                'credits_expire'          => true,
                'credits_expiry_period'   => 'monthly',
                'credits_expiry_interval' => 1,
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
                'name'  => 'Updated name'
            ]
        ],
        'response' => [
            'content' => [
                'id'                => null,
                'name'              => 'Updated name',
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
                'credit_type'             => 'credit_amount',
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
                'credit_type'             => 'credit_amount',
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
                'credit_type'             => 'credit_amount',
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
                    'description' => 'The credits expiry period field is required when credits expirable is 1.'
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
                'credit_type'             => 'credit_amount',
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
                    'description' => 'The credits expiry interval field is required when credits expirable is 1.'
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
    ],
];
