<?php

return [
    'testProxyIntentApplyApplication' => [
        'request'  => [
            'url'     => '/mob/intents/intent00000001/application/apply',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'content' => [
                'merchant_id' => '10000000000000',
                'service'   => 'x',
                'source' => 'x_dash'
            ]
        ],
        'response' => [
            'content' => [
                'intent' => [
                    'id' => 'intent00000001',
                    'merchant_id' => '10000000000000',
                    'service' => 'x',
                    'source' => 'x_signup',
                    'user_id' => '20000000000000',
                    'application' =>
                        [
                            'id' => 'application001',
                        ],
                    'product_bundle' =>
                        [
                            'id' => 'prodBundle0001',
                            'name' => 'ca_cc_product_bundle',
                            'line_items' => [
                                [
                                    'product_bundle_id' => 'prodBundle0001',
                                    'product_name' => 'ca',
                                    'ranking' => '1'
                                ],
                                [
                                    'product_bundle_id' => 'prodBundle0001',
                                    'product_name' => 'cc',
                                    'ranking' => '2'
                                ]
                            ]
                        ]
                    ],
                ]
            ]
        ],

    'testProxyFetchApplication' => [
        'request'  => [
            'url'     => '/mob/applications/application001',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'content' => [
                'merchant_id' => '10000000000000',
                'service'     => 'x',
            ],
        ],
        'response' => [
            'content' => [
                'application' => [
                    'merchant_id' => '10000000000000',
                    'service' => 'x',
                    'source' => 'x_signup',
                    'obs_workflow_id' => 'obsWorkflowId1',
                    'status' => 'created',
                    'kyc_applications' => [
                        [
                            'id' => 'kycApp00000001',
                            'application_id' => 'application001',
                            'merchant_id' => '10000000000000',
                            'kyc_name' => 'ca',
                            'status' => 'created',
                            'domain_status' => 'created',
                            'reference_id' => 'kycAppRefID001',
                            'metadata' => null
                        ],
                        [
                            'id' => 'kycApp00000002',
                            'application_id' => 'application001',
                            'merchant_id' => '10000000000000',
                            'kyc_name' => 'cc',
                            'status' => 'created',
                            'domain_status' => 'created',
                            'reference_id' => 'kycAppRefID002',
                            'metadata' => null
                        ]
                    ]
                ]
            ]
        ],
    ],

    'testProxyFetchMultipleApplications' => [
        'request'  => [
            'url'     => '/mob/applications',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'content' => [
                'merchant_id' => '10000000000000',
                'service'     => 'x',
                'offset' => '0',
                'limit' => '2',
            ],
        ],
        'response' => [
            'content' => [
                'count' => 2,
                'entity' => 'application',
                'items' => [
                    [
                        'id' => 'application002',
                        'merchant_id' => '10000000000000',
                        'service' => 'x',
                        'source' => 'x_signup',
                        'obs_workflow_id' => 'obsWorkflowId2',
                        'status' => 'created',
                        'kyc_applications' => [
                            [
                                'id' => 'kycApp00000003',
                                'application_id' => 'application002',
                                'merchant_id' => '10000000000000',
                                'kyc_name' => 'ca',
                                'status' => 'created',
                                'domain_status' => 'created',
                                'reference_id' => 'kycAppRefID001',
                                'metadata' => null
                            ],
                            [
                                'id' => 'kycApp00000004',
                                'application_id' => 'application002',
                                'merchant_id' => '10000000000000',
                                'kyc_name' => 'cc',
                                'status' => 'created',
                                'domain_status' => 'created',
                                'reference_id' => 'kycAppRefID002',
                                'metadata' => null
                            ]
                        ]
                    ],
                    [
                        'id' => 'application001',
                        'merchant_id' => '10000000000000',
                        'service' => 'x',
                        'source' => 'x_signup',
                        'obs_workflow_id' => 'obsWorkflowId1',
                        'status' => 'created',
                        'kyc_applications' => [
                            [
                                'id' => 'kycApp00000001',
                                'application_id' => 'application001',
                                'merchant_id' => '10000000000000',
                                'kyc_name' => 'ca',
                                'status' => 'created',
                                'domain_status' => 'created',
                                'reference_id' => 'kycAppRefID001',
                                'metadata' => null
                            ],
                            [
                                'id' => 'kycApp00000002',
                                'application_id' => 'application001',
                                'merchant_id' => '10000000000000',
                                'kyc_name' => 'cc',
                                'status' => 'created',
                                'domain_status' => 'created',
                                'reference_id' => 'kycAppRefID002',
                                'metadata' => null
                            ]
                        ]
                    ]
                ]
            ]
        ],
    ],

    'testProxyCreateIntent' => [
        'request'  => [
            'url'     => '/mob/intents',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'content' => [
                'merchant_id' => '10000000000000',
                'user_id' => '20000000000000',
                'source' => 'signup',
                'service' => 'x',
                'product_bundle_name' => 'ca_cc_product_bundle'
            ]
        ],
        'response' => [
            'content' => [
                'intent' => [
                    'merchant_id' => '10000000000000',
                    'service' => 'x',
                    'source' => 'signup',
                    'user_id' => '20000000000000',
                    'product_bundle' => []
                ]
            ]
        ],
    ],

    'testProxyFetchIntent' => [
        'request'  => [
            'url'     => '/mob/intents/intent00000001',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'content' => [
                'merchant_id' => '10000000000000',
                'service'     => 'x',
            ],
        ],
        'response' => [
            'content' => [
                'intent' => [
                    'id' => 'intent00000001',
                    'merchant_id' => '10000000000000',
                    'service' =>'x',
                    'source' => 'x_signup',
                    'user_id' => '20000000000000',
                    'product_bundle' => [
                        'id' => 'prodBundle0001',
                        'name' => 'ca_cc_product_bundle',
                        'line_items' => [
                            [
                                'product_bundle_id' => 'prodBundle0001',
                                'product_name' => 'ca',
                                'ranking' => 1
                            ],
                            [
                                'product_bundle_id' => 'prodBundle0001',
                                'product_name' => 'cc',
                                'ranking' => 2
                            ]
                        ]
                    ]
                ]
            ]
        ],
    ],

    'testProxyFetchMultipleIntents' => [
        'request'  => [
            'url'     => '/mob/intents',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'content' => [
                'merchant_id' => '10000000000000',
                'service'     => 'x',
                'limit' => '2',
                'offset' => '0',
            ],
        ],
        'response' => [
            'content' => [
                'count' => 2,
                'entity' => 'Intent',
                'items' => [
                    [
                        'id' => 'intent00000002',
                        'merchant_id' => '10000000000000',
                        'service' => 'x',
                        'source' => 'x_signup',
                        'user_id' => '20000000000000',
                        'product_bundle' =>
                            [
                                'id' => 'prodBundle0001',
                                'name' => 'ca_cc_product_bundle',
                                'line_items' => [
                                    [
                                        'product_bundle_id' => 'prodBundle0001',
                                        'product_name' => 'ca',
                                        'ranking' => 1,
                                    ],
                                    [
                                        'product_bundle_id' => 'prodBundle0002',
                                        'product_name' => 'cc',
                                        'ranking' => 2,
                                    ]
                                ]
                            ]
                    ],
                    [
                        'id' => 'intent00000001',
                        'merchant_id' => '10000000000000',
                        'service' => 'x',
                        'source' => 'x_signup',
                        'user_id' => '20000000000000',
                        'product_bundle' =>
                            [
                                'id' => 'prodBundle0001',
                                'name' => 'ca_cc_product_bundle',
                                'line_items' => [
                                    [
                                        'product_bundle_id' => 'prodBundle0001',
                                        'product_name' => 'ca',
                                        'ranking' => 1,
                                    ],
                                    [
                                        'product_bundle_id' => 'prodBundle0002',
                                        'product_name' => 'cc',
                                        'ranking' => 2,
                                    ]
                                ]
                            ]
                    ]
                ]
            ]
        ],
    ],

    'testProxyGetWorkflow' => [
        'request'  => [
            'url'     => '/mob/get_workflow/IfyrSDmvEmnA0N',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'id' => 'IfyrSDmvEmnA0N'
            ]
        ]
    ],

    'testProxySaveWorkflow' => [
        'request'  => [
            'url'     => '/mob/save_workflow',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'content' => [
                'id' => 'IfyrSDmvEmnA0N'
            ]
        ],
        'response' => [
            'content' => [
                'id' => 'IfyrSDmvEmnA0N'
            ]
        ]
    ],

    'testAdminIntentApplyApplication' => [
        'request'  => [
            'url'     => '/mob/admin/intents/intent00000001/application/apply',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'content' => [
                'merchant_id' => '10000000000000',
                'service'   => 'x',
                'source' => 'x_dash'
            ]
        ],
        'response' => [
            'content' => [
                'intent' => [
                    'id' => 'intent00000001',
                    'merchant_id' => '10000000000000',
                    'service' => 'x',
                    'source' => 'x_signup',
                    'user_id' => '20000000000000',
                    'application' =>
                        [
                            'id' => 'application001',
                        ],
                    'product_bundle' =>
                        [
                            'id' => 'prodBundle0001',
                            'name' => 'ca_cc_product_bundle',
                            'line_items' => [
                                [
                                    'product_bundle_id' => 'prodBundle0001',
                                    'product_name' => 'ca',
                                    'ranking' => '1'
                                ],
                                [
                                    'product_bundle_id' => 'prodBundle0001',
                                    'product_name' => 'cc',
                                    'ranking' => '2'
                                ]
                            ]
                        ]
                ],
            ]
        ]
    ],

    'testAdminFetchApplication' => [
        'request'  => [
            'url'     => '/mob/admin/applications/application001',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'content' => [
                'merchant_id' => '10000000000000',
                'service'     => 'x',
            ],
        ],
        'response' => [
            'content' => [
                'application' => [
                    'merchant_id' => '10000000000000',
                    'service' => 'x',
                    'source' => 'x_signup',
                    'obs_workflow_id' => 'obsWorkflowId1',
                    'status' => 'created',
                    'kyc_applications' => [
                        [
                            'id' => 'kycApp00000001',
                            'application_id' => 'application001',
                            'merchant_id' => '10000000000000',
                            'kyc_name' => 'ca',
                            'status' => 'created',
                            'domain_status' => 'created',
                            'reference_id' => 'kycAppRefID001',
                            'metadata' => null
                        ],
                        [
                            'id' => 'kycApp00000002',
                            'application_id' => 'application001',
                            'merchant_id' => '10000000000000',
                            'kyc_name' => 'cc',
                            'status' => 'created',
                            'domain_status' => 'created',
                            'reference_id' => 'kycAppRefID002',
                            'metadata' => null
                        ]
                    ]
                ]
            ]
        ],
    ],

    'testAdminFetchMultipleApplications' => [
        'request'  => [
            'url'     => '/mob/admin/applications',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'content' => [
                'merchant_id' => '10000000000000',
                'service'     => 'x',
                'offset' => '0',
                'limit' => '2',
            ],
        ],
        'response' => [
            'content' => [
                'count' => 2,
                'entity' => 'application',
                'items' => [
                    [
                        'id' => 'application002',
                        'merchant_id' => '10000000000000',
                        'service' => 'x',
                        'source' => 'x_signup',
                        'obs_workflow_id' => 'obsWorkflowId2',
                        'status' => 'created',
                        'kyc_applications' => [
                            [
                                'id' => 'kycApp00000003',
                                'application_id' => 'application002',
                                'merchant_id' => '10000000000000',
                                'kyc_name' => 'ca',
                                'status' => 'created',
                                'domain_status' => 'created',
                                'reference_id' => 'kycAppRefID001',
                                'metadata' => null
                            ],
                            [
                                'id' => 'kycApp00000004',
                                'application_id' => 'application002',
                                'merchant_id' => '10000000000000',
                                'kyc_name' => 'cc',
                                'status' => 'created',
                                'domain_status' => 'created',
                                'reference_id' => 'kycAppRefID002',
                                'metadata' => null
                            ]
                        ]
                    ],
                    [
                        'id' => 'application001',
                        'merchant_id' => '10000000000000',
                        'service' => 'x',
                        'source' => 'x_signup',
                        'obs_workflow_id' => 'obsWorkflowId1',
                        'status' => 'created',
                        'kyc_applications' => [
                            [
                                'id' => 'kycApp00000001',
                                'application_id' => 'application001',
                                'merchant_id' => '10000000000000',
                                'kyc_name' => 'ca',
                                'status' => 'created',
                                'domain_status' => 'created',
                                'reference_id' => 'kycAppRefID001',
                                'metadata' => null
                            ],
                            [
                                'id' => 'kycApp00000002',
                                'application_id' => 'application001',
                                'merchant_id' => '10000000000000',
                                'kyc_name' => 'cc',
                                'status' => 'created',
                                'domain_status' => 'created',
                                'reference_id' => 'kycAppRefID002',
                                'metadata' => null
                            ]
                        ]
                    ]
                ]
            ]
        ],
    ],

    'testAdminCreateIntentForOneCa' => [
        'request'  => [
            'url'     => '/mob/admin_oneca/intents',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'content' => [
                'merchant_id' => '10000000000000',
                'user_id' => '20000000000000',
                'source' => 'signup',
                'service' => 'x',
                'product_bundle_name' => 'ca_cc_product_bundle'
            ]
        ],
        'response' => [
            'content' => [
                'intent' => [
                    'merchant_id' => '10000000000000',
                    'service' => 'x',
                    'source' => 'signup',
                    'user_id' => '20000000000000',
                    'product_bundle' => []
                ]
            ]
        ],
    ],

    'testAdminCreateIntent' => [
        'request'  => [
            'url'     => '/mob/admin/intents',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'content' => [
                'merchant_id' => '10000000000000',
                'user_id' => '20000000000000',
                'source' => 'signup',
                'service' => 'x',
                'product_bundle_name' => 'ca_cc_product_bundle'
            ]
        ],
        'response' => [
            'content' => [
                'intent' => [
                    'merchant_id' => '10000000000000',
                    'service' => 'x',
                    'source' => 'signup',
                    'user_id' => '20000000000000',
                    'product_bundle' => []
                ]
            ]
        ],
    ],

    'testAdminFetchIntent' => [
        'request'  => [
            'url'     => '/mob/admin/intents/intent00000001',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'content' => [
                'merchant_id' => '10000000000000',
                'service'     => 'x',
            ],
        ],
        'response' => [
            'content' => [
                'intent' => [
                    'id' => 'intent00000001',
                    'merchant_id' => '10000000000000',
                    'service' =>'x',
                    'source' => 'x_signup',
                    'user_id' => '20000000000000',
                    'product_bundle' => [
                        'id' => 'prodBundle0001',
                        'name' => 'ca_cc_product_bundle',
                        'line_items' => [
                            [
                                'product_bundle_id' => 'prodBundle0001',
                                'product_name' => 'ca',
                                'ranking' => 1
                            ],
                            [
                                'product_bundle_id' => 'prodBundle0001',
                                'product_name' => 'cc',
                                'ranking' => 2
                            ]
                        ]
                    ]
                ]
            ]
        ],
    ],

    'testAdminFetchMultipleIntents' => [
        'request'  => [
            'url'     => '/mob/admin/intents',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'content' => [
                'merchant_id' => '10000000000000',
                'service'     => 'x',
                'limit' => '2',
                'offset' => '0',
            ],
        ],
        'response' => [
            'content' => [
                'count' => 2,
                'entity' => 'Intent',
                'items' => [
                    [
                        'id' => 'intent00000002',
                        'merchant_id' => '10000000000000',
                        'service' => 'x',
                        'source' => 'x_signup',
                        'user_id' => '20000000000000',
                        'product_bundle' =>
                            [
                                'id' => 'prodBundle0001',
                                'name' => 'ca_cc_product_bundle',
                                'line_items' => [
                                    [
                                        'product_bundle_id' => 'prodBundle0001',
                                        'product_name' => 'ca',
                                        'ranking' => 1,
                                    ],
                                    [
                                        'product_bundle_id' => 'prodBundle0002',
                                        'product_name' => 'cc',
                                        'ranking' => 2,
                                    ]
                                ]
                            ]
                    ],
                    [
                        'id' => 'intent00000001',
                        'merchant_id' => '10000000000000',
                        'service' => 'x',
                        'source' => 'x_signup',
                        'user_id' => '20000000000000',
                        'product_bundle' =>
                            [
                                'id' => 'prodBundle0001',
                                'name' => 'ca_cc_product_bundle',
                                'line_items' => [
                                    [
                                        'product_bundle_id' => 'prodBundle0001',
                                        'product_name' => 'ca',
                                        'ranking' => 1,
                                    ],
                                    [
                                        'product_bundle_id' => 'prodBundle0002',
                                        'product_name' => 'cc',
                                        'ranking' => 2,
                                    ]
                                ]
                            ]
                    ]
                ]
            ]
        ],
    ],

    'testMobMigration' => [
        'request'  => [
            'url'     => '/mob/migration',
            'method'  => 'POST',
            'content' => [
                'mids' => ["10000000000000"],
            ],
        ],
        'response' => [
            'content' => [
                "10000000000000",
            ]
        ]
    ],

    'testMobToBasRoutes' => [
        'request'  => [
            'url'     => '/mob/bas/merchant/banking_application/business/10000000000000/applications/10000000000000',
            'method'  => 'GET',
            'server' => [
                'HTTP_X_RAZORPAY_ACCOUNT' => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [
                'data' => [
                    'id' => '10000000000000',
                    'application_specific_fields' => [
                        'isBusinessGovtBodyOrLiasedOnUnrecognisedStockOrInternationalOrg' => 'N',
                        'isIndianFinancialInstitution'                                    => 'Y',
                        'isOwnerNotIndianCitizen'                                         => 'N',
                        'isTaxResidentOutsideIndia'                                       => 'Y',
                        'role_in_business'                                                => 'ACCOUNTANT',
                        'business_document_mapping'   => [
                            'entityProof1' => 'AADHAR',
                            'entityProof2' => 'PANCARD'
                        ],
                        'persons_document_mapping' => [
                            '20000000000000' => [
                                'addressProof' => 'AADHAAR',
                                'idProof' => 'PANCARD'
                            ],
                            '50000000000000' => [
                                'addressProof' => 'AADHAAR',
                                'idProof' => 'PANCARD'
                            ]
                        ]
                    ],
                    'signatories' => [
                        0 => [
                            'person_id'      => '20000000000000',
                            'signatory_type' => 'AUTHORIZED_SIGNATORY',
                        ],
                    ],
                ],
            ]
        ]
    ],

    'testIntentCreateViaLms' => [
        'request'  => [
            'url'     => '/mob/lms/intents/intent00000001/application/apply',
            'method'  => 'POST',
            'content' => [
                'merchant_id' => '10000000000000',
                'service'   => 'x',
                'source' => 'x_dash'
            ]
        ],
        'response' => [
            'content' => []
        ]
    ],
];
