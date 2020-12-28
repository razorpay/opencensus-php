<?php

return [

    // completely filled request
    'testCreateAccountV2ForCompletelyFilledRequest' => [
        'request'  => [
            'url'     => '/v2/accounts',
            'method'  => 'POST',
            'content' => [
                'email'           => 'testcreateaccountaa@razorpay.com',
                'phone'           => '9999999999',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'doing_business_as'   => 'Acme',
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
                'status'              => 'active',
                'email'               => 'testcreateaccountaa@razorpay.com',
                'phone'               => '9999999999',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'doing_business_as'   => 'Acme',
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

    'testCreateAccountV2ForMandatoryFilledRequest' => [
        'request'  => [
            'url'     => '/v2/accounts',
            'method'  => 'POST',
            'content' => [
                'email'               => 'testcreateaccountaa@razorpay.com',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'business_type'       => 'partnership',
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
                'status'              => 'active',
                'email'               => 'testcreateaccountaa@razorpay.com',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'doing_business_as'   => 'Acme Corp Pvt Ltd',
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

    'testFetchAccountV2' => [
        'request' => [
            'url'    => '/v2/accounts/{accountId}',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'type'                => 'standard',
                'status'              => 'active',
                'email'               => 'testcreateaccountaa@razorpay.com',
                'phone'               => '9999999999',
                'legal_business_name' => 'Acme Corp Pvt Ltd',
                'doing_business_as'   => 'Acme',
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
];
