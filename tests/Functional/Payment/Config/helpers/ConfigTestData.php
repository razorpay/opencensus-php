<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return  [
    'testCreateCheckoutConfig' => [
        'request' => [
            'content' => [
                'name'       => 'First',
	            'is_default' => true,
                'type'       => 'checkout',
                'config'     => [
                    'method' => 'card',
                ],
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'name'       => 'First',
                'is_default' => true,
                'config'     => [
                    'method' => 'card',
                ],
            ]
        ],
    ],

    'testCreateCheckoutConfigWithDefaultFalse' => [
        'request' => [
            'content' => [
                'name'       => 'Test Config',
                'type'       => 'checkout',
                'is_default' => '0',
                'config'     => [
                    'issuer'   => 'sbi',
                    'network'  => 'visa',
                ],
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'name'       => 'Test Config',
                'is_default' => false,
                'config'     => [
                    'issuer'   => 'sbi',
                    'network'  => 'visa',
                ],
            ]
        ],
    ],

    'testCreateCheckoutConfigWithoutConfig' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'type'       => 'checkout',
                'is_default' => true,
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The config field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreateCheckoutConfigWithoutName' => [
        'request' => [
            'content' => [
                'is_default' => true,
                'type'       => 'checkout',
                'config'     => [
                    'method' => 'card',
                ],
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
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
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreateCheckoutConfigWithConfigNotInJsonFormat' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'type'       => 'checkout',
                'is_default' => true,
                'config'     => 'Wrong',
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The config must be an array.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testUpdateDefaultFieldForCheckoutConfig' => [
        'request' => [
            'content' => [
                'type'      => 'checkout',
                'is_default'=> '0',
            ],
            'method'    => 'PATCH',
            'url'       => '',
        ],
        'response' => [
            'content' => [
                'is_default' => false,
            ]
        ],
    ],

    'testUpdateDefaultFieldForCheckoutConfigWithExistingDefaultConfig' => [
        'request' => [
            'content' => [
                'type'      => 'checkout',
                'is_default'=> true,
            ],
            'method'    => 'PATCH',
            'url'       => '',
        ],
        'response' => [
            'content' => [
                'is_default' => true,
            ]
        ],
    ],

    'testUpdateConfigFieldForCheckoutConfig' => [
        'request' => [
            'content' => [
                'type'      => 'checkout',
                'config'     => [
                    'issuer' => 'sbi',
                ],
                'is_default' => true,
            ],
            'method'    => 'PATCH',
            'url'       => '',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Config field is not required for type checkout',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreateLateAuthConfig' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'is_default' => true,
                'type'       => 'late_auth',
                'config'     => [
                    "capture"=> 'automatic',
                    "capture_options"=> [
                        "manual_expiry_period"=> 1600,
                        "automatic_expiry_period"=> 600,
                        "refund_speed"=> "normal"
                    ]
                ],
                ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'name'       => 'First',
                'is_default' => true,
                'config'     => [
                    "capture"=> 'automatic',
                    "capture_options"=> [
                        "manual_expiry_period"=> 1600,
                        "automatic_expiry_period"=> 600,
                        "refund_speed"=> "normal"
                    ]
                ],
            ]
        ],
    ],

    'testCreateLateAuthConfig' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'is_default' => true,
                'type'       => 'late_auth',
                'config'     => [
                    "capture"=> 'automatic',
                    "capture_options"=> [
                        "manual_expiry_period"=> 1600,
                        "automatic_expiry_period"=> 600,
                        "refund_speed"=> "normal"
                    ]
                ],
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'name'       => 'First',
                'is_default' => true,
                'config'     => [
                    "capture"=> 'automatic',
                    "capture_options"=> [
                        "manual_expiry_period"=> 1600,
                        "automatic_expiry_period"=> 600,
                        "refund_speed"=> "normal"
                    ]
                ],
            ]
        ],
    ],

    'testUpdateConfigFieldForLateAuthConfig' => [
        'request' => [
            'content' => [
                'type'      => 'late_auth',
                'config'     => [
                    "capture"=> 'automatic',
                        "capture_options"=> [
                            "manual_expiry_period"=> 1600,
                            "automatic_expiry_period"=> 600,
                            "refund_speed"=> "normal"
                        ]
                ],
            ],
            'method'    => 'PATCH',
            'url'       => '',
        ],
        'response' => [
            'content' => [
                'name'       => 'Test Config',
                'config'     => [
                    "capture"=> 'automatic',
                    "capture_options"=> [
                        "manual_expiry_period"=> 1600,
                        "automatic_expiry_period"=> 600,
                        "refund_speed"=> "normal"
                    ]
                ],
            ]
        ],
    ],

    'testFetchDccConfig' => [
        'request' => [
            'method'    => 'GET',
            'url'       => '/payment/config/dcc',
        ],
        'response' => [
            'content' => [
                'count' => 1,
                 'items' => [
                    [
                        'entity' => 'config',
                        'name' => 'dcc',
                        'is_default' => false,
                    ]
                ]
            ],
            'status_code' => 200,
        ]
    ],

    'testCreateConfigFieldForDccConfig' => [
        'request' => [
            'content' => [
                'type'          => 'dcc',
                'name'          => 'dcc',
                'is_default'    => '0',
                'config'     => [
                    "dcc_markup_percentage"    => 5,
                ],
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'name'       => 'dcc',
                'config'     => [
                    "dcc_markup_percentage"    => 5,
                ],
            ]
        ],
    ],

    'testErrorCreateConfigFieldForDccConfig' => [
        'request' => [
            'content' => [
                'type'          => 'dcc',
                'name'          => 'dcc',
                'is_default'    => '0',
                'config'     => [
                    "dcc_markup_percentage"    => 5,
                ],
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Dcc Config is already present for the provided merchant',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_DCC_CONFIG_PRESENT,
        ],
    ],

    'testCreateWrongConfigForDccConfig' => [
        'request' => [
            'content' => [
                'type'          => 'dcc',
                'name'          => 'dcc',
                'is_default'    => '0',
                'config'     => [
                    "dcc_markup_amount"    => 5,
                ],
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'dcc_markup_amount is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\ExtraFieldsException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testCreateDecimalValueForDccConfig' => [
        'request' => [
            'content' => [
                'type'          => 'dcc',
                'name'          => 'dcc',
                'is_default'    => '0',
                'config'     => [
                    "dcc_markup_percentage"    => 9.99,
                ],
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'name'       => 'dcc',
                'config'     => [
                    "dcc_markup_percentage"    => 9.99,
                ],
            ]
        ],
    ],

    'testCreateDecimalPrecisionMoreThan2DccConfig' => [
        'request' => [
            'content' => [
                'type'          => 'dcc',
                'name'          => 'dcc',
                'is_default'    => '0',
                'config'     => [
                    "dcc_markup_percentage"    => 5.234,
                ],
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The dcc markup percentage format is invalid.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],


    'testCreateBlankValueForDccConfig' => [
        'request' => [
            'content' => [
                'type'          => 'dcc',
                'name'          => 'dcc',
                'is_default'    => '0',
                'config'     => [
                    "dcc_markup_percentage"    => '',
                ],
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The dcc markup percentage field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateForDccConfig' => [
        'request' => [
            'content' => [
                'type'      => 'dcc',
                    'config'     => [
                        "dcc_markup_percentage"    => 2,
                    ],
            ],
            'method'    => 'PATCH',
            'url'       => '',
        ],
        'response' => [
            'content' => [
                'name'       => 'dcc',
                'config'     => [
                    "dcc_markup_percentage"    => 2,
                ],
            ]
        ],
    ],

    'testDeleteDccConfig' => [
        'request' => [
            'content' => [
                'type'         => 'dcc',
                'merchant_ids' => ['10000000000000'],
            ],
            'method'    => 'DELETE',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'success'  => 1,
                'failures' => [],
            ]
        ],
    ],

    'testCreateCheckoutConfigFromAdminAuth' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'is_default' => true,
                'type'       => 'checkout',
                'config'     => [
                    'method' => 'card',
                ],
            ],
            'method'    => 'POST',
            'url'       => '/admin/payment/config',
            'server' => [
                'HTTP_X_RAZORPAY_ACCOUNT' => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [
                'name'       => 'First',
                'is_default' => true,
                'config'     => [
                    'method' => 'card',
                ],
            ]
        ],
    ],
    'testCreateLocaleConfig' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'is_default' => true,
                'type'       => 'locale',
                'config'     => [
                    'language_code' => 'hi',
                ],
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'name'       => 'First',
                'is_default' => true,
                'config'     => [
                    'language_code' => 'hi',
                ],
            ]
        ],
    ],
    'testCreateLocaleConfigWithExistingDefaultConfig' => [
        'request' => [
            'content' => [
                'name'       => 'Second',
                'is_default' => true,
                'type'       => 'locale',
                'config'     => [
                    'language_code' => 'hi',
                ],
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Default locale config already present for the merchant'
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_DEFAULT_LOCALE_CONFIG_PRESENT
        ]
    ],
    'testUpdateConfigFieldForLocaleConfig' => [
        'request' => [
            'content' => [
                'type'      => 'locale',
                'config'     => [
                    'language_code' => 'en',
                ],
            ],
            'method'    => 'PATCH',
            'url'       => '',
        ],
        'response' => [
            'content' => [
                'config'     => [
                    'language_code' => 'en',
                ],
            ]
        ],
    ],
    'testDeleteLocaleConfig' => [
        'request' => [
            'content' => [
                'type'         => 'checkout',
                'merchant_ids' => ['10000000000000'],
            ],
            'method'    => 'DELETE',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'success'  => 1,
                'failures' => [],
            ]
        ],
    ],
    'testUpdateConfigFieldForLateAuthConfigBulk' => [
        'request' => [
            'content' => [
                'config'     => [
                    "capture"=> 'automatic',
                    "capture_options"=> [
                        "manual_expiry_period"=> 1600,
                        "automatic_expiry_period"=> 600,
                        "refund_speed"=> "normal"
                    ]
                ],
                'merchant_ids' => ['10000000000000'],
            ],
            'method'    => 'PATCH',
            'url'       => '',
        ],
        'response' => [
            'content' => [
                'success'       => 1,
                'failures'      => []
            ]
        ],
    ],

    'testCreateCheckoutConfigBulk' => [
        'request' => [
            'content' => [
                'type'       => 'checkout',
                'config'     => [
                    'method' => 'card',
                ],
                'is_default' => true,
                'merchant_ids' => ['10000000000000'],
            ],
            'method'    => 'POST',
            'url'       => '',
        ],
        'response' => [
            'content' => [
                'success'       => 1,
                'failures'      => []
            ]
        ],
    ],

    'testCreateRiskConfigBulk' => [
        'request' => [
            'content' => [
                'type'       => 'risk',
                'config'     => [
                    'secure_3d_international' => 'v2',
                ],
                'is_default' => true,
                'merchant_ids' => ['10000000000000'],
            ],
            'method'    => 'POST',
            'url'       => '',
        ],
        'response' => [
            'content' => [
                'success'       => 1,
                'failures'      => []
            ]
        ],
    ],

    'testConfigInternalById' => [
        'request' => [
            'method'    => 'GET',
            'url'       => '/internal/config/',
        ],
        'response'      =>  [
            'content' => [
                'id' => '',
            ],
            'status_code'   =>  200
        ]
    ],

    'testConfigInternalList' => [
        'request' => [
            'method'    => 'GET',
            'url'       => '/internal/config?type=locale',
        ],
        'response'      =>  [
            'content'   => [
                'count' => 1,
            ],
            'status_code'   =>  200
        ]
    ]
];
