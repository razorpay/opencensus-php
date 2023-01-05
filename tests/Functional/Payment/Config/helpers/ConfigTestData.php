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

    'testUpdateConfigFieldForMultipleLateAuthConfig' => [
        'request' => [
            'content' => [
                'type'      => 'late_auth',
                'config'     => [
                    "capture"=> 'automatic',
                    "capture_options"=> [
                        "manual_expiry_period"=> 1600,
                        "automatic_expiry_period"=> 600,
                        "refund_speed"=> "normal"
                    ],
                ],
            ],
            'method'    => 'PATCH',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'is_default'       => true,
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
    ],

    'testConfigInternalListByIsDefault' => [
        'request' => [
            'method'    => 'GET',
            'url'       => '/internal/config?is_default=true',
        ],
        'response'      =>  [
            'content'   => [
                'count' => 1,
            ],
            'status_code'   =>  200
        ]
    ],

    'testCreateConvenienceFeeConfigWithEmptyRules' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'is_default' => '0',
                'type'       => 'convenience_fee',
                'config'     => [
                    'rules' => []
                ],
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'name'       => 'First',
                'is_default' => false,
                'config'     => [
                    'label' => 'Convenience Fee',
                    'rules' => []
                ],
            ]
        ],
    ],

    'testCreateConvenienceFeeConfigWithNullRules' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'is_default' => '0',
                'type'       => 'convenience_fee',
                'config'     => [
                    'rules' => null
                ],
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'name'       => 'First',
                'is_default' => false,
                'config'     => [],
            ]
        ],
    ],

    'testCreateConvenienceFeeConfigForUPIWithFlatValue' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'is_default' => '0',
                'type'       => 'convenience_fee',
                'config'     => [
                    'message' => 'To prevent additional fee use methods other than UPI',
                    'rules' => [
                        [
                            'method' => 'upi',
                            'fee' => [
                                'payee' => 'business',
                                'flat_value' => 20
                            ],
                        ]
                    ]
                ]
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'name'       => 'First',
                'is_default' => false,
                'config'     => [
                    'label' => 'Convenience Fee',
                    'message' => 'To prevent additional fee use methods other than UPI',
                    'rules' => [
                        'upi' => [
                            'fee' => [
                                'payee' => 'business',
                                'flat_value' => 20
                            ]
                        ]
                    ]
                ],
            ]
        ],
    ],

    'testCreateConvenienceFeeConfigForUPIWithPercentageValue' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'is_default' => '0',
                'type'       => 'convenience_fee',
                'config'     => [
                    'message' => 'To prevent additional fee use methods other than UPI',
                    'rules' => [
                        [
                            'method' => 'upi',
                            'fee' => [
                                'payee' => 'business',
                                'percentage_value' => "20.22"
                            ],
                        ]
                    ]
                ]
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'name'       => 'First',
                'is_default' => false,
                'config'     => [
                    'label' => 'Convenience Fee',
                    'message' => 'To prevent additional fee use methods other than UPI',
                    'rules' => [
                        'upi' => [
                            'fee' => [
                                'payee' => 'business',
                                'percentage_value' => 20.22
                            ]
                        ]
                    ]
                ],
            ]
        ],
    ],

    'testCreateConvenienceFeeConfigForNetbanking' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'is_default' => '0',
                'type'       => 'convenience_fee',
                'config'     => [
                    'rules' => [
                        [
                            'method' => 'netbanking',
                            'fee' => [
                                'payee' => 'customer',
                                'flat_value' => 20
                            ],
                        ]
                    ]
                ]
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'name'       => 'First',
                'is_default' => false,
                'config'     => [
                    'label' => 'Convenience Fee',
                    'rules' => [
                        'netbanking' => [
                            'fee' => [
                                'payee' => 'customer',
                                'flat_value' => 20
                            ]
                        ]
                    ]
                ],
            ]
        ],
    ],

    'testCreateConvenienceFeeConfigWithExtraFieldProvided' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'is_default' => '0',
                'type'       => 'convenience_fee',
                'config'     => [
                    'message' => 'To prevent additional fee use methods other than UPI',
                    'rules' => [
                        [
                            'method' => 'upi',
                            'fee' => [
                                'payee' => 'business',
                                'flat_value' => 20
                            ],
                        ]
                    ],
                    'random' => 'extra field'
                ]
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'convenience_fee.random is/are not required and should not be sent'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
                'class' => 'RZP\Exception\ExtraFieldsException',
                'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED
        ],
    ],

    'testCreateConvenienceFeeConfigForCardWithFlatValue' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'is_default' => '0',
                'type'       => 'convenience_fee',
                'config'     => [
                    'message' => 'To prevent additional fee use methods other than Card',
                    'rules' => [
                        [
                            'method' => 'card',
                            'fee' => [
                                'payee' => 'business',
                                'flat_value' => 20
                            ],
                        ]
                    ]
                ]
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'name'       => 'First',
                'is_default' => false,
                'config'     => [
                    'label' => 'Convenience Fee',
                    'message' => 'To prevent additional fee use methods other than Card',
                    'rules' => [
                        'card' => [
                            'fee' => [
                                'payee' => 'business',
                                'flat_value' => 20
                            ]
                        ]
                    ]
                ],
            ]
        ],
    ],

    'testCreateConvenienceFeeConfigForCardWithPercentageValue' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'is_default' => '0',
                'type'       => 'convenience_fee',
                'config'     => [
                    'message' => 'To prevent additional fee use methods other than Card',
                    'rules' => [
                        [
                            'method' => 'card',
                            'fee' => [
                                'payee' => 'business',
                                'percentage_value' => "20.23"
                            ],
                        ]
                    ]
                ]
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'name'       => 'First',
                'is_default' => false,
                'config'     => [
                    'label' => 'Convenience Fee',
                    'message' => 'To prevent additional fee use methods other than Card',
                    'rules' => [
                        'card' => [
                            'fee' => [
                                'payee' => 'business',
                                'percentage_value' => 20.23
                            ]
                        ]
                    ]
                ],
            ]
        ],
    ],

    'testCreateConvenienceFeeConfigForNonRepeatingCardTypes' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'is_default' => '0',
                'type'       => 'convenience_fee',
                'config'     => [
                    'message' => 'To prevent additional fee use methods other than Card',
                    'rules' => [
                        [
                            "method" => "card",
                            "card.type" => ["prepaid", "debit"],
                            "fee" => [
                                 "payee" => "business",
                                 "flat_value" => 20
                            ],
                        ],
                        [
                            "method" => "card",
                            "card.type" => ["credit"],
                            "fee" => [
                                "payee" => "customer",
                                "percentage_value" => "20"
                            ]
                        ]
                    ]
                ]
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'name'       => 'First',
                'is_default' => false,
                'config'     => [
                    'label' => 'Convenience Fee',
                    'message' => 'To prevent additional fee use methods other than Card',
                    'rules' => [
                        'card' => [
                            'type' => [
                                'prepaid' => [
                                    'fee' => [
                                        "payee" => "business",
                                        "flat_value" => 20
                                    ]
                                ],
                                'debit' => [
                                    'fee' => [
                                        "payee" => "business",
                                        "flat_value" => 20
                                    ]
                                ],
                                'credit' => [
                                    "fee" => [
                                        "payee" => "customer",
                                        "percentage_value" => 20
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ]
        ],
    ],

    'testCreateConvenienceFeeConfigForRepeatingCardTypes' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'is_default' => '0',
                'type'       => 'convenience_fee',
                'config'     => [
                    'message' => 'To prevent additional fee use methods other than Card',
                    'rules' => [
                        [
                            "method" => "card",
                            "card.type" => ["prepaid", "debit"],
                            "fee" => [
                                "payee" => "business",
                                "flat_value" => 20
                            ],
                        ],
                        [
                            "method" => "card",
                            "card.type" => ["debit", "credit"],
                            "fee" => [
                                "payee" => "customer",
                                "flat_value" => 20
                            ]
                        ]
                    ]
                ]
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'error' => [

                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_CONVENIENCE_FEE_CONFIG
        ],

    ],

    'testCreateConvenienceFeeConfigforPercentageFeeInFloat' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'is_default' => '0',
                'type'       => 'convenience_fee',
                'config'     => [
                    'message' => 'To prevent additional fee use methods other than Card',
                    'rules' => [
                        [
                            "method" => "card",
                            "card.type" => ["prepaid", "debit"],
                            "fee" => [
                                "payee" => "business",
                                "percentage_value" => 20.00
                            ],
                        ]

                    ]
                ]
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => 'BAD_REQUEST_ERROR',
                    'description' => 'percentage_value value should be string'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_CONVENIENCE_FEE_CONFIG
        ],

    ],

    'testCreateConvenienceFeeConfigForPercentageFeeInvalidValue' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'is_default' => '0',
                'type'       => 'convenience_fee',
                'config'     => [
                    'message' => 'To prevent additional fee use methods other than Card',
                    'rules' => [
                        [
                            "method" => "card",
                            "card.type" => ["prepaid", "debit"],
                            "fee" => [
                                "payee" => "business",
                                "percentage_value" => "20.009"
                            ],
                        ]

                    ]
                ]
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => 'BAD_REQUEST_ERROR',
                    'description' => 'Incorrect format provided for the parameter. Please check the valid format'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_CONVENIENCE_FEE_CONFIG
        ],

    ],

    'testCreateConvenienceFeeConfigForInvalidMethodName' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'is_default' => '0',
                'type'       => 'convenience_fee',
                'config'     => [
                    'message' => 'To prevent additional fee use methods other than Card',
                    'rules' => [
                        [
                            "method" => "emandate",
                            "fee" => [
                                "payee" => "business",
                                "percentage_value" => "20.0"
                            ],
                        ]

                    ]
                ]
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => 'BAD_REQUEST_ERROR',
                    'description' => 'emandate is not a valid method',
                    'field' => 'convenience_fee_config.rules.emandate'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_CONVENIENCE_FEE_CONFIG
        ],

    ],

    'testCreateConvenienceFeeConfigForFlatValueLessThanZero' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'is_default' => '0',
                'type'       => 'convenience_fee',
                'config'     => [
                    'message' => 'To prevent additional fee use methods other than Card',
                    'rules' => [
                        [
                            "method" => "card",
                            "fee" => [
                                "payee" => "business",
                                "flat_value" => -20
                            ],
                        ]

                    ]
                ]
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => 'BAD_REQUEST_ERROR',
                    'description' => 'The value for this parameter cannot be less than 0',
                    'field' => 'convenience_fee_config.rules.fee.flat_value'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_CONVENIENCE_FEE_CONFIG
        ],
    ],

    'testCreateConvenienceFeeConfigWithExtraFieldProvidedInRules' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'is_default' => '0',
                'type'       => 'convenience_fee',
                'config'     => [
                    'message' => 'To prevent additional fee use methods other than UPI',
                    'rules' => [
                        [
                            'method' => 'upi',
                            'fee' => [
                                'payee' => 'business',
                                'flat_value' => 20
                            ],
                            'random' => 'extra field'
                        ]
                    ],
                ]
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'convenience_fee_config.rules.random is/are not required and should not be sent'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED
        ],
    ],

    'testCreateConvenienceFeeConfigWithRequiredFieldNotProvidedInRules' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'is_default' => '0',
                'type'       => 'convenience_fee',
                'config'     => [
                    'message' => 'To prevent additional fee use methods other than UPI',
                    'rules' => [
                        [
                            'fee' => [
                                'payee' => 'business',
                                'flat_value' => 20
                            ]
                        ]
                    ],
                ]
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The order could not be processed as it is missing required information',
                    'field' => 'convenience_fee_config.rules.method'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_CONVENIENCE_FEE_CONFIG
        ],
    ],

    'testCreateConvenienceFeeConfigWithRepeatingWalletConfig' =>[
        'request' => [
            'content' => [
                'name'       => 'First',
                'is_default' => '0',
                'type'       => 'convenience_fee',
                'config'     => [
                    'message' => 'To prevent additional fee use methods other than Wallet',
                    'rules' => [
                        [
                            "method" => "wallet",
                            "fee" => [
                                "payee" => "business",
                                "flat_value" => 20
                            ],
                        ],
                        [
                            "method" => "wallet",
                            "fee" => [
                                "payee" => "customer",
                                "flat_value" => 20
                            ]
                        ]
                    ]
                ]
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Duplicate configuration for wallet'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_CONVENIENCE_FEE_CONFIG
        ],
    ],

    'testCreateConvenienceFeeConfigWithRepeatingCardConfig' =>[
        'request' => [
            'content' => [
                'name'       => 'First',
                'is_default' => '0',
                'type'       => 'convenience_fee',
                'config'     => [
                    'message' => 'To prevent additional fee use methods other than Card',
                    'rules' => [
                        [
                            "method" => "card",
                            "fee" => [
                                "payee" => "business",
                                "flat_value" => 20
                            ],
                        ],
                        [
                            "method" => "card",
                            "fee" => [
                                "payee" => "customer",
                                "percentage_value" => "99.99"
                            ]
                        ]
                    ]
                ]
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Duplicate configuration for card'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_CONVENIENCE_FEE_CONFIG
        ],
    ],

    'testCreateConvenienceFeeConfigForPercentageFeeLessThanZero' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'is_default' => '0',
                'type'       => 'convenience_fee',
                'config'     => [
                    'message' => 'To prevent additional fee use methods other than Card',
                    'rules' => [
                        [
                            "method" => "card",
                            "card.type" => ["prepaid", "debit"],
                            "fee" => [
                                "payee" => "business",
                                "percentage_value" => "-9"
                            ],
                        ]

                    ]
                ]
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => 'BAD_REQUEST_ERROR',
                    'description' => 'The value for this parameter cannot be less than 0 or greater than 100'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_CONVENIENCE_FEE_CONFIG
        ],

    ],

    'testCreateConvenienceFeeConfigForPercentageGreaterThanMaxValue' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'is_default' => '0',
                'type'       => 'convenience_fee',
                'config'     => [
                    'message' => 'To prevent additional fee use methods other than Card',
                    'rules' => [
                        [
                            "method" => "card",
                            "card.type" => ["prepaid", "debit"],
                            "fee" => [
                                "payee" => "business",
                                "percentage_value" => "101"
                            ],
                        ]

                    ]
                ]
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => 'BAD_REQUEST_ERROR',
                    'description' => 'The value for this parameter cannot be less than 0 or greater than 100'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_CONVENIENCE_FEE_CONFIG
        ],

    ],

    'testCreateConvenienceFeeConfigWithInvalidFeePayee' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'is_default' => '0',
                'type'       => 'convenience_fee',
                'config'     => [
                    'message' => 'To prevent additional fee use methods other than Card',
                    'rules' => [
                        [
                            "method" => "card",
                            "card.type" => ["prepaid", "debit"],
                            "fee" => [
                                "payee" => "platform",
                                "percentage_value" => "100"
                            ],
                        ]

                    ]
                ]
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => 'BAD_REQUEST_ERROR',
                    'description' => 'platform is not a valid value for this parameter.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_CONVENIENCE_FEE_CONFIG
        ],

    ],

    'testCreateConvenienceFeeConfigWithRepeatingCardTypeConfig' =>[
        'request' => [
            'content' => [
                'name'       => 'First',
                'is_default' => '0',
                'type'       => 'convenience_fee',
                'config'     => [
                    'message' => 'To prevent additional fee use methods other than Card',
                    'rules' => [
                        [
                            "method" => "card",
                            "card.type" => ['debit', 'credit'],
                            "fee" => [
                                "payee" => "business",
                                "flat_value" => 20
                            ],
                        ],
                        [
                            "method" => "card",
                            "card.type" => ['debit'],
                            "fee" => [
                                "payee" => "customer",
                                "percentage_value" => "99.99"
                            ]
                        ]
                    ]
                ]
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Duplicate configuration for debit '
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_CONVENIENCE_FEE_CONFIG
        ],
    ],

    'testCreateConvenienceFeeConfigWithInvalidCardType' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'is_default' => '0',
                'type'       => 'convenience_fee',
                'config'     => [
                    'message' => 'To prevent additional fee use methods other than Card',
                    'rules' => [
                        [
                            "method" => "card",
                            "card.type" => ["testCard"],
                            "fee" => [
                                "payee" => "business",
                                "percentage_value" => "50"
                            ],
                        ]

                    ]
                ]
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => 'BAD_REQUEST_ERROR',
                    'description' => 'testCard is not a valid card type.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_CONVENIENCE_FEE_CONFIG
        ],

    ],

    'testCreateConvenienceFeeConfigWithMethodNotSent' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'is_default' => '0',
                'type'       => 'convenience_fee',
                'config'     => [
                    'message' => 'To prevent additional fee use methods other than Card',
                    'rules' => [
                        [
                            "fee" => [
                                "payee" => "business",
                                "flat_value" => 50
                            ],
                        ]

                    ]
                ]
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => 'BAD_REQUEST_ERROR',
                    'description' => 'The order could not be processed as it is missing required information'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_CONVENIENCE_FEE_CONFIG
        ],
    ],

    'testCreateConvenienceFeeConfigInvalidLabelLength' => [
        'request' => [
            'content' => [
                'name'       => 'First',
                'is_default' => '0',
                'type'       => 'convenience_fee',
                'config'     => [
                    'label' => "Convenience Fee Configuration",
                    'message' => 'To prevent additional fee use methods other than Card',
                    'rules' => [
                        [
                            "method" => "card",
                            "fee" => [
                                "payee" => "business",
                                "flat_value" => 50
                            ],
                        ]

                    ]
                ]
            ],
            'method'    => 'POST',
            'url'       => '/payment/config',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => 'BAD_REQUEST_ERROR',
                    'description' => 'label cannot be greater then 20 characters'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_CONVENIENCE_FEE_CONFIG
        ],
    ],

    'testGetCheckoutConfigWithIdInternal' => [
        'request' => [
            'method'    => 'GET',
            'url'       => '/internal/payment/config/checkout',
        ],
        'response' => [
            'content' => [
                'restrictions' => [
                    'allow' => [
                        [
                            'iins' => ['400016'],
                            'method' => 'card',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetDefaultCheckoutConfigInternal' => [
        'request' => [
            'method'    => 'GET',
            'url'       => '/internal/payment/config/checkout',
        ],
        'response' => [
            'content' => [
                'restrictions' => [
                    'allow' => [
                        [
                            'iins' => ['400016'],
                            'method' => 'card',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetDefaultCheckoutConfigInternalWithNoDefaultConfig' => [
        'request' => [
            'method'    => 'GET',
            'url'       => '/internal/payment/config/checkout',
        ],
        'response' => [
            'content' => []
        ],
    ],
];
