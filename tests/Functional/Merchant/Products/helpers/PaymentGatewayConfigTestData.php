<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'createUnregisteredBusinessTypeAccount' => [
        'request'  => [
            'url'     => '/v2/accounts',
            'method'  => 'POST',
            'content' => [
                'email'                         => 'testcreateaccountaa@razorpay.com',
                'phone'                         => '9999999999',
                'legal_business_name'           => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name' => 'Acme',
                'business_type'                 => 'individual',
                'contact_name'                  => 'contactname',
                'profile'                       => [
                    'category'       => 'healthcare',
                    'subcategory'    => 'clinic',
                    'description'    => 'Healthcare E-commerce platform',
                    'business_model' => 'b2c',
                    'addresses'      => [
                        'operation'  => [
                            'street1'     => '507, Koramangala 1st block',
                            'street2'     => 'MG Road',
                            'city'        => 'Bengaluru',
                            'state'       => 'Karnataka',
                            'postal_code' => 560034,
                            'country'     => 'IN'
                        ],
                        'registered' => [
                            'street1'     => '507, Koramangala 1st block',
                            'street2'     => 'MG Road',
                            'city'        => 'Bengaluru',
                            'state'       => 'Karnataka',
                            'postal_code' => 560034,
                            'country'     => 'IN'
                        ]
                    ],
                ],
                'brand'                         => [
                    'color' => '000000',
                ],
                'contact_info'                  => [
                    'chargeback' => [
                        'email'      => 'cb@acme.org',
                        'phone'      => '8951496311',
                        'policy_url' => 'https://www.google.com'
                    ],
                    'refund'     => [
                        'email'      => 'cb@acme.org',
                        'phone'      => '8951496311',
                        'policy_url' => 'https://www.google.com'
                    ],
                    'support'    => [
                        'email'      => 'support@acme.org',
                        'phone'      => '8951496311',
                        'policy_url' => 'https://www.google.com'
                    ]
                ],
                'notes'                         => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
            ],
        ],
        'response' => [
            'content' => [
                'type'                          => 'standard',
                'status'                        => 'created',
                'email'                         => 'testcreateaccountaa@razorpay.com',
                'phone'                         => '+919999999999',
                'legal_business_name'           => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name' => 'Acme',
                'business_type'                 => 'individual',
                'contact_name'                  => 'contactname',
                'profile'                       => [
                    'description'    => 'Healthcare E-commerce platform',
                    'business_model' => 'b2c',
                    'addresses'      => [
                        'operation'  => [
                            'street1'     => '507, Koramangala 1st block',
                            'street2'     => 'MG Road',
                            'city'        => 'Bengaluru',
                            'state'       => 'KARNATAKA',
                            'postal_code' => 560034,
                            'country'     => 'IN'
                        ],
                        'registered' => [
                            'street1'     => '507, Koramangala 1st block',
                            'street2'     => 'MG Road',
                            'city'        => 'Bengaluru',
                            'state'       => 'KARNATAKA',
                            'postal_code' => 560034,
                            'country'     => 'IN'
                        ]
                    ],
                ],
                'brand'                         => [
                    'color' => '#000000',
                ],
                'contact_info'                  => [
                    'chargeback' => [
                        'email'      => 'cb@acme.org',
                        'phone'      => '8951496311',
                        'policy_url' => 'https://www.google.com'
                    ],
                    'refund'     => [
                        'email'      => 'cb@acme.org',
                        'phone'      => '8951496311',
                        'policy_url' => 'https://www.google.com'
                    ],
                    'support'    => [
                        'email'      => 'support@acme.org',
                        'phone'      => '8951496311',
                        'policy_url' => 'https://www.google.com'
                    ]
                ],
                'notes'                         => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
            ],
        ],
    ],

    'testCreateDefaultPaymentGatewayConfig' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products',
            'method'  => 'POST',
            'content' => [
                'product_name' => 'payment_gateway'
            ],
        ],
        'response' => [
            'content' => [
                'active_configuration' => [
                    'payment_capture' => [
                        'mode'                    => 'automatic',
                        'refund_speed'            => 'normal',
                        'automatic_expiry_period' => 7200
                    ],
                    'notifications'   => [
                        'sms'      => false,
                        'whatsapp' => false
                    ],
                    'checkout'        => [
                        'theme_color'    => '#000000',
                        'flash_checkout' => true
                    ],
                    'refund'          => [
                        'default_refund_speed' => 'normal'
                    ]
                ]
            ],
        ]
    ],

    'testCreateProductConfigInvalidInput' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products',
            'method'  => 'POST',
            'content' => [
                'product_name' => 'abcd'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The product requested is invalid',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PRODUCT_NAME,
        ],

    ],

    'testFetchDefaultPaymentGatewayConfig' => [
        'request'  => [
            'url'    => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'active_configuration' => [
                    'payment_capture' => [
                        'mode'                    => 'automatic',
                        'refund_speed'            => 'normal',
                        'automatic_expiry_period' => 7200
                    ],
                    'notifications'   => [
                        'sms'      => false,
                        'whatsapp' => false
                    ],
                    'checkout'        => [
                        'theme_color'    => '#000000',
                        'flash_checkout' => true
                    ],
                    'refund'          => [
                        'default_refund_speed' => 'normal'
                    ]
                ]
            ],
        ]
    ],

    'testUpdatePaymentGatewayConfig' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method'  => 'PATCH',
            'content' => [
                'notifications'   => [
                    'sms' => true,
                    'whatsapp' => true
                ],
                'settlements'     => [
                    'account_number' => '051610100039258',
                    'ifsc_code'      => 'UBIN0805165'
                ],
                'checkout'        => [
                    'flash_checkout' => false,
                    'logo'           => __DIR__ . '/sample_valid_logo.jpg'
                ],
                'payment_methods' => [
                    'wallet' => [
                        'instrument' => ['airtelmoney']
                    ]
                ]
            ]
        ],
        'response' => [
            'content' => [
                'requested_configuration' => [
                    'payment_methods' => [
                        'wallet' => [
                            'instrument' => ['airtelmoney']
                        ]
                    ]
                ],
                'active_configuration'    => [
                    'payment_capture' => [
                        'mode'                    => 'automatic',
                        'refund_speed'            => 'normal',
                        'automatic_expiry_period' => 7200
                    ],
                    'notifications'   => [
                        'sms'      => true,
                        'whatsapp' => false
                    ],
                    'checkout'        => [
                        'theme_color'    => '#000000',
                        'flash_checkout' => false,
                    ],
                    'refund'          => [
                        'default_refund_speed' => 'normal'
                    ],
                    'settlements'     => [
                        'account_number' => '051610100039258',
                        'ifsc_code'      => 'UBIN0805165'
                    ],
                ]
            ],
        ]
    ],

    'testNonStringAccNumberAsInputWithPatchProductConfig' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method'  => 'PATCH',
            'content' => [
                'notifications'   => [
                    'sms' => true,
                    'whatsapp' => true
                ],
                'settlements'     => [
                    'account_number' => 51610100039258,
                    'ifsc_code'      => 'UBIN0805165'
                ],
                'checkout'        => [
                    'flash_checkout' => false,
                    'logo'           => __DIR__ . '/sample_valid_logo.jpg'
                ]
            ]
        ],
        'response' => [
            'content' => [
                'requested_configuration' => [
                ],
                'active_configuration'    => [
                    'payment_capture' => [
                        'mode'                    => 'automatic',
                        'refund_speed'            => 'normal',
                        'automatic_expiry_period' => 7200
                    ],
                    'notifications'   => [
                        'sms'      => true,
                        'whatsapp' => false
                    ],
                    'checkout'        => [
                        'theme_color'    => '#000000',
                        'flash_checkout' => false,
                    ],
                    'refund'          => [
                        'default_refund_speed' => 'normal'
                    ],
                    'settlements'     => [
                        'account_number' => '51610100039258',
                        'ifsc_code'      => 'UBIN0805165'
                    ],
                ]
            ],
        ]
    ],

    'testUpdatePaymentGatewayConfigWithCardsInstrument' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method'  => 'PATCH',
            'content' => [
                'notifications'   => [
                    'sms' => true
                ],
                'settlements'     => [
                    'account_number' => '051610100039258',
                    'ifsc_code'      => 'UBIN0805165'
                ],
                'checkout'        => [
                    'flash_checkout' => false,
                    'logo'           => __DIR__ . '/sample_valid_logo.jpg'
                ],
                'payment_methods' => [
                    'cards' => [
                        'instrument' => [
                            [
                                "issuer" => 'visa',
                                "type"   => ['domestic']
                            ]
                        ]
                    ]
                ]
            ]
        ],
        'response' => [
            'content' => [
                'requested_configuration' => [
                    'payment_methods' => [
                        'cards' => [
                            'instrument' => [
                                [
                                    "issuer" => 'visa',
                                    "type"   => ['domestic']
                                ]
                            ]
                        ]
                    ]
                ],
                'active_configuration'    => [
                    'payment_capture' => [
                        'mode'                    => 'automatic',
                        'refund_speed'            => 'normal',
                        'automatic_expiry_period' => 7200
                    ],
                    'notifications'   => [
                        'sms'      => true,
                        'whatsapp' => false
                    ],
                    'checkout'        => [
                        'theme_color'    => '#000000',
                        'flash_checkout' => false,
                    ],
                    'refund'          => [
                        'default_refund_speed' => 'normal'
                    ],
                    'settlements'     => [
                        'account_number' => '051610100039258',
                        'ifsc_code'      => 'UBIN0805165'
                    ],
                ]
            ],
        ]
    ],

    'testUpdatePaymentGatewayConfigOfCardsWithExperimentEnabled' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method'  => 'PATCH',
            'content' => [
                'notifications'   => [
                    'sms' => true
                ],
                'settlements'     => [
                    'account_number' => '051610100039258',
                    'ifsc_code'      => 'UBIN0805165'
                ],
                'checkout'        => [
                    'flash_checkout' => false,
                    'logo'           => __DIR__ . '/sample_valid_logo.jpg'
                ],
                'payment_methods' => [
                    'cards' => [
                        'instrument' => [
                            "issuer" => 'visa',
                            "type"   => 'domestic'
                        ]
                    ]
                ]
            ]
        ],
        'response' => [
            'content' => [
                'requested_configuration' => [
                    'payment_methods' => [
                        'cards' => [
                            'instrument' => [
                                [
                                    "issuer" => 'visa',
                                    "type"   => ['domestic']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
        ]
    ],

    'testUpdatePaymentGatewayConfigOfNetbankingWithExperimentEnabled' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method'  => 'PATCH',
            'content' => [
                'notifications'   => [
                    'sms' => true
                ],
                'settlements'     => [
                    'account_number' => '051610100039258',
                    'ifsc_code'      => 'UBIN0805165'
                ],
                'checkout'        => [
                    'flash_checkout' => false,
                    'logo'           => __DIR__ . '/sample_valid_logo.jpg'
                ],
                'payment_methods' => [
                    'netbanking' => [
                        'instrument' => [
                            "type" => 'retail',
                            "bank"   => 'scbl'
                        ]
                    ]
                ]
            ]
        ],
        'response' => [
            'content' => [
                'requested_configuration' => [
                    'payment_methods' => [
                        'netbanking' => [
                            'instrument' => [
                                [
                                    "type" => 'retail',
                                    "bank"   => ["scbl"]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
        ]
    ],

    'testUpdatePaymentGatewayConfigOfWalletWithExperimentEnabled' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method'  => 'PATCH',
            'content' => [
                'notifications'   => [
                    'sms' => true
                ],
                'settlements'     => [
                    'account_number' => '051610100039258',
                    'ifsc_code'      => 'UBIN0805165'
                ],
                'checkout'        => [
                    'flash_checkout' => false,
                    'logo'           => __DIR__ . '/sample_valid_logo.jpg'
                ],
                'payment_methods' => [
                    'wallet' => [
                        "enabled" => true,
                        'instrument' => "airtelmoney"
                    ]
                ]
            ]
        ],
        'response' => [
            'content' => [
                'requested_configuration' => [
                    'payment_methods' => [
                        'wallet' => [
                            'instrument' => ["airtelmoney"]
                        ]
                    ]
                ]
            ],
        ]
    ],

    'testUpdatePaymentGatewayConfigOfPaylaterWithExperimentEnabled' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method'  => 'PATCH',
            'content' => [
                'notifications'   => [
                    'sms' => true
                ],
                'settlements'     => [
                    'account_number' => '051610100039258',
                    'ifsc_code'      => 'UBIN0805165'
                ],
                'checkout'        => [
                    'flash_checkout' => false,
                    'logo'           => __DIR__ . '/sample_valid_logo.jpg'
                ],
                'payment_methods' => [
                    'paylater' => [
                        "enabled" => true,
                        'instrument' => "epaylater"
                    ]
                ]
            ]
        ],
        'response' => [
            'content' => [
                'requested_configuration' => [
                    'payment_methods' => [
                        'paylater' => [
                            'instrument' => ["epaylater"]
                        ]
                    ]
                ]
            ],
        ]
    ],

    'testUpdatePaymentGatewayConfigWithInvalidLogoResolution' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method'  => 'PATCH',
            'content' => [
                'checkout'        => [
                    'flash_checkout' => false,
                    'logo'           => __DIR__ . '/sample_invalid_logo.jpeg'
                ],
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The height and width of the logo are not the same. Upload a square image.',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_LOGO_NOT_SQUARE,
        ],
    ],

    'testUpdatePaymentGatewayConfigWithInvalidLogoPath' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method'  => 'PATCH',
            'content' => [
                'checkout'        => [
                    'flash_checkout' => false,
                    'logo'           => __DIR__ . '/sample_invalid_logo_path.jpeg'
                ],
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Error occurred while fetching logo from url provided',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_FETCH_LOGO_FROM_URL_FAILED,
        ],
    ],

    'testRequirementsForUnregisteredBusiness' => [
        'request'  => [
            'url'    => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'requirements' => [
                    [
                        'field_reference' => 'tnc_accepted',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'ip',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'individual_proof_of_address',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders/{stakeholderId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing'
                    ],
                    [
                        'field_reference' => 'settlements.beneficiary_name',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'settlements.account_number',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'settlements.ifsc_code',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'name',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'kyc.pan',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                ]
            ]
        ]
    ],

    'updateSettlementFields' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method'  => 'PATCH',
            'content' => [
                'settlements' => [
                    'account_number'   => '123576432234',
                    'ifsc_code'        => 'HDFC0000317',
                    'beneficiary_name' => 'bank - account name'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'active_configuration' => [
                    'payment_capture' => [
                        'mode'                    => 'automatic',
                        'refund_speed'            => 'normal',
                        'automatic_expiry_period' => 7200
                    ],
                    'notifications'   => [
                        'sms'      => false,
                        'whatsapp' => false
                    ],
                    'checkout'        => [
                        'theme_color' => '#000000'
                    ],
                    'refund'          => [
                        'default_refund_speed' => 'normal'
                    ],
                    'settlements'     => [
                        'account_number'   => '123576432234',
                        'ifsc_code'        => 'HDFC0000317',
                        'beneficiary_name' => 'bank - account name'
                    ],
                ],
                'requirements'         => [
                    [
                        'field_reference' => 'tnc_accepted',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'ip',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'individual_proof_of_address',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders/{stakeholderId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing'
                    ],
                    [
                        'field_reference' => 'name',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'kyc.pan',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                ]
            ],
        ],
    ],

    'updateSettlementFieldsForRegisteredBusiness' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method'  => 'PATCH',
            'content' => [
                'settlements' => [
                    'account_number'   => '123576432234',
                    'ifsc_code'        => 'HDFC0000317',
                    'beneficiary_name' => 'bank account name'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'active_configuration' => [
                    'payment_capture' => [
                        'mode'                    => 'automatic',
                        'refund_speed'            => 'normal',
                        'automatic_expiry_period' => 7200
                    ],
                    'notifications'   => [
                        'sms'      => false,
                        'whatsapp' => false
                    ],
                    'checkout'        => [
                        'theme_color' => '#000000'
                    ],
                    'refund'        => [
                        'default_refund_speed' => 'normal'
                    ],
                    'settlements'     => [
                        'account_number'   => '123576432234',
                        'ifsc_code'        => 'HDFC0000317',
                        'beneficiary_name' => 'bank account name'
                    ],
                ],
                'requirements'         => [
                    [
                        'field_reference' => 'tnc_accepted',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'ip',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'business_proof_of_identification.business_pan_url',
                        'resolution_url'  => '/accounts/{accountId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing'
                    ],
                    [
                        'field_reference' => 'business_proof_of_identification.business_proof_url',
                        'resolution_url'  => '/accounts/{accountId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing'
                    ],
                    [
                        'field_reference' => 'individual_proof_of_address',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders/{stakeholderId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing'
                    ],
                    [
                        'field_reference' => 'name',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'kyc.pan',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'legal_info.pan',
                        'resolution_url'  => '/accounts/{accountId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'legal_info.cin',
                        'resolution_url'  => '/accounts/{accountId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                ]
            ],
        ],
    ],

    'testUpdateSettlementDetailsForRegisteredBusiness' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method'  => 'PATCH',
            'content' => [
                'settlements' => [
                    'account_number'   => '123576432234',
                    'ifsc_code'        => 'HDFC0000317',
                    'beneficiary_name' => 'bank account name'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'active_configuration' => [
                    'payment_capture' => [
                        'mode'                    => 'automatic',
                        'refund_speed'            => 'normal',
                        'automatic_expiry_period' => 7200
                    ],
                    'notifications'   => [
                        'sms'      => false,
                        'whatsapp' => false
                    ],
                    'checkout'        => [
                        'theme_color' => '#000000'
                    ],
                    'refund'        => [
                        'default_refund_speed' => 'normal'
                    ],
                    'settlements'     => [
                        'account_number'   => '123576432234',
                        'ifsc_code'        => 'HDFC0000317',
                        'beneficiary_name' => 'bank account name'
                    ],
                ],
                'requirements'         => [
                    [
                        'field_reference' => 'tnc_accepted',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'ip',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'business_proof_of_identification.business_pan_url',
                        'resolution_url'  => '/accounts/{accountId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing'
                    ],
                    [
                        'field_reference' => 'business_proof_of_identification.business_proof_url',
                        'resolution_url'  => '/accounts/{accountId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing'
                    ],
                    [
                        'field_reference' => 'individual_proof_of_address',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders/{stakeholderId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing'
                    ],
                    [
                        'field_reference' => 'legal_info.pan',
                        'resolution_url'  => '/accounts/{accountId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'legal_info.cin',
                        'resolution_url'  => '/accounts/{accountId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                ]
            ],
        ],
    ],

    'testUpdateSettlementDetailsForRegisteredBusinessWithoutIp' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method'  => 'PATCH',
            'content' => [
                'settlements' => [
                    'account_number'   => '123576432234',
                    'ifsc_code'        => 'HDFC0000317',
                    'beneficiary_name' => 'bank account name'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'active_configuration' => [
                    'payment_capture' => [
                        'mode'                    => 'automatic',
                        'refund_speed'            => 'normal',
                        'automatic_expiry_period' => 7200
                    ],
                    'notifications'   => [
                        'sms'      => false,
                        'whatsapp' => false
                    ],
                    'checkout'        => [
                        'theme_color' => '#000000'
                    ],
                    'refund'        => [
                        'default_refund_speed' => 'normal'
                    ],
                    'settlements'     => [
                        'account_number'   => '123576432234',
                        'ifsc_code'        => 'HDFC0000317',
                        'beneficiary_name' => 'bank account name'
                    ],
                ],
                'requirements'         => [
                    [
                        'field_reference' => 'tnc_accepted',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'business_proof_of_identification.business_pan_url',
                        'resolution_url'  => '/accounts/{accountId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing'
                    ],
                    [
                        'field_reference' => 'business_proof_of_identification.business_proof_url',
                        'resolution_url'  => '/accounts/{accountId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing'
                    ],
                    [
                        'field_reference' => 'individual_proof_of_address',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders/{stakeholderId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing'
                    ],
                    [
                        'field_reference' => 'legal_info.pan',
                        'resolution_url'  => '/accounts/{accountId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'legal_info.cin',
                        'resolution_url'  => '/accounts/{accountId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                ]
            ],
        ],
    ],

    'testAcceptTncWithoutIpUsingPostProductConfigWhenExperimentDisabled' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products',
            'method'  => 'POST',
            'content' => [
                'product_name' => 'payment_gateway',
                'tnc_accepted' => true
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Both tnc_accepted and ip fields are required while accepting tnc'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TNC_ACCEPTANCE_AND_IP_NOT_TOGETHER,
        ],
    ],

    'acceptTncUsingPostProductConfig' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products',
            'method'  => 'POST',
            'content' => [
                'product_name' => 'payment_gateway',
                'tnc_accepted' => true,
                'ip'           => '223.233.71.18'
            ],
        ],
        'response' => [
            'content' => [
                'requirements'         => [
                    [
                        'field_reference' => 'individual_proof_of_address',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders/{stakeholderId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing'
                    ],
                    [
                        'field_reference' => 'settlements.beneficiary_name',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'settlements.account_number',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'settlements.ifsc_code',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'name',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'kyc.pan',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                ],
                'tnc'     => [
                    'accepted'   => true
                ]
            ],
        ],
    ],

    'testAcceptTncWithoutIpUsingPatchProductConfigWhenExperimentDisabled' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method'  => 'PATCH',
            'content' => [
                'tnc_accepted' => true
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Both tnc_accepted and ip fields are required while accepting tnc'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TNC_ACCEPTANCE_AND_IP_NOT_TOGETHER,
        ],
    ],

    'acceptTncUsingPatchProductConfig' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method'  => 'PATCH',
            'content' => [
                'tnc_accepted' => true,
                'ip'           => '223.233.71.18'
            ],
        ],
        'response' => [
            'content' => [
                'requirements'         => [
                    [
                        'field_reference' => 'individual_proof_of_address',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders/{stakeholderId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing'
                    ],
                    [
                        'field_reference' => 'settlements.beneficiary_name',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'settlements.account_number',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'settlements.ifsc_code',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'name',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'kyc.pan',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                ],
                'tnc'     => [
                    'accepted'   => true
                ]
            ],
        ],
    ],

    'acceptedTncResponseUsingPatchProductConfig' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method'  => 'PATCH',
            'content' => [
                'tnc_accepted' => true,
                'ip'           => '223.233.71.18'
            ],
        ],
        'response' => [
            'content' => [
                'requirements'         => [
                    [
                        'field_reference' => 'individual_proof_of_address',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders/{stakeholderId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing'
                    ],
                    [
                        'field_reference' => 'settlements.beneficiary_name',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'settlements.account_number',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'settlements.ifsc_code',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'name',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'kyc.pan',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                ]
            ],
        ],
    ],

    'acceptTncUsingCreateProductConfigForNoDoc' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products',
            'method'  => 'POST',
            'content' => [
                'product_name' => 'payment_links',
                'tnc_accepted' => true,
                'ip'           => '223.233.71.18'
            ],
        ],
        'response' => [
            'content' => [
                'requirements'         => [
                    [
                        'field_reference' => 'otp.contact_mobile',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'otp.external_reference_number',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'optional',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'otp.otp_submission_timestamp',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'optional',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'otp.otp_verification_timestamp',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'optional',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'legal_info.pan',
                        'resolution_url'  => '/accounts/{accountId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'name',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'kyc.pan',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'settlements.beneficiary_name',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'settlements.account_number',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'settlements.ifsc_code',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'individual_proof_of_address',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders/{stakeholderId}/documents',
                        'status'          => 'optional',
                        'reason_code'     => 'document_missing'
                    ],
                ],
                'tnc'     => [
                    'accepted'   => true
                ]
            ],
        ],
    ],

    'createProductConfigForNoDoc' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products',
            'method'  => 'POST',
            'content' => [
                'product_name' => 'payment_links'
            ],
        ],
        'response' => [
            'content' => [
                'requirements'         => [
                    [
                        'field_reference' => 'otp.contact_mobile',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'otp.external_reference_number',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'optional',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'otp.otp_submission_timestamp',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'optional',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'otp.otp_verification_timestamp',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'optional',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'tnc_accepted',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'ip',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'legal_info.pan',
                        'resolution_url'  => '/accounts/{accountId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'name',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'kyc.pan',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'settlements.beneficiary_name',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'settlements.account_number',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'settlements.ifsc_code',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'individual_proof_of_address',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders/{stakeholderId}/documents',
                        'status'          => 'optional',
                        'reason_code'     => 'document_missing'
                    ],
                ],
                'tnc'     => []
            ],
        ],
    ],

    'acceptTncUsingUpdateProductConfigForNoDoc' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method'  => 'PATCH',
            'content' => [
                'tnc_accepted' => true,
                'ip'           => '223.233.71.18'
            ],
        ],
        'response' => [
            'content' => [
                'requirements'         => [
                    [
                        'field_reference' => 'otp.contact_mobile',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'otp.external_reference_number',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'optional',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'otp.otp_submission_timestamp',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'optional',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'otp.otp_verification_timestamp',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'optional',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'legal_info.pan',
                        'resolution_url'  => '/accounts/{accountId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'name',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'kyc.pan',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'settlements.beneficiary_name',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'settlements.account_number',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'settlements.ifsc_code',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'individual_proof_of_address',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders/{stakeholderId}/documents',
                        'status'          => 'optional',
                        'reason_code'     => 'document_missing'
                    ],
                ],
                'tnc'     => [
                    'accepted'   => true
                ]
            ],
        ],
    ],

    'testAcceptTncWithoutIpForNoDoc' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products',
            'method'  => 'POST',
            'content' => [
                'product_name' => 'payment_links',
                'tnc_accepted' => true
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Both tnc_accepted and ip fields are required while accepting tnc'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TNC_ACCEPTANCE_AND_IP_NOT_TOGETHER,
        ],
    ],

    'testCreateStakeholderForThinRequest' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/stakeholders',
            'method'  => 'POST',
            'content' => [
                'name'  => 'Rahul Sharma',
                'email' => 'rahul@acme.org',
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'stakeholder',
                'name'         => 'Rahul Sharma',
                'email'        => 'rahul@acme.org',
                'relationship' => [],
                'notes'        => [],
                'phone'        => [],
                'kyc'          => [],
            ],
        ],
    ],

    'testUpdateStakeholderDetails' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/stakeholders/{stakeholderId}',
            'method'  => 'PATCH',
            'content' => [
                'kyc' => [
                    'pan' => 'EBCPK8222J',
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'stakeholder',
                'name'         => 'Rahul Sharma',
                'email'        => 'rahul@acme.org',
                'relationship' => [],
                'notes'        => [],
                'phone'        => [],
                'kyc'          => [
                    'pan' => 'EBCPK8222J'
                ],
            ],
        ],
    ],

    'testRequirementsForUnregisteredBusinessAfterStakeholderDetailsSubmission' => [
        'request'  => [
            'url'    => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'requirements' => [
                    [
                        'field_reference' => 'tnc_accepted',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'ip',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'individual_proof_of_address',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders/{stakeholderId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing'
                    ],

                ]
            ]
        ]
    ],

    'testEmptyRequirements' => [
        'request'  => [
            'url'    => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'requirements'      => [],
                'activation_status' => 'under_review'
            ]
        ]
    ],

    'testPostStakeholderDocumentAadharFront' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/stakeholders/{stakeholderId}/documents',
            'method'  => 'POST',
            'content' => [
                'document_type' => 'aadhar_front',
            ]
        ],
        'response' => [
            'content' => [
                'individual_proof_of_address' => [
                    [
                        'type' => 'aadhar_front',
                        'url'  => 'paper-mandate/generated/ppm_DczOAf1V7oqaDA_DczOEhobMkq2Do.pdf',
                    ],
                ]
            ],
        ]
    ],

    'testPostStakeholderDocumentAadharBack' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/stakeholders/{stakeholderId}/documents',
            'method'  => 'POST',
            'content' => [
                'document_type' => 'aadhar_back',
            ]
        ],
        'response' => [
            'content' => [
                'individual_proof_of_address' => [
                    [
                        'type' => 'aadhar_front',
                        'url'  => 'paper-mandate/generated/ppm_DczOAf1V7oqaDA_DczOEhobMkq2Do.pdf',
                    ],
                    [
                        'type' => 'aadhar_back',
                        'url'  => 'paper-mandate/generated/ppm_DczOAf1V7oqaDA_DczOEhobMkq2Do.pdf',
                    ],
                ]
            ],
        ]
    ],

    'createRegisteredBusinessTypeAccount' => [
        'request'  => [
            'url'     => '/v2/accounts',
            'method'  => 'POST',
            'content' => [
                'email'                         => 'testcreateaccountaa@razorpay.com',
                'phone'                         => '9999999999',
                'legal_business_name'           => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name' => 'Acme',
                'business_type'                 => 'public_limited',
                'contact_name'                  => 'contactname',
                'profile'                       => [
                    'category'       => 'healthcare',
                    'subcategory'    => 'clinic',
                    'description'    => 'Healthcare E-commerce platform',
                    'business_model' => 'b2c',
                    'addresses'      => [
                        'operation'  => [
                            'street1'     => '507, Koramangala 1st block',
                            'street2'     => 'MG Road',
                            'city'        => 'Bengaluru',
                            'state'       => 'Karnataka',
                            'postal_code' => 560034,
                            'country'     => 'IN'
                        ],
                        'registered' => [
                            'street1'     => '507, Koramangala 1st block',
                            'street2'     => 'MG Road',
                            'city'        => 'Bengaluru',
                            'state'       => 'Karnataka',
                            'postal_code' => 560034,
                            'country'     => 'IN'
                        ]
                    ],
                ],
                'brand'                         => [
                    'color' => '000000',
                ],
                'contact_info'                  => [
                    'chargeback' => [
                        'email'      => 'cb@acme.org',
                        'phone'      => '8951496311',
                        'policy_url' => 'https://www.google.com'
                    ],
                    'refund'     => [
                        'email'      => 'cb@acme.org',
                        'phone'      => '8951496311',
                        'policy_url' => 'https://www.google.com'
                    ],
                    'support'    => [
                        'email'      => 'support@acme.org',
                        'phone'      => '8951496311',
                        'policy_url' => 'https://www.google.com'
                    ]
                ],
                'apps'                          => [
                    'websites' => [
                        'https://www.google.com/'
                    ],
                    'android'  => [
                        [
                            'url'  => 'https://play.google.com/store/apps/details?id=com.razorpay.payments.app',
                            'name' => 'razorpay'
                        ]
                    ],
                    'ios'      => [
                        [
                            'url'  => 'https://apps.apple.com/in/app/twitter/id333903271',
                            'name' => 'twitter'
                        ]
                    ]
                ],
                'notes'                         => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
            ],
        ],
        'response' => [
            'content' => [
                'type'                          => 'standard',
                'status'                        => 'created',
                'email'                         => 'testcreateaccountaa@razorpay.com',
                'phone'                         => '+919999999999',
                'legal_business_name'           => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name' => 'Acme',
                'business_type'                 => 'public_limited',
                'contact_name'                  => 'contactname',
                'profile'                       => [
                    'category'       => 'healthcare',
                    'subcategory'    => 'clinic',
                    'description'    => 'Healthcare E-commerce platform',
                    'business_model' => 'b2c',
                    'addresses'      => [
                        'operation'  => [
                            'street1'     => '507, Koramangala 1st block',
                            'street2'     => 'MG Road',
                            'city'        => 'Bengaluru',
                            'state'       => 'KARNATAKA',
                            'postal_code' => 560034,
                            'country'     => 'IN'
                        ],
                        'registered' => [
                            'street1'     => '507, Koramangala 1st block',
                            'street2'     => 'MG Road',
                            'city'        => 'Bengaluru',
                            'state'       => 'KARNATAKA',
                            'postal_code' => 560034,
                            'country'     => 'IN'
                        ]
                    ],
                ],
                'brand'                         => [
                    'color' => '#000000',
                ],
                'contact_info'                  => [
                    'chargeback' => [
                        'email'      => 'cb@acme.org',
                        'phone'      => '8951496311',
                        'policy_url' => 'https://www.google.com'
                    ],
                    'refund'     => [
                        'email'      => 'cb@acme.org',
                        'phone'      => '8951496311',
                        'policy_url' => 'https://www.google.com'
                    ],
                    'support'    => [
                        'email'      => 'support@acme.org',
                        'phone'      => '8951496311',
                        'policy_url' => 'https://www.google.com'
                    ]
                ],
                'apps'                          => [
                    'websites' => [
                        'https://www.google.com/'
                    ],
                    'android'  => [
                        [
                            'url'  => 'https://play.google.com/store/apps/details?id=com.razorpay.payments.app',
                            'name' => 'razorpay'
                        ]
                    ],
                    'ios'      => [
                        [
                            'url'  => 'https://apps.apple.com/in/app/twitter/id333903271',
                            'name' => 'twitter'
                        ]
                    ]
                ],
                'notes'                         => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
            ],
        ],
    ],

    'testRequirementsForRegisteredBusiness' => [
        'request'  => [
            'url'    => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'requirements' => [
                    [
                        'field_reference' => 'tnc_accepted',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'ip',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'business_proof_of_identification.business_pan_url',
                        'resolution_url'  => '/accounts/{accountId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing'
                    ],
                    [
                        'field_reference' => 'business_proof_of_identification.business_proof_url',
                        'resolution_url'  => '/accounts/{accountId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing'
                    ],
                    [
                        'field_reference' => 'individual_proof_of_address',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders/{stakeholderId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing'
                    ],
                    [
                        'field_reference' => 'settlements.beneficiary_name',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'settlements.account_number',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'settlements.ifsc_code',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'name',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'kyc.pan',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'legal_info.pan',
                        'resolution_url'  => '/accounts/{accountId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'legal_info.cin',
                        'resolution_url'  => '/accounts/{accountId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],

                ]
            ]
        ]
    ],

    'testRequirementsForRegisteredBusinessAfterStakeholderDetailsSubmission' => [
        'request'  => [
            'url'    => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'requirements' => [
                    [
                        'field_reference' => 'tnc_accepted',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'ip',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'business_proof_of_identification.business_pan_url',
                        'resolution_url'  => '/accounts/{accountId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing'
                    ],
                    [
                        'field_reference' => 'business_proof_of_identification.business_proof_url',
                        'resolution_url'  => '/accounts/{accountId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing'
                    ],
                    [
                        'field_reference' => 'individual_proof_of_address',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders/{stakeholderId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing'
                    ],
                    [
                        'field_reference' => 'legal_info.pan',
                        'resolution_url'  => '/accounts/{accountId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'legal_info.cin',
                        'resolution_url'  => '/accounts/{accountId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                ]
            ]
        ]
    ],

    'testRequirementsForRegisteredBusinessAfterStakeholderDocSubmission' => [
        'request'  => [
            'url'    => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'requirements' => [
                    [
                        'field_reference' => 'tnc_accepted',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'business_proof_of_identification.business_pan_url',
                        'resolution_url'  => '/accounts/{accountId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing'
                    ],
                    [
                        'field_reference' => 'business_proof_of_identification.business_proof_url',
                        'resolution_url'  => '/accounts/{accountId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing'
                    ],
                    [
                        'field_reference' => 'legal_info.pan',
                        'resolution_url'  => '/accounts/{accountId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'legal_info.cin',
                        'resolution_url'  => '/accounts/{accountId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                ]
            ]
        ]
    ],

    'updateBusinessProofDetails' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}',
            'method'  => 'PATCH',
            'content' => [
                'legal_info' => [
                    'pan' => 'AAACL1234C',
                    'cin' => 'U67190TN2014PTC096978'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'type'                          => 'standard',
                'status'                        => 'created',
                'email'                         => 'testcreateaccountaa@razorpay.com',
                'legal_business_name'           => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name' => 'Acme',
                'business_type'                 => 'public_limited',
                'legal_info'                    => [
                    'pan' => 'AAACL1234C',
                    'cin' => 'U67190TN2014PTC096978'
                ],
            ],
        ],
    ],

    'testPostBusinessProofDocument' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/documents',
            'method'  => 'POST',
            'content' => [
                'document_type' => 'business_proof_url',
            ]
        ],
        'response' => [
            'content' => [
                'business_proof_of_identification' => [
                    [
                        'type' => 'business_proof_url',
                        'url'  => 'paper-mandate/generated/ppm_DczOAf1V7oqaDA_DczOEhobMkq2Do.pdf',
                    ],
                ]
            ],
        ]
    ],

    'testPostBusinessPanDocument' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/documents',
            'method'  => 'POST',
            'content' => [
                'document_type' => 'business_pan_url',
            ]
        ],
        'response' => [
            'content' => [
                'business_proof_of_identification' => [
                    [
                        'type' => 'business_pan_url',
                        'url'  => 'paper-mandate/generated/ppm_DczOAf1V7oqaDA_DczOEhobMkq2Do.pdf',
                    ],
                    [
                        'type' => 'business_proof_url',
                        'url'  => 'paper-mandate/generated/ppm_DczOAf1V7oqaDA_DczOEhobMkq2Do.pdf',
                    ]
                ]
            ],
        ]
    ],

    'testRequirementsInNCState' => [
        'request'  => [
            'url'    => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'requirements' => [
                    [
                        'field_reference' => 'tnc_accepted',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'ip',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'individual_proof_of_address.aadhar_front',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders/{stakeholderId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'needs_clarification',
                        'description'     => 'The document attached is not legible. Please resubmit a clearer copy'
                    ],
                    [
                        'field_reference' => 'additional_documents',
                        'resolution_url'  => '/accounts/{accountId}/documents',
                        'status'          => 'required',
                        'description'     => "We're unable to validate the account number from the document attached. Kindly submit a cancelled cheque/welcome letter merged along with the document.",
                        'reason_code'     => 'needs_clarification'
                    ],
                    [
                        'field_reference' => 'name',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders/{stakeholderId}',
                        'status'          => 'required',
                        'reason_code'     => 'needs_clarification'
                    ],
                    [
                        'field_reference' => 'settlements.account_number',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'description'     => "We're unable to validate the account number from the document attached. Kindly submit a cancelled cheque/welcome letter merged along with the document.",
                        'reason_code'     => 'needs_clarification'
                    ],
                ]
            ]
        ]
    ],

    'testUpdateAccountNonNCFields' => [
        'request'   => [
            'url'     => '/v2/accounts/{accountId}',
            'method'  => 'PATCH',
            'content' => [
                'profile' => [
                    'addresses' => [
                        'registered' => [
                            'street1'     => '507, Koramangala 1st block',
                            'street2'     => 'MG Road',
                            'city'        => 'Hyderabad',
                            'state'       => 'Telangana',
                            'postal_code' => 560034,
                            'country'     => 'IN'
                        ]
                    ]
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Only fields requested for needs clarification are allowed for update',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ONLY_NEEDS_CLARIFICATION_FIELDS_ARE_ALLOWED,
        ],
    ],

    'testUploadNonNCDocument' => [
        'request'   => [
            'url'     => '/v2/accounts/{accountId}/stakeholder/{stakeholderId}/documents',
            'method'  => 'POST',
            'content' => [
                'document_type' => 'aadhar_back',
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Only documents requested for needs clarification are allowed for upload',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ONLY_NEEDS_CLARIFICATION_DOCUMENTS_ARE_ALLOWED,
        ],
    ],

    'testMerchantActivationStatus' => [
        'request' => [
            'content' => [
                'activation_status'  => 'needs_clarification',
            ],
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'activation_status'  => 'needs_clarification',
            ],
        ],
    ],

    'testDefaultPaymentMethods' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products',
            'method'  => 'POST',
            'content' => [
                'product_name' => 'payment_gateway'
            ],
        ],
        'response' => [
            'content' => [
                'active_configuration' => [
                    'payment_capture' => [
                        'mode'                    => 'automatic',
                        'refund_speed'            => 'normal',
                        'automatic_expiry_period' => 7200
                    ],
                    'notifications'   => [
                        'sms'      => false,
                        'whatsapp' => false
                    ],
                    'checkout'        => [
                        'theme_color'    => '#000000',
                        'flash_checkout' => true
                    ],
                    'refund'          => [
                        'default_refund_speed' => 'normal'
                    ],
                    'payment_methods' => [
                        'netbanking' => [
                            'enabled' => true,
                            'instrument' =>  [
                                [
                                    'type' =>  "retail",
                                    'bank' => ["scbl", "aubl", "airp"]
                                ]
                            ]
                        ],
                        'emi' =>  [
                            'enabled' => true,
                            'instrument' => [
                                [
                                    'type' =>  "cardless_emi",
                                    'partner' => [ "zestmoney" ]
                                ],
                                [
                                    'type' => "card_emi",
                                    'partner' => [ "debit", "credit"]
                                ]
                            ]]
                    ]
                ],
            ]
        ]
    ],

    'fetchAccountTnc' => [
        'request'  => [
            'url'    => '/v2/accounts/{id}/tnc',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'status'   => 'active',
                'content'  => [
                    'terms' => 'https://www.razorpay.com/terms/'
                ],
                'accepted' => false
            ],

        ]
    ],

    'testAcceptedAccountTnc' => [
        'request'  => [
            'url'    => '/v2/accounts/{id}/tnc',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'status'   => 'active',
                'content'  => [
                    'terms' => 'https://www.razorpay.com/terms/'
                ],
                'accepted' => true
            ],

        ]
    ],

    'acceptAccountTnc' => [
        'request'  => [
            'url'     => '/v2/accounts/{id}/tnc',
            'method'  => 'POST',
            'content' => [
                'accepted' => true,
                'ip'       => '223.233.71.18'
            ],
        ],
        'response' => [
            'content' => [
                'status'   => 'active',
                'content'  => [
                    'terms' => 'https://www.razorpay.com/terms/'
                ],
                'accepted' => true
            ]
        ]
    ],
    'testAcceptAccountTncWithoutIpWhenExperimentEnabled' => [
        'request'  => [
            'url'     => '/v2/accounts/{id}/tnc',
            'method'  => 'POST',
            'content' => [
                'accepted' => true
            ],
        ],
        'response' => [
            'content' => [
                'status'   => 'active',
                'content'  => [
                    'terms' => 'https://www.razorpay.com/terms/'
                ],
                'accepted' => true
            ]
        ]
    ],
    'testAcceptAccountTncWithoutIpWhenExperimentDisabled' => [
        'request'  => [
            'url'     => '/v2/accounts/{id}/tnc',
            'method'  => 'POST',
            'content' => [
                'accepted' => true
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Both tnc_accepted and ip fields are required while accepting tnc'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TNC_ACCEPTANCE_AND_IP_NOT_TOGETHER,
        ],
    ],
    'createAccountWithoutBrandColor' => [
        'request'  => [
            'url'     => '/v2/accounts',
            'method'  => 'POST',
            'content' => [
                'email'                         => 'testcreateaccountaa@razorpay.com',
                'phone'                         => '9999999999',
                'legal_business_name'           => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name' => 'Acme',
                'business_type'                 => 'individual',
                'contact_name'                  => 'contactname',
                'profile'                       => [
                    'category'       => 'healthcare',
                    'subcategory'    => 'clinic',
                    'description'    => 'Healthcare E-commerce platform',
                    'business_model' => 'b2c',
                    'addresses'      => [
                        'operation'  => [
                            'street1'     => '507, Koramangala 1st block',
                            'street2'     => 'MG Road',
                            'city'        => 'Bengaluru',
                            'state'       => 'Karnataka',
                            'postal_code' => 560034,
                            'country'     => 'IN'
                        ],
                        'registered' => [
                            'street1'     => '507, Koramangala 1st block',
                            'street2'     => 'MG Road',
                            'city'        => 'Bengaluru',
                            'state'       => 'Karnataka',
                            'postal_code' => 560034,
                            'country'     => 'IN'
                        ]
                    ],
                ],
                'contact_info'                  => [
                    'chargeback' => [
                        'email'      => 'cb@acme.org',
                        'phone'      => '8951496311',
                        'policy_url' => 'https://www.google.com'
                    ],
                    'refund'     => [
                        'email'      => 'cb@acme.org',
                        'phone'      => '8951496311',
                        'policy_url' => 'https://www.google.com'
                    ],
                    'support'    => [
                        'email'      => 'support@acme.org',
                        'phone'      => '8951496311',
                        'policy_url' => 'https://www.google.com'
                    ]
                ],
                'notes'                         => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
            ],
        ],
        'response' => [
            'content' => [
                'type'                          => 'standard',
                'status'                        => 'created',
                'email'                         => 'testcreateaccountaa@razorpay.com',
                'phone'                         => '+919999999999',
                'legal_business_name'           => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name' => 'Acme',
                'business_type'                 => 'individual',
                'contact_name'                  => 'contactname',
                'profile'                       => [
                    'description'    => 'Healthcare E-commerce platform',
                    'business_model' => 'b2c',
                    'addresses'      => [
                        'operation'  => [
                            'street1'     => '507, Koramangala 1st block',
                            'street2'     => 'MG Road',
                            'city'        => 'Bengaluru',
                            'state'       => 'KARNATAKA',
                            'postal_code' => 560034,
                            'country'     => 'IN'
                        ],
                        'registered' => [
                            'street1'     => '507, Koramangala 1st block',
                            'street2'     => 'MG Road',
                            'city'        => 'Bengaluru',
                            'state'       => 'KARNATAKA',
                            'postal_code' => 560034,
                            'country'     => 'IN'
                        ]
                    ],
                ],
                'contact_info'                  => [
                    'chargeback' => [
                        'email'      => 'cb@acme.org',
                        'phone'      => '8951496311',
                        'policy_url' => 'https://www.google.com'
                    ],
                    'refund'     => [
                        'email'      => 'cb@acme.org',
                        'phone'      => '8951496311',
                        'policy_url' => 'https://www.google.com'
                    ],
                    'support'    => [
                        'email'      => 'support@acme.org',
                        'phone'      => '8951496311',
                        'policy_url' => 'https://www.google.com'
                    ]
                ],
                'notes'                         => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
            ],
        ],
    ],
    'testCreateDefaultPaymentConfig' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products',
            'method'  => 'POST',
            'content' => [
                'product_name' => 'payment_gateway'
            ],
        ],
        'response' => [
            'content' => [
                'active_configuration' => [
                    'payment_capture' => [
                        'mode'                    => 'automatic',
                        'refund_speed'            => 'normal',
                        'automatic_expiry_period' => 7200
                    ],
                    'notifications'   => [
                        'sms'      => false,
                        'whatsapp' => false
                    ],
                    'checkout'        => [
                        'theme_color'    => '#FFFFFF',
                        'flash_checkout' => true
                    ],
                    'refund'          => [
                        'default_refund_speed' => 'normal'
                    ]
                ]
            ],
        ]
    ],

    'testRequirementsForOtpVerficationlog' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method'  => 'PATCH',
            'content' => [
                'otp' => [
                    'contact_mobile'             => '+919999999999',
                    'external_reference_number'  => 'Shk@123',
                    'otp_submission_timestamp'   => '1653847138',
                    'otp_verification_timestamp' => '1653848138'
                ],
                'tnc_accepted' => true,
                'ip'           => '223.233.71.18'
            ],
        ],
        'response' => [
            'content' => [
                'active_configuration' => [
                    'otp' => [
                        'contact_mobile'             => '+919999999999',
                        'external_reference_number'  => 'Shk@123',
                        'otp_submission_timestamp'   => '1653847138',
                        'otp_verification_timestamp' => '1653848138'
                    ]
                ],
                'requirements'         => [
                    [
                        'field_reference' => 'legal_info.pan',
                        'resolution_url'  => '/accounts/{accountId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'name',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'kyc.pan',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'settlements.beneficiary_name',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'settlements.account_number',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'settlements.ifsc_code',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'individual_proof_of_address',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders/{stakeholderId}/documents',
                        'status'          => 'optional',
                        'reason_code'     => 'document_missing'
                    ],
                ]
            ],
        ]
    ],

    'testRequirementsRegisteredInstantlyActivated' => [
        'request'  => [
            'url'    => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'requirements' => [
                    [
                        'field_reference' => 'tnc_accepted',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'ip',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'business_proof_of_identification.business_pan_url',
                        'resolution_url'  => '/accounts/{accountId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing'
                    ],
                    [
                        'field_reference' => 'business_proof_of_identification.business_proof_url',
                        'resolution_url'  => '/accounts/{accountId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing'
                    ],
                    [
                        'field_reference' => 'individual_proof_of_address',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders/{stakeholderId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing'
                    ],
                    [
                        'field_reference' => 'settlements.beneficiary_name',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'settlements.account_number',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'settlements.ifsc_code',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                ]
            ]
        ]
    ],

    'testRequirementsUnRegisteredInstantlyActivated' => [
        'request'  => [
            'url'    => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'requirements' => [
                    [
                        'field_reference' => 'tnc_accepted',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'ip',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'individual_proof_of_address',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders/{stakeholderId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing'
                    ],
                    [
                        'field_reference' => 'settlements.beneficiary_name',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'settlements.account_number',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'settlements.ifsc_code',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                ],
                'activation_status' => 'instantly_activated'
            ]
        ]
    ],

    'testRequirementsUnRegisteredInstantlyActivatedLimitBreached' => [
        'request'  => [
            'url'    => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'requirements' => [
                    [
                        'field_reference' => 'tnc_accepted',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing',
                        'description'     => 'You can no longer accept payments as you have breached the INR 15,000 limit. Kindly fill in the remaining details to re-activate your account.'
                    ],
                    [
                        'field_reference' => 'ip',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing',
                        'description'     => 'You can no longer accept payments as you have breached the INR 15,000 limit. Kindly fill in the remaining details to re-activate your account.'
                    ],
                    [
                        'field_reference' => 'business_proof_of_identification.business_pan_url',
                        'resolution_url'  => '/accounts/{accountId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing',
                        'description'     => 'You can no longer accept payments as you have breached the INR 15,000 limit. Kindly fill in the remaining details to re-activate your account.'
                    ],
                    [
                        'field_reference' => 'business_proof_of_identification.business_proof_url',
                        'resolution_url'  => '/accounts/{accountId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing',
                        'description'     => 'You can no longer accept payments as you have breached the INR 15,000 limit. Kindly fill in the remaining details to re-activate your account.'
                    ],
                    [
                        'field_reference' => 'individual_proof_of_address',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders/{stakeholderId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing',
                        'description'     => 'You can no longer accept payments as you have breached the INR 15,000 limit. Kindly fill in the remaining details to re-activate your account.'
                    ],
                    [
                        'field_reference' => 'settlements.beneficiary_name',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing',
                        'description'     => 'You can no longer accept payments as you have breached the INR 15,000 limit. Kindly fill in the remaining details to re-activate your account.'
                    ],
                    [
                        'field_reference' => 'settlements.account_number',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing',
                        'description'     => 'You can no longer accept payments as you have breached the INR 15,000 limit. Kindly fill in the remaining details to re-activate your account.'
                    ],
                    [
                        'field_reference' => 'settlements.ifsc_code',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing',
                        'description'     => 'You can no longer accept payments as you have breached the INR 15,000 limit. Kindly fill in the remaining details to re-activate your account.'
                    ],
                ],
                'activation_status' => 'instantly_activated'
            ]
        ]
    ],

    'createUnregisteredBusinessTypeAccountForNoDocWithPan' => [
        'request'  => [
            'url'     => '/v2/accounts',
            'method'  => 'POST',
            'content' => [
                'email'                         => 'testcreateaccountaa@razorpay.com',
                'phone'                         => '9999999999',
                'legal_business_name'           => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name' => 'Acme',
                'business_type'                 => 'individual',
                'contact_name'                  => 'contactname',
                'profile'                       => [
                    'category'       => 'healthcare',
                    'subcategory'    => 'clinic',
                    'description'    => 'Healthcare E-commerce platform',
                    'business_model' => 'b2c',
                    'addresses'      => [
                        'operation'  => [
                            'street1'     => '507, Koramangala 1st block',
                            'street2'     => 'MG Road',
                            'city'        => 'Bengaluru',
                            'state'       => 'Karnataka',
                            'postal_code' => 560034,
                            'country'     => 'IN'
                        ],
                        'registered' => [
                            'street1'     => '507, Koramangala 1st block',
                            'street2'     => 'MG Road',
                            'city'        => 'Bengaluru',
                            'state'       => 'Karnataka',
                            'postal_code' => 560034,
                            'country'     => 'IN'
                        ]
                    ],
                ],
                'brand'                         => [
                    'color' => '000000',
                ],
                'legal_info' => [
                    'pan' => 'AAACL1234C',
                ],
                'contact_info'                  => [
                    'chargeback' => [
                        'email'      => 'cb@acme.org',
                        'phone'      => '8951496311',
                        'policy_url' => 'https://www.google.com'
                    ],
                    'refund'     => [
                        'email'      => 'cb@acme.org',
                        'phone'      => '8951496311',
                        'policy_url' => 'https://www.google.com'
                    ],
                    'support'    => [
                        'email'      => 'support@acme.org',
                        'phone'      => '8951496311',
                        'policy_url' => 'https://www.google.com'
                    ]
                ],
                'notes'                         => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
            ],
        ],
        'response' => [
            'content' => [
                'type'                          => 'standard',
                'status'                        => 'created',
                'email'                         => 'testcreateaccountaa@razorpay.com',
                'phone'                         => '+919999999999',
                'legal_business_name'           => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name' => 'Acme',
                'business_type'                 => 'individual',
                'contact_name'                  => 'contactname',
                'profile'                       => [
                    'description'    => 'Healthcare E-commerce platform',
                    'business_model' => 'b2c',
                    'addresses'      => [
                        'operation'  => [
                            'street1'     => '507, Koramangala 1st block',
                            'street2'     => 'MG Road',
                            'city'        => 'Bengaluru',
                            'state'       => 'KARNATAKA',
                            'postal_code' => 560034,
                            'country'     => 'IN'
                        ],
                        'registered' => [
                            'street1'     => '507, Koramangala 1st block',
                            'street2'     => 'MG Road',
                            'city'        => 'Bengaluru',
                            'state'       => 'KARNATAKA',
                            'postal_code' => 560034,
                            'country'     => 'IN'
                        ]
                    ],
                ],
                'brand'                         => [
                    'color' => '#000000',
                ],
                'contact_info'                  => [
                    'chargeback' => [
                        'email'      => 'cb@acme.org',
                        'phone'      => '8951496311',
                        'policy_url' => 'https://www.google.com'
                    ],
                    'refund'     => [
                        'email'      => 'cb@acme.org',
                        'phone'      => '8951496311',
                        'policy_url' => 'https://www.google.com'
                    ],
                    'support'    => [
                        'email'      => 'support@acme.org',
                        'phone'      => '8951496311',
                        'policy_url' => 'https://www.google.com'
                    ]
                ],
                'notes'                         => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
            ],
        ],
    ],

    'productConfigCreateForNoDocWithTnc' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products',
            'method'  => 'POST',
            'content' => [
                'product_name' => 'payment_links',
                'tnc_accepted' => true,
                'ip'           => '223.233.71.18'
            ],
        ],
        'response' => [
            'content' => [
                'requirements'         => [
                    [
                        'field_reference' => 'otp.contact_mobile',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'otp.external_reference_number',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'optional',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'otp.otp_submission_timestamp',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'optional',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'otp.otp_verification_timestamp',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'optional',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'settlements.beneficiary_name',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'settlements.account_number',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'settlements.ifsc_code',
                        'resolution_url'  => '/accounts/{accountId}/products/{merchantProductConfigId}',
                        'status'          => 'required',
                        'reason_code'     => 'field_missing'
                    ],
                    [
                        'field_reference' => 'individual_proof_of_address',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders/{stakeholderId}/documents',
                        'status'          => 'optional',
                        'reason_code'     => 'document_missing'
                    ],
                ],
                'tnc'     => [
                    'accepted'   => true
                ]
            ],
        ],
    ],

    'testUpdatePaymentGatewayConfigForNoDoc' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products/{merchantProductId}',
            'method'  => 'PATCH',
            'content' => [
                'settlements'     => [
                    'account_number'   => '123576432234',
                    'ifsc_code'        => 'HDFC0000317',
                    'beneficiary_name' => 'bank account name'
                ],
                'otp'        => [
                    'contact_mobile'             => '+919999999999',
                    'external_reference_number'  => 'Shk@123',
                    'otp_submission_timestamp'   => '1653847138',
                    'otp_verification_timestamp' => '1653848138'
                ],
            ]
        ],
        'response' => [
            'content' => [
                'activation_status' => 'needs_clarification'
            ],
        ]
    ],
];
