<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [

    // completely filled request
    'testCreateAccountV2ForCompletelyFilledRequest' => [
        'request'  => [
            'url'     => '/v2/accounts',
            'method'  => 'POST',
            'content' => [
                'email'           => 'testcreateaccountaa@razorpay.com',
                'phone'           => '9999999999',
                'contact_name'    =>  'contactname',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name'   => 'Acme',
                'business_type'       => 'individual',
                'profile' => [
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
                'legal_info' => [
                  'pan' => 'AAACL1234C',
                  'gst' => '18AABCU9603R1ZM'
                ],
                'brand' => [
                    'color' => 'FFFFFF',
                ],
                'contact_info' => [
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
                'apps' => [
                    'websites' => [
                        'https://www.example.com/'
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
                'notes'           => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
            ],
        ],
        'response' => [
            'content' => [
                'type'                => 'standard',
                'status'              => 'created',
                'email'               => 'testcreateaccountaa@razorpay.com',
                'phone'               => '+919999999999',
                'contact_name'        => 'contactname',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name'   => 'Acme',
                'business_type'       => 'individual',
                'profile'             => [
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
                'legal_info'          => [
                    'pan' => 'AAACL1234C',
                    'gst' => '18AABCU9603R1ZM'
                ],
                'brand'               => [
                    'color' => '#FFFFFF',
                ],
                'contact_info'        => [
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
                'apps'                => [
                    'websites' => [
                        'https://www.example.com/'
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
                'notes'               => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
            ],
        ],
    ],

    'testCreateAccountV2WithInvalidDataRequest' => [
        'request'  => [
            'url'     => '/v2/accounts',
            'method'  => 'POST',
            'content' => [
                'email'           => 'testcreateaccountaa@razorpay.com',
                'phone'           => '9999999999',
                'contact_name'    =>  'contactname',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name'   => 'Acme',
                'business_type'       => 'individual',
                'profile' => [
                    'category'       => 'healthcare',
                    'subcategory'    => 'clinic',
                    'description'    => 'Healthcare E-commerce platform',
                    'business_model' => 'b2c',
                    'addresses'      => [
                        'operation'  => [
                            'street1'     => '507, Koramangala 1st block',
                            'street2'     => 'MG Road',
                            'city'        => 'Bengaluru on 🔥',
                            'state'       => 'Karnataka',
                            'postal_code' => 560034,
                            'country'     => 'IN'
                        ],
                        'registered' => [
                            'street1'     => '507, Koramangala 1st block',
                            'street2'     => 'MG Road',
                            'city'        => 'Bengaluru on 🔥',
                            'state'       => 'Karnataka',
                            'postal_code' => 560034,
                            'country'     => 'IN'
                        ]
                    ],
                ],
                'legal_info' => [
                  'pan' => 'AAACL1234C',
                  'gst' => '18AABCU9603R1ZM'
                ],
                'brand' => [
                    'color' => 'FFFFFF',
                ],
                'contact_info' => [
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
                'apps' => [
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
                'notes'           => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
            ],
        ],
        'response' => [
            'status_code' => 400,
            'content' => [
                'error' => [
                    'code' => 'BAD_REQUEST_ERROR',
                    'description' => 'The business registered city may only contain alphabets, digits and spaces.',
                    'source' => 'business',
                    'step' => 'payment_initiation',
                    'reason' => 'input_validation_failed',
                    'metadata' => [],
                    'field' => 'business_registered_city',
                ],
            ],
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateAccountV2WithInvalidStateName' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'State name entered is incorrect. Please provide correct state name.',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateAccountV2WithEmptyCustomerFacingBusinessName' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The customer facing business name field is required.',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateAccountV2ForCompletelyFilledRegisteredBusinessRequest' => [
        'request'  => [
            'url'     => '/v2/accounts',
            'method'  => 'POST',
            'content' => [
                'email'           => 'testcreateaccountaa@razorpay.com',
                'phone'           => '9999999999',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name'   => 'Acme',
                'business_type'       => 'partnership',
                'profile' => [
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
                'legal_info' => [
                    'pan' => 'AAACL1234C',
                    'gst' => '18AABCU9603R1ZM'
                ],
                'brand' => [
                    'color' => 'FFFFFF',
                ],
                'contact_info' => [
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
                'apps' => [
                    'websites' => [
                        'https://www.example.com/'
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
                'notes'           => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
            ],
        ],
        'response' => [
            'content' => [
                'type'                => 'standard',
                'status'              => 'created',
                'live'                => false,
                'hold_funds'          => false,
                'email'               => 'testcreateaccountaa@razorpay.com',
                'phone'               => '+919999999999',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name'   => 'Acme',
                'business_type'       => 'partnership',
                'profile'             => [
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
                'legal_info'          => [
                    'pan' => 'AAACL1234C',
                    'gst' => '18AABCU9603R1ZM'
                ],
                'brand'               => [
                    'color' => '#FFFFFF',
                ],
                'contact_info'        => [
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
                'apps'                => [
                    'websites' => [
                        'https://www.example.com/'
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
                'notes'               => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
            ],
        ],
    ],

    'testCreateSubmerchantWithNoDocFeature' => [
        'request'  => [
            'url'     => '/v2/accounts',
            'method'  => 'POST',
            'content' => [
                'email'           => 'testcreateaccountaa@razorpay.com',
                'phone'           => '9999999999',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name'   => 'Acme',
                'business_type'       => 'partnership',
                'profile' => [
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
                'legal_info' => [
                    'pan' => 'AAACL1234C',
                    'gst' => '18AABCU9603R1ZM'
                ],
                'brand' => [
                    'color' => 'FFFFFF',
                ],
                'contact_info' => [
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
                'apps' => [
                    'websites' => [
                        'https://www.example.com/'
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
                'notes'           => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
                'no_doc_onboarding' => true,
            ],
        ],
        'response' => [
            'content' => [
                'type'                => 'standard',
                'status'              => 'created',
                'email'               => 'testcreateaccountaa@razorpay.com',
                'phone'               => '+919999999999',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name'   => 'Acme',
                'business_type'       => 'partnership',
                'profile'             => [
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
                'legal_info'          => [
                    'pan' => 'AAACL1234C',
                    'gst' => '18AABCU9603R1ZM'
                ],
                'brand'               => [
                    'color' => '#FFFFFF',
                ],
                'contact_info'        => [
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
                'apps'                => [
                    'websites' => [
                        'https://www.example.com/'
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
                'notes'               => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
            ],
        ],
    ],

    'testCreateSubmerchantWithNoDocFeatureDisabled' => [
        'request'  => [
            'url'     => '/v2/accounts',
            'method'  => 'POST',
            'content' => [
                'email'           => 'testcreateaccountaa@razorpay.com',
                'phone'           => '9999999999',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name'   => 'Acme',
                'business_type'       => 'partnership',
                'profile' => [
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
                'legal_info' => [
                    'pan' => 'AAACL1234C',
                    'gst' => '18AABCU9603R1ZM'
                ],
                'brand' => [
                    'color' => 'FFFFFF',
                ],
                'contact_info' => [
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
                'apps' => [
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
                'notes'           => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
                'no_doc_onboarding' => true,
            ],
        ],
        'response' => [
                'content'     => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'Sub-merchant no-doc onboarding is not enabled for partner'
                    ],
                ],
                'status_code' => 400,
            ],

            'exception' => [
                'class'               => 'RZP\Exception\BadRequestException',
                'internal_error_code' => ErrorCode::BAD_REQUEST_SUBM_NO_DOC_ONBOARDING_NOT_ENABLED_FOR_PARTNER,
            ],
    ],

    'testCreateAccountV2ForMandatoryFilledRequest' => [
        'request'  => [
            'url'     => '/v2/accounts',
            'method'  => 'POST',
            'content' => [
                'email'               => 'testcreateaccountaa@razorpay.com',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'business_type'       => 'partnership',
                'phone'               => '9999999999',
                'profile' => [
                    'category'       => 'healthcare',
                    'subcategory'    => 'clinic',
                    'addresses'      => [
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
            ],
        ],
        'response' => [
            'content' => [
                'type'                => 'standard',
                'status'              => 'created',
                'email'               => 'testcreateaccountaa@razorpay.com',
                'phone'               => '+919999999999',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name'   => 'Acme Corp Pvt Ltd',
                'business_type'       => 'partnership',
                'profile'             => [
                    'category'       => 'healthcare',
                    'subcategory'    => 'clinic',
                    'addresses'      => [
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
            ],
        ],
    ],

    'testEditSubmerchantAccountNoDocFeature' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}',
            'method'  => 'PATCH',
            'content' => [
                'no_doc_onboarding' => true
            ],
        ],
        'response' => [
            'content' => [
                'type'                => 'standard',
                'status'              => 'created',
                'email'               => 'testcreateaccountaa@razorpay.com',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name'   => 'Acme',
                'business_type'       => 'individual',
                'profile'             => [
                    'category'       => 'healthcare',
                    'subcategory'    => 'clinic',
                ],
            ],
        ],
    ],

    'testProvideOptionalFieldForNoDocSubmerchantInNC' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}',
            'method'  => 'PATCH',
            'content' => [
                'customer_facing_business_name'   => 'Acme'
            ],
        ],
        'response' => [
            'content' => [
                'business_type'                   => 'partnership',
                'customer_facing_business_name'   => 'Acme'
            ],
        ],
    ],

    'testProvideNonOptionalFieldForNoDocSubmerchantInNC' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}',
            'method'  => 'PATCH',
            'content' => [
                'legal_info' => [
                    'cin'            => 'U67190TN2014PTC096971'
                ]
            ],
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

    'testProvideNotAllowedFieldForNoDocSubmerchantInAKPstate' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}',
            'method'  => 'PATCH',
            'content' => [
                'legal_info' => [
                    'cin'            => 'U67190TN2014PTC096971'
                ]
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'You can not update this value as it is already verified.',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ONLY_REMAINING_KYC_FIELDS_ARE_ALLOWED,
        ],
    ],

    'testEditAccountV2ProfileAddress' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}',
            'method'  => 'PATCH',
            'content' => [
                'profile' => [
                    'addresses'      => [
                        'registered' => [
                            'street1'     => '507, Malad 1st block',
                            'street2'     => 'SV Road',
                            'city'        => 'Mumbai',
                            'state'       => 'Maharashtra',
                            'postal_code' => 400064,
                            'country'     => 'IN'
                        ]
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'type'                => 'standard',
                'status'              => 'created',
                'live'                => false,
                'hold_funds'          => false,
                'email'               => 'testcreateaccountaa@razorpay.com',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name'   => 'Acme',
                'business_type'       => 'individual',
                'profile'             => [
                    'category'       => 'healthcare',
                    'subcategory'    => 'clinic',
                    'addresses'      => [
                        'registered' => [
                            'street1'     => '507, Malad 1st block',
                            'street2'     => 'SV Road',
                            'city'        => 'Mumbai',
                            'state'       => 'MAHARASHTRA',
                            'postal_code' => 400064,
                            'country'     => 'IN'
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testEditAccountV2OtherDetails' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}',
            'method'  => 'PATCH',
            'content' => [
                'legal_info' => [
                    'pan' => 'AAACL1234C',
                    'gst' => '18AABCU9603R1ZM'
                ],
                'brand' => [
                    'color' => 'FFFAAA',
                ],
                'contact_info' => [
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
                'apps' => [
                    'websites' => [
                        'https://www.yahoo.com/'
                    ],
                    'android'  => [
                        [
                            'url'  => 'https://play.google.com/store/apps/details?id=com.razorpay.payments.app',
                            'name' => 'razorpayx'
                        ]
                    ],
                    'ios'      => [
                        [
                            'url'  => 'https://apps.apple.com/in/app/reddit/id1064216828',
                            'name' => 'reddit'
                        ]
                    ]
                ],
                'notes'           => [
                    'business_details' => 'This is a test business update',
                    'key2'             => 'updateValue2',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'type'                => 'standard',
                'status'              => 'created',
                'email'               => 'testcreateaccountaa@razorpay.com',
                'phone'               => '+919999999999',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name'   => 'Acme',
                'business_type'       => 'individual',
                'profile'             => [
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
                            'postal_code' => '560034',
                            'country'     => 'IN'
                        ],
                        'registered' => [
                            'street1'     => '507, Koramangala 1st block',
                            'street2'     => 'MG Road',
                            'city'        => 'Bengaluru',
                            'state'       => 'KARNATAKA',
                            'postal_code' => '560034',
                            'country'     => 'IN'
                        ]
                    ],
                ],
                'legal_info'          => [
                    'pan' => 'AAACL1234C',
                    'gst' => '18AABCU9603R1ZM'
                ],
                'brand' => [
                    'color' => '#FFFAAA',
                ],
                'contact_info' => [
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
                'apps' => [
                    'websites' => [
                        'https://www.yahoo.com/'
                    ],
                    'android'  => [
                        [
                            'url'  => 'https://play.google.com/store/apps/details?id=com.razorpay.payments.app',
                            'name' => 'razorpayx'
                        ]
                    ],
                    'ios'      => [
                        [
                            'url'  => 'https://apps.apple.com/in/app/reddit/id1064216828',
                            'name' => 'reddit'
                        ]
                    ]
                ],
                'notes'           => [
                    'business_details' => 'This is a test business update',
                    'key2'             => 'updateValue2',
                ],
            ],
        ],
    ],

    'testEditAccountWithEmptyCustomerFacingBusinessName' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}',
            'method'  => 'PATCH',
            'content' => [
                'customer_facing_business_name'  => '',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The customer facing business name field is required.',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFetchAccountV2' => [
        'request' => [
            'url'    => '/v2/accounts/{accountId}',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'type'                => 'standard',
                'status'              => 'created',
                'email'               => 'testcreateaccountaa@razorpay.com',
                'phone'               => '+919999999999',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name'   => 'Acme',
                'business_type'       => 'individual',
                'profile'             => [
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
                            'postal_code' => '560034',
                            'country'     => 'IN'
                        ],
                        'registered' => [
                            'street1'     => '507, Koramangala 1st block',
                            'street2'     => 'MG Road',
                            'city'        => 'Bengaluru',
                            'state'       => 'KARNATAKA',
                            'postal_code' => '560034',
                            'country'     => 'IN'
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testDeleteAccountV2' => [
        'request' => [
            'url'    => '/v2/accounts/{accountId}',
            'method' => 'DELETE',
        ],
        'response' => [
            'content' => [
                'type'                => 'standard',
                'status'              => 'suspended',
                'email'               => 'testcreateaccountaa@razorpay.com',
                'phone'               => '+919999999999',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name'   => 'Acme',
                'business_type'       => 'individual',
                'profile'             => [
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
                            'postal_code' => '560034',
                            'country'     => 'IN'
                        ],
                        'registered' => [
                            'street1'     => '507, Koramangala 1st block',
                            'street2'     => 'MG Road',
                            'city'        => 'Bengaluru',
                            'state'       => 'KARNATAKA',
                            'postal_code' => '560034',
                            'country'     => 'IN'
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testEditAccountV2PostDelete' => [
        'request' => [
            'url'    => '/v2/accounts/{accountId}',
            'method' => 'PATCH',
            'content' => [
                'legal_info' => [
                    'pan' => 'AAACL1234C',
                    'gst' => '18AABCU9603R1ZM'
                ],
                'brand' => [
                    'color' => 'FFFAAA',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The merchant has been suspended. The action is invalid'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_SUSPENDED,
        ],
    ],

    'testGetValidationFieldsForNoDocOnboarding'=>[
        'request'  => [
            'url'     => '/v2/accounts',
            'method'  => 'POST',
            'content' => [
                'email'           => 'testcreateaccountaa2@razorpay.com',
                'phone'           => '9999999999',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name'   => 'Acme',
                'business_type'       => 'not_yet_registered',
                'profile' => [
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
                'legal_info' => [
                    'pan' => 'AAACL1234C',
                    'gst' => '18AABCU9603R1ZM'
                ],
                'brand' => [
                    'color' => 'FFFFFF',
                ],
                'contact_info' => [
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
                'apps' => [
                    'websites' => [
                        'https://www.example.com/'
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
                'notes'           => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
                'no_doc_onboarding' => true,
            ],
        ],
        'response' => [
            'content' => [
                'type'                => 'standard',
                'status'              => 'created',
                'email'               => 'testcreateaccountaa2@razorpay.com',
                'phone'               => '+919999999999',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name'   => 'Acme',
                'business_type'       => 'not_yet_registered',
                'profile'             => [
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
                'legal_info'          => [
                    'pan' => 'AAACL1234C',
                    'gst' => '18AABCU9603R1ZM'
                ],
                'brand'               => [
                    'color' => '#FFFFFF',
                ],
                'contact_info'        => [
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
                'apps'                => [
                    'websites' => [
                        'https://www.example.com/'
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
                'notes'               => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
            ],
        ],
    ],

    'testEditAccountHavingNonEnglishDescription' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}',
            'method'  => 'PATCH',
            'content' => [
                "contact_name" => "Ларавель лучше",
                'profile'      => [
                    'addresses' => [
                        'registered' => [
                            'street1'     => '507, Malad 1st block',
                            'street2'     => 'SV Road',
                            'city'        => 'Mumbai',
                            'state'       => 'Maharashtra',
                            'postal_code' => 400064,
                            'country'     => 'IN'
                        ]
                    ],
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
                'business_type'                 => 'individual',
                "contact_name"                  => "Ларавель лучше",
                'profile'                       => [
                    'category'    => 'healthcare',
                    'subcategory' => 'clinic',
                    'addresses'   => [
                        'registered' => [
                            'street1'     => '507, Malad 1st block',
                            'street2'     => 'SV Road',
                            'city'        => 'Mumbai',
                            'state'       => 'MAHARASHTRA',
                            'postal_code' => 400064,
                            'country'     => 'IN'
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testEditAccountHavingEmojiInContactName' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}',
            'method'  => 'PATCH',
            'content' => [
                "contact_name" => "😀 Shivam Kumar",
                'profile'      => [
                    'addresses' => [
                        'registered' => [
                            'street1'     => '507, Malad 1st block',
                            'street2'     => 'SV Road',
                            'city'        => 'Mumbai',
                            'state'       => 'Maharashtra',
                            'postal_code' => 400064,
                            'country'     => 'IN'
                        ]
                    ],
                ],
            ],
        ],
        'response' => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'The contact name format is invalid.',
                    'source'      => 'business',
                    'step'        => 'payment_initiation',
                    'reason'      => 'input_validation_failed',
                    'metadata'    => [],
                    'field'       => 'contact_name',
                ],
            ],
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testSetMaxPaymentAmountForUnregisteredSubMerchant'=>[
        'request'  => [
            'url'     => '/v2/accounts',
            'method'  => 'POST',
            'content' => [
                'email'           => 'testcreateaccountaa2@razorpay.com',
                'phone'           => '9999999999',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name'   => 'Acme',
                'business_type'       => 'not_yet_registered',
                'profile' => [
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
                'legal_info' => [
                    'pan' => 'AAACL1234C',
                    'gst' => '18AABCU9603R1ZM'
                ],
                'brand' => [
                    'color' => 'FFFFFF',
                ],
                'contact_info' => [
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
                'apps' => [
                    'websites' => [
                        'https://www.example.com/'
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
                'notes'           => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
            ],
        ],
        'response' => [
            'content' => [
                'type'                => 'standard',
                'status'              => 'created',
                'email'               => 'testcreateaccountaa2@razorpay.com',
                'phone'               => '+919999999999',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name'   => 'Acme',
                'business_type'       => 'not_yet_registered',
                'profile'             => [
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
                'legal_info'          => [
                    'pan' => 'AAACL1234C',
                    'gst' => '18AABCU9603R1ZM'
                ],
                'brand'               => [
                    'color' => '#FFFFFF',
                ],
                'contact_info'        => [
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
                'apps'                => [
                    'websites' => [
                        'https://www.example.com/'
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
                'notes'               => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
            ],
        ],
    ],

    'testSetMaxPaymentAmountDefaultForRegisteredSubMerchant'=>[
        'request'  => [
            'url'     => '/v2/accounts',
            'method'  => 'POST',
            'content' => [
                'email'           => 'testcreateaccountaa2@razorpay.com',
                'phone'           => '9999999999',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name'   => 'Acme',
                'business_type'       => 'partnership',
                'profile' => [
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
                'legal_info' => [
                    'pan' => 'AAACL1234C',
                    'gst' => '18AABCU9603R1ZM'
                ],
                'brand' => [
                    'color' => 'FFFFFF',
                ],
                'contact_info' => [
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
                'apps' => [
                    'websites' => [
                        'https://www.example.com/'
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
                'notes'           => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
            ],
        ],
        'response' => [
            'content' => [
                'type'                => 'standard',
                'status'              => 'created',
                'email'               => 'testcreateaccountaa2@razorpay.com',
                'phone'               => '+919999999999',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name'   => 'Acme',
                'business_type'       => 'partnership',
                'profile'             => [
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
                'legal_info'          => [
                    'pan' => 'AAACL1234C',
                    'gst' => '18AABCU9603R1ZM'
                ],
                'brand'               => [
                    'color' => '#FFFFFF',
                ],
                'contact_info'        => [
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
                'apps'                => [
                    'websites' => [
                        'https://www.example.com/'
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
                'notes'               => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
            ],
        ],
    ],

    'testCreateAccountV2WithInvalidContactName' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The contact name may not be greater than 255 characters.',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditAccountWithInvalidContactName' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}',
            'method'  => 'PATCH',
            'content' => [
                'contact_name'   => 'contactnamecontactnamecontactnamecontactnamecontactnamecontactnamecontactnamecontact
                                 namecontactnamecontactnamecontactnamecontactnamecontactnamecontactnamecontactnamecontact
                                 namecontactnamecontactnamecontactnamecontactnamecontactnamecontactnamecontactnamecoc',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The contact name may not be greater than 255 characters.',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateAccountV2WithInvalidPhone' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The phone format is invalid.',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateAccountV2WithPhoneNumbersExceeding'=> [
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Contact number should not be greater than 15 digits, including country code',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_TOO_LONG,
        ],
    ],

    'testEditAccountV2WithInvalidPhone' => [
        'request'  => [
            'url'     => '/v2/accounts/{accountId}',
            'method'  => 'PATCH',
            'content' => [
                'phone'   => '+91.8721302112',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The phone format is invalid.',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateAccountWithExtraKeysInAndroid' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'randomKey is/are not required and should not be sent',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\ExtraFieldsException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testEditAccountWithExtraKeysInIos' => [
        'request' => [
            'method'  => 'PATCH',
            'content' => [
                'apps' => [
                    'android' => [
                        [
                            'randomKey' => 'randomValue'
                        ]
                    ]
                ],
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'randomKey is/are not required and should not be sent',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\ExtraFieldsException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testCreateLinkedAccountWithMarketplaceFeature'  => [
        'request'   => [
            'url'       => '/v2/accounts',
            'method'    => 'POST',
            'content'   => [
                "email"                     => "testaccount@email.com",
                "type"                      => "route",
                "reference_id"              => "route-account-test",
                "phone"                     => "1234567890",
                "legal_business_name"       => "Acme Corp",
                "business_type"             => "private_limited",
                "profile"                   => [
                    "category"      => "healthcare",
                    "subcategory"   => "clinic",
                    "addresses"     => [
                        "registered"    => [
                                "street1"       => "507, Koramangala 1st block",
                                "street2"       => "MG Road",
                                "city"          => "Bengaluru",
                                "state"         => "Karnataka",
                                "postal_code"   => 560034,
                                "country"       => "IN"
                        ]
                    ],
                    "business_model"    => "Healthcare E-commerce platform"
                ],
                "contact_name"              => "Test Account"
            ],
        ],
        'response'  =>  [
            'content'   => [
                 "type"             => "route",
                 "status"           => "created",
                 "email"            => "testaccount@email.com",
                 "profile"          => [
                     "category"     => "healthcare",
                     "subcategory"  => "clinic",
                     "addresses"    => [
                         "registered"   => [
                             "street1"      => "507, Koramangala 1st block",
                             "street2"      => "MG Road",
                             "city"         => "Bengaluru",
                             "state"        => "KARNATAKA",
                             "postal_code"  => 560034,
                             "country"      => "IN"
                         ]
                     ],
                     "business_model" => "Healthcare E-commerce platform"
                 ],
                 "phone"                         => "+911234567890",
                 "contact_name"                  => "Test Account",
                 "reference_id"                  => "route-account-test",
                 "business_type"                 => "private_limited",
                 "legal_business_name"           => "Acme Corp",
                 "customer_facing_business_name" => "Acme Corp"
            ],
        ],
    ],

    'testCreateLinkedAccountWithOutMarketplaceFeature'  => [
        'request'   => [
            'url'       => '/v2/accounts',
            'method'    => 'POST',
            'content'   => [
                "email"             => "testaccount@email.com",
                "type"              => "route",
                "reference_id"      => "route-account-test",
                "phone"             => "1234567890",
                "legal_business_name" => "Acme Corp",
                "business_type"     => "private_limited",
                "profile"           => [
                    "category"              => "healthcare",
                    "subcategory"           => "clinic",
                    "addresses"             => [
                        "registered"            => [
                            "street1"               => "507, Koramangala 1st block",
                            "street2"               => "MG Road",
                            "city"                  => "Bengaluru",
                            "state"                 => "Karnataka",
                            "postal_code"           => 560034,
                            "country"               => "IN"
                        ]
                    ],
                    "business_model" => "Healthcare E-commerce platform"
                ],
                "contact_name" => "Test Account"
            ],
        ],
        'response'  =>  [
            'content'   => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Route feature not enabled for the merchant'
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' =>  [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR_NOT_MARKETPLACE_MERCHANT,
        ],
    ],

    'testAccountStatusWhenMerchantActivationStatusIsActivatedWhenExpIsEnabled' => [
        'request' => [
            'url'    => '/v2/accounts/{accountId}',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'type'                => 'standard',
                'status'              => 'activated',
                'activated_at'        => 1678107805,
                'live'                => true,
                'hold_funds'          => false,
                'email'               => 'testcreateaccountaa@razorpay.com',
                'phone'               => '+919999999999',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name'   => 'Acme',
                'business_type'       => 'individual',
                'profile'             => [
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
                            'postal_code' => '560034',
                            'country'     => 'IN'
                        ],
                        'registered' => [
                            'street1'     => '507, Koramangala 1st block',
                            'street2'     => 'MG Road',
                            'city'        => 'Bengaluru',
                            'state'       => 'KARNATAKA',
                            'postal_code' => '560034',
                            'country'     => 'IN'
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testAccountStatusWhenMerchantActivationStatusIsActivatedWhenExpIsNotEnabled' => [
        'request' => [
            'url'    => '/v2/accounts/{accountId}',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testDeleteAccountV2WhenNewPaymentAcceptanceFieldsExpIsEnabled' => [
        'request' => [
            'url'    => '/v2/accounts/{accountId}',
            'method' => 'DELETE',
        ],
        'response' => [
            'content' => [
                'type'                => 'standard',
                'status'              => 'suspended',
                'live'                => false,
                'hold_funds'          => true,
                'email'               => 'testcreateaccountaa@razorpay.com',
                'phone'               => '+919999999999',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'customer_facing_business_name'   => 'Acme',
                'business_type'       => 'individual',
                'profile'             => [
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
                            'postal_code' => '560034',
                            'country'     => 'IN'
                        ],
                        'registered' => [
                            'street1'     => '507, Koramangala 1st block',
                            'street2'     => 'MG Road',
                            'city'        => 'Bengaluru',
                            'state'       => 'KARNATAKA',
                            'postal_code' => '560034',
                            'country'     => 'IN'
                        ]
                    ],
                ],
            ],
        ],
    ]
];
