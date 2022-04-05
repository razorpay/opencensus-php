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
                'tos_acceptance' => [
                    'date'   => '1561110415',
                    'ip' => '201.189.12.23',
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4]',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'type'                => 'standard',
                'status'              => 'created',
                'email'               => 'testcreateaccountaa@razorpay.com',
                'phone'               => '9999999999',
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
                'notes'               => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
                'tos_acceptance'      => [
                    'date'   => '1561110415',
                    'ip' => '201.189.12.23',
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4]',
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
                'tos_acceptance' => [
                    'date'   => '1561110415',
                    'ip' => '201.189.12.23',
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4]',
                ],
            ],
        ],
        'response' => [
            'status_code' => 400,
            'content' => [
                'error' => [
                    'code' => 'BAD_REQUEST_ERROR',
                    'description' => 'The business registered city may only contain alphabets and spaces.',
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
                'tos_acceptance' => [
                    'date'   => '1561110415',
                    'ip' => '201.189.12.23',
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4]',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'type'                => 'standard',
                'status'              => 'created',
                'email'               => 'testcreateaccountaa@razorpay.com',
                'phone'               => '9999999999',
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
                'notes'               => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
                'tos_acceptance'      => [
                    'date'   => '1561110415',
                    'ip' => '201.189.12.23',
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4]',
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
                'tos_acceptance' => [
                    'date'   => '1561110415',
                    'ip' => '201.189.12.23',
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4]',
                ],
                'no_doc_onboarding' => true,
            ],
        ],
        'response' => [
            'content' => [
                'type'                => 'standard',
                'status'              => 'created',
                'email'               => 'testcreateaccountaa@razorpay.com',
                'phone'               => '9999999999',
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
                'notes'               => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
                'tos_acceptance'      => [
                    'date'   => '1561110415',
                    'ip' => '201.189.12.23',
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4]',
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
                'tos_acceptance' => [
                    'date'   => '1561110415',
                    'ip' => '201.189.12.23',
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4]',
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
                'phone'               => '9999999999',
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
                'tos_acceptance' => [
                    'date'   => '1661110415',
                    'ip' => '202.189.12.23',
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4]',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'type'                => 'standard',
                'status'              => 'created',
                'email'               => 'testcreateaccountaa@razorpay.com',
                'phone'               => '9999999999',
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
                'tos_acceptance' => [
                    'date'   => '1661110415',
                    'ip' => '202.189.12.23',
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4]',
                ],
            ],
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
                'phone'               => '9999999999',
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
                'phone'               => '9999999999',
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
                'tos_acceptance' => [
                    'date'   => '1561110415',
                    'ip' => '201.189.12.23',
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4]',
                ],
                'no_doc_onboarding' => true,
            ],
        ],
        'response' => [
            'content' => [
                'type'                => 'standard',
                'status'              => 'created',
                'email'               => 'testcreateaccountaa2@razorpay.com',
                'phone'               => '9999999999',
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
                'notes'               => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
                'tos_acceptance'      => [
                    'date'   => '1561110415',
                    'ip' => '201.189.12.23',
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4]',
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
                'tos_acceptance' => [
                    'date'   => '1561110415',
                    'ip' => '201.189.12.23',
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4]',
                ]
            ],
        ],
        'response' => [
            'content' => [
                'type'                => 'standard',
                'status'              => 'created',
                'email'               => 'testcreateaccountaa2@razorpay.com',
                'phone'               => '9999999999',
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
                'notes'               => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
                'tos_acceptance'      => [
                    'date'   => '1561110415',
                    'ip' => '201.189.12.23',
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4]',
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
                'tos_acceptance' => [
                    'date'   => '1561110415',
                    'ip' => '201.189.12.23',
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4]',
                ]
            ],
        ],
        'response' => [
            'content' => [
                'type'                => 'standard',
                'status'              => 'created',
                'email'               => 'testcreateaccountaa2@razorpay.com',
                'phone'               => '9999999999',
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
                'notes'               => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
                'tos_acceptance'      => [
                    'date'   => '1561110415',
                    'ip' => '201.189.12.23',
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4]',
                ],
            ],
        ],
    ],
];
