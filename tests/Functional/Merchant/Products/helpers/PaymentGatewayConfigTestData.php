<?php

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
                    'color' => 'FFFFFF',
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
                'tos_acceptance'                => [
                    'date'       => '1561110415',
                    'ip'         => '201.189.12.23',
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4]',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'type'                          => 'standard',
                'status'                        => 'created',
                'email'                         => 'testcreateaccountaa@razorpay.com',
                'phone'                         => '9999999999',
                'legal_business_name'           => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name' => 'Acme',
                'business_type'                 => 'individual',
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
                    'color' => '#FFFFFF',
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
                'tos_acceptance'                => [
                    'date'       => '1561110415',
                    'ip'         => '201.189.12.23',
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4]',
                ],
            ],
        ],
    ],

    'testCreateDefaultPaymentGatewayConfig' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}/products',
            'method'  => 'POST',
            'content' => [
                'name' => 'payment_gateway'
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
                        'default_refund_speed' => 'normal'
                    ]
                ]
            ],
        ]
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
                'notifications' => [
                    'sms' => true
                ],
                'settlements'   => [
                    'account_number' => '051610100039258',
                    'ifsc_code'      => 'UBIN0805165'
                ],
                'checkout'      => [
                    'flash_checkout' => false
                ]
            ]
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
                        'sms'      => true,
                        'whatsapp' => false
                    ],
                    'checkout'        => [
                        'default_refund_speed' => 'normal',
                        'flash_checkout'       => false
                    ],
                    'settlements'     => [
                        'account_number' => '051610100039258',
                        'ifsc_code'      => 'UBIN0805165'
                    ],
                ]
            ],
        ]
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
                        'field_reference' => 'individual_proof_of_address',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders/{stakeholderId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing'
                    ],
                    [
                        'field_reference' => 'settlements.name',
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
                    ]
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
                    'account_number' => '123576432234',
                    'ifsc_code' => 'HDFC0000317',
                    'name'           => 'bank account name'
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
                        'default_refund_speed' => 'normal'
                    ],
                    'settlements'     => [
                        'account_number' => '123576432234',
                        'ifsc_code' => 'HDFC0000317',
                        'name'           => 'bank account name'
                    ],
                ],
                'requirements'         => [
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
                    ]
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
                    'account_number' => '123576432234',
                    'ifsc_code' => 'HDFC0000317',
                    'name'           => 'bank account name'
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
                        'default_refund_speed' => 'normal'
                    ],
                    'settlements'     => [
                        'account_number' => '123576432234',
                        'ifsc_code' => 'HDFC0000317',
                        'name'           => 'bank account name'
                    ],
                ],
                'requirements'         => [
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
                    ]
                ]
            ],
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
                        'field_reference' => 'individual_proof_of_address',
                        'resolution_url'  => '/accounts/{accountId}/stakeholders/{stakeholderId}/documents',
                        'status'          => 'required',
                        'reason_code'     => 'document_missing'
                    ]
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
                'requirements' => []
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
                    'color' => 'FFFFFF',
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
                'tos_acceptance'                => [
                    'date'       => '1561110415',
                    'ip'         => '201.189.12.23',
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4]',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'type'                          => 'standard',
                'status'                        => 'created',
                'email'                         => 'testcreateaccountaa@razorpay.com',
                'phone'                         => '9999999999',
                'legal_business_name'           => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name' => 'Acme',
                'business_type'                 => 'public_limited',
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
                    'color' => '#FFFFFF',
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
                'tos_acceptance'                => [
                    'date'       => '1561110415',
                    'ip'         => '201.189.12.23',
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4]',
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
                        'field_reference' => 'settlements.name',
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
                    ]
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
                    ]
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
                    ]
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
];
