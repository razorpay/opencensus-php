<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateLinkedAccountWithMarketplaceFeature'  => [
        'request'   => [
            'url'       => '/v2/accounts',
            'method'    => 'POST',
            'content'   => [
                "email" => "testaccount@email.com",
                "type" => "route",
                "phone" => "1234567890",
                "legal_business_name" => "Acme Corp",
                "business_type" => "private_limited",
                "reference_id" => "route-account-test",
                "profile" => [
                    "category" => "healthcare",
                    "subcategory" => "clinic",
                    "addresses" => [
                        "registered" => [
                            "street1" => "507, Koramangala 1st block",
                            "street2" => "MG Road",
                            "city" => "Bengaluru",
                            "state" => "Karnataka",
                            "postal_code" => 560034,
                            "country" => "IN"
                        ]
                    ],
                    "business_model" => "Healthcare E-commerce platform"
                ],
                "contact_name" => "Test Account"
            ],
        ],
        'response'  =>  [
            'content'   => [
                "type" => "route",
                "status" => "created",
                "email" => "testaccount@email.com",
                "profile" => [
                    "category" => "healthcare",
                    "subcategory" => "clinic",
                    "addresses" => [
                        "registered" => [
                            "street1" => "507, Koramangala 1st block",
                            "street2" => "MG Road",
                            "city" => "Bengaluru",
                            "state" => "KARNATAKA",
                            "postal_code" => 560034,
                            "country" => "IN"
                        ]
                    ],
                    "business_model" => "Healthcare E-commerce platform"
                ],
                "notes" => [
                ],
                "phone" => "+911234567890",
                "contact_name" => "Test Account",
                "reference_id" => "route-account-test",
                "business_type" => "private_limited",
                "legal_business_name" => "Acme Corp",
                "customer_facing_business_name" => "Acme Corp"
            ],
        ],
    ],

    'testRouteProductConfigWithoutRouteNoDocEnabledForUnregMerchant'  => [
        'request'   => [
            'url'       => '/v2/accounts',
            'method'    => 'POST',
            'content'   => [
                "email" => "testaccount@email.com",
                "type" => "route",
                "phone" => "1234567890",
                "legal_business_name" => "Acme Corp",
                "business_type" => "individual",
                "reference_id" => "route-account-test",
                "profile" => [
                    "category" => "healthcare",
                    "subcategory" => "clinic",
                    "addresses" => [
                        "registered" => [
                            "street1" => "507, Koramangala 1st block",
                            "street2" => "MG Road",
                            "city" => "Bengaluru",
                            "state" => "Karnataka",
                            "postal_code" => 560034,
                            "country" => "IN"
                        ]
                    ],
                    "business_model" => "Healthcare E-commerce platform"
                ],
                "contact_name" => "Test Account"
            ],
        ],
        'response'  =>  [
            'content'   => [
                "type" => "route",
                "status" => "created",
                "email" => "testaccount@email.com",
                "profile" => [
                    "category" => "healthcare",
                    "subcategory" => "clinic",
                    "addresses" => [
                        "registered" => [
                            "street1" => "507, Koramangala 1st block",
                            "street2" => "MG Road",
                            "city" => "Bengaluru",
                            "state" => "KARNATAKA",
                            "postal_code" => 560034,
                            "country" => "IN"
                        ]
                    ],
                    "business_model" => "Healthcare E-commerce platform"
                ],
                "notes" => [
                ],
                "phone" => "+911234567890",
                "contact_name" => "Test Account",
                "reference_id" => "route-account-test",
                "business_type" => "individual",
                "legal_business_name" => "Acme Corp",
                "customer_facing_business_name" => "Acme Corp"
            ],
        ],
    ],

    'createActivatedLinkedAccount' => [
        'request'  => [
            'url'     => '/beta/accounts',
            'method'  => 'post',
            'content' => [
                'name'            => 'Linked Account 1',
                'email'           => 'linked1@account.com',
                'tnc_accepted'    => true,
                'notes'           => [
                    'custom_account_id' => 'Qwerty123',
                    'custom_attribute'  => 'some_value',
                ],
                'account_details' => [
                    'business_name' => 'Acme solutions',
                    'business_type' => 'proprietorship',
                ],
                'bank_account' => [
                    'ifsc_code'             => 'ICIC0001206',
                    'account_number'        => '0002020000304030434',
                    'account_type'          => 'current',
                    'beneficiary_name'      => 'Test R4zorpay:'
                ]
            ],
        ],
        'response' => [],
    ],

    'testRouteDefaultConfig'    =>  [
        'request'   =>  [
            'method'    =>   'POST',
            'content'   =>  [
                'product_name'  =>  'route',
                'tnc_accepted'  =>  true
            ],
        ],
        'response'  =>  [
            'content'   =>  [
                "active_configuration" => [
                    "settlements" => [
                        "account_number" => null,
                        "ifsc_code" => null,
                        "beneficiary_name" => null
                    ]
                ],
                "requirements" => [
                    [
                        "field_reference" => "settlements.beneficiary_name",
                        "resolution_url" => "/accounts/{accountId}/products/{merchantProductConfigId}",
                        "reason_code" => "field_missing",
                        "status" => "required"
                    ],
                    [
                        "field_reference" => "settlements.account_number",
                        "resolution_url" => "/accounts/{accountId}/products/{merchantProductConfigId}",
                        "reason_code" => "field_missing",
                        "status" => "required"
                    ],
                    [
                        "field_reference" => "settlements.ifsc_code",
                        "resolution_url" => "/accounts/{accountId}/products/{merchantProductConfigId}",
                        "reason_code" => "field_missing",
                        "status" => "required"
                    ],
                    [
                        'field_reference' => 'name',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                ],
                "product_name" => "route",
            ],
        ],
    ],
    'testRouteProductConfigWithRouteNoDocEnabled'    =>  [
        'request'   =>  [
            'method'    =>   'POST',
            'content'   =>  [
                'product_name'  =>  'route',
                'tnc_accepted'  =>  true
            ],
        ],
        'response'  =>  [
            'content'   =>  [
                "active_configuration" => [
                    "settlements" => [
                        "account_number" => null,
                        "ifsc_code" => null,
                        "beneficiary_name" => null
                    ]
                ],
                "requirements" => [
                    [
                        "field_reference" => "name",
                        "resolution_url" => "/accounts/{accountId}/stakeholders",
                        "status" => "required",
                        "reason_code" => "field_missing",
                    ],
                    [
                        "field_reference" => "settlements.account_number",
                        "resolution_url" => "/accounts/{accountId}/products/{merchantProductConfigId}",
                        "reason_code" => "field_missing",
                        "status" => "required",
                    ],
                    [
                        "field_reference" => "settlements.beneficiary_name",
                        "resolution_url" => "/accounts/{accountId}/products/{merchantProductConfigId}",
                        "reason_code" => "field_missing",
                        "status" => "required",
                    ],
                    [
                        "field_reference" => "settlements.ifsc_code",
                        "resolution_url" => "/accounts/{accountId}/products/{merchantProductConfigId}",
                        "reason_code" => "field_missing",
                        "status" => "required",
                    ],
                ],
                "product_name"          => "route",
                "activation_status"     =>  'needs_clarification'
            ],
        ],
    ],
    'testRouteProductConfigWithRouteNoDocEnabledUnregisteredBusiness'    =>  [
        'request'   =>  [
            'method'    =>   'POST',
            'content'   =>  [
                'product_name'  =>  'route',
                'tnc_accepted'  =>  true
            ],
        ],
        'response'  =>  [
            'content'   =>  [
                "active_configuration" => [
                    "settlements" => [
                        "account_number" => null,
                        "ifsc_code" => null,
                        "beneficiary_name" => null
                    ]
                ],
                "requirements" => [
                    [
                        'field_reference' => 'kyc.pan',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        "field_reference" => "name",
                        "resolution_url" => "/accounts/{accountId}/stakeholders",
                        "status" => "required",
                        "reason_code" => "field_missing",
                    ],
                    [
                        "field_reference" => "settlements.account_number",
                        "resolution_url" => "/accounts/{accountId}/products/{merchantProductConfigId}",
                        "reason_code" => "field_missing",
                        "status" => "required",
                    ],
                    [
                        "field_reference" => "settlements.beneficiary_name",
                        "resolution_url" => "/accounts/{accountId}/products/{merchantProductConfigId}",
                        "reason_code" => "field_missing",
                        "status" => "required",
                    ],
                    [
                        "field_reference" => "settlements.ifsc_code",
                        "resolution_url" => "/accounts/{accountId}/products/{merchantProductConfigId}",
                        "reason_code" => "field_missing",
                        "status" => "required",
                    ],
                ],
                "product_name"          => "route",
                "activation_status"     =>  'needs_clarification'
            ],
        ],
    ],
    'testUpdateAccountV2'   => [
      'request'     =>  [
          'url'     =>  '/v2/accounts/{accountId}',
          'method'  =>  'PATCH',
          'content' =>  [],
      ],
      'response'    =>  [],
    ],

    'testRequestRouteProductLinkedAccountWithDifferentParentMId'    =>  [
        'request'   =>  [
            'method'    =>   'POST',
            'content'   =>  [
                'product_name'  =>  'route',
                'tnc_accepted'  =>  true
            ],
        ],
        'response'  =>  [
            'content'   =>  [
                    'error_code'    =>  PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   =>  'Linked account does not exist'
                ],
            'status_code'   => 400,
        ],
        'exception' =>  [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_ID_DOES_NOT_EXIST,
        ],
    ],
    'testFetchRouteConfig' => [
        'request'  => [
            'url'    => '/v2/accounts/{accountId}/products/{productId}',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'active_configuration' => [
                    "settlements" => [
                        "account_number" => null,
                        "ifsc_code" => null,
                        "beneficiary_name" => null
                    ]
                ],
                'product_name'         => 'route'
            ],
        ]
    ],
    'testUpdateRouteConfig' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products',
            'method'  => 'PATCH',
            'content' => [
                'settlements' => [
                    'ifsc_code'             => 'ICIC0001206',
                    'account_number'        => '0002020000304030434',
                    'beneficiary_name'      => 'Test Razorpay'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'active_configuration' => [
                    'settlements'     => [
                        'ifsc_code'             => 'ICIC0001206',
                        'account_number'        => '0002020000304030434',
                        'beneficiary_name'      => 'Test Razorpay'
                    ],
                ],
                'product_name'         => 'route'
            ],
        ]
    ],
    'testUpdateRouteConfigWithExtraFields' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products',
            'method'  => 'PATCH',
            'content' => [
                'checkout'    => [
                    'theme_color' => '#FFFFFA',
                ],
                'settlements' => [
                    'ifsc_code'             => 'ICIC0001206',
                    'account_number'        => '0002020000304030434',
                    'beneficiary_name'      => 'Test Razorpay'
                ],
            ],
        ],
        'response'  =>  [
            'content'   =>  [
                'error_code'    =>  PublicErrorCode::BAD_REQUEST_ERROR,
                'description'   =>  'checkout is/are not required and should not be sent'
            ],
            'status_code'   => 400,
        ],
        'exception' =>  [
            'class'               => RZP\Exception\ExtraFieldsException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],
    'testCreateStakeholder' =>  [
      'request'     =>  [
          'url'     =>  '/v2/accounts/{accountId}/stakeholders',
          'method'  => 'POST',
          'content' => [
              'name'    =>  'Shubham gupta',
              'email'   =>  'shubham.gupta@email.com'
          ]
      ],
      'response'    =>  [
          'content' =>  [
              'name'    => 'Shubham gupta',
              'email'   => 'shubham.gupta@email.com'
          ]
      ],
    ],
    'mockBVSInputData' => [
        'bank_account' => [
            'success'   => [
                "error_code" => "",
                "error_description" => "",
                "status" => "success"
            ],
            'failed'    => [
                "error_code" => "INPUT_DATA_ISSUE",
                "error_description" => "KC03::Incorrect beneficiary name",
                "status" => "failed"
            ]
        ],
        'business_pan'  =>  [
            'success'   => [
                "error_code" => "",
                "error_description" => "",
                "status" => "success"
            ],
            'failed'    => [
                "error_code" => "INPUT_DATA_ISSUE",
                "error_description" => "invalid data submitted or PAN is new which is issued in last 10 days, please retry after 15 minutes",
                "status" => "failed"
            ]
        ],
        'personal_pan'  =>  [
            'success'   => [
                "error_code" => "",
                "error_description" => "",
                "status" => "success"
            ],
            'failed'    => [
                "error_code" => "INPUT_DATA_ISSUE",
                "error_description" => "invalid data submitted or PAN is new which is issued in last 10 days, please retry after 15 minutes",
                "status" => "failed"
            ]
        ],
        'gstin'  =>  [
            'success'   => [
                "error_code" => "",
                "error_description" => "",
                "status" => "success"
            ],
            'failed'    => [
                "error_code" => "INPUT_DATA_ISSUE",
                "error_description" => "invalid data submitted",
                "status" => "failed"
            ]
        ],
    ],
];
