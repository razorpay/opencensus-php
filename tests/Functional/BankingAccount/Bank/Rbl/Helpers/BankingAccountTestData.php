<?php

use RZP\Error\ErrorCode;
use RZP\Models\BankingAccount;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\Fixtures\Entity\Org;

return [
    'testCreateBankingAccount' => [
        'request'  => [
            'url'     => '/banking_accounts',
            'method'  => 'POST',
            'content' => [
                'channel' => 'rbl',
                'pincode' => '560034',
            ],
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                'status'      => 'created'
            ],
        ],
    ],

    'testCreateBankingAccountFromSalesforce' => [
        'request'  => [
            'url'     => '/salesforce/banking_accounts/rbl',
            'method'  => 'POST',
            'content' => [
                'channel' => 'rbl',
                'pincode' => '560034',
            ],
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                'status'      => 'created'
            ],
        ],
    ],

    'testSalesforceOpportunityDetails' => [
        'request'  => [
            'url'     => '/merchant/10000000000001/salesforce_opportunity_detail',
            'method'  => 'GET',
            'content' => [
                'opportunity' => ['current_account']
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED,
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testGetBankingAccountFromSalesforce' => [
        'request'  => [
            'url'     => '/salesforce/banking_accounts',
            'method'  => 'GET',
            'content' => [
                'channel' => 'rbl',
                'pincode' => '560034',
            ],
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                'status'      => 'created'
            ],
        ],
    ],

    'testBankLmsEndToEndForGetBranchList' => [
        'request'  => [
            'url'     => '/banking_accounts/rbl/lms/bank_branches',
            'method'  => 'GET',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                  //  'count' => 495, checking this value in the test itself
                    'data' => [],
            ],
        ],
    ],

    'testBankLmsEndToEndForGetRmList' => [
        'request'  => [
            'url'     => '/banking_accounts/rbl/lms/bank_pocs',
            'method'  => 'GET',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                //  'count' => , checking this value in the test itself
                'data' => [],
            ],
        ],
    ],

    'testCreateBankingAccountAdmin' => [
        'request'  => [
            'url'     => '/banking_accounts_admin',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [
                'channel' => 'rbl',
                'pincode' => '560034',
            ],
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                'status'      => 'created'
            ],
        ],
    ],

    'testCreateBankingAccountAdminWithClarityContext' => [
        'request'  => [
            'url'     => '/banking_accounts_admin',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [
                'channel'           => 'rbl',
                'pincode'           => '560034',
                'clarity_context'   => '1'
            ],
        ],
        'response' => [
            'content' => [
                'channel'           => 'rbl',
                'status'            => 'created',
            ],
        ],
    ],

    'testCreateBankingAccountForNonRzpOrgMerchantFromDashboard' => [
        'request'  => [
            'url'     => '/banking_accounts_dashboard',
            'method'  => 'POST',
            'content' => [
                'channel' => 'rbl',
                'pincode' => '560034',
                'activation_detail' => [
                    'business_category' => 'partnership',
                    'sales_team' => 'self_serve'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                'status'      => 'created'
            ],
        ],
    ],

    'testCreateBankingAccountWithRestrictionExcludedForLMS' => [
        'request'  => [
            'url'     => '/banking_accounts_admin',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [
                'channel' => 'rbl',
                'pincode' => '560034',
            ],
        ],
        'response' => [
            'content' => [
                'channel'         => 'rbl',
                'status'          => 'created',
                'pincode'         => '560034',
                'account_type'    => 'current'
            ],
        ],
    ],

    'testCreateBankingAccountWithActivationDetail' => [
        'request'  => [
            'url'     => '/banking_accounts_admin',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [
                'channel' => 'rbl',
                'pincode' => '560034',
                'activation_detail' => [
                    'merchant_poc_name' => 'Sample Name',
                    'merchant_poc_designation' => 'Financial Consultant',
                    'merchant_poc_email' => 'sample@sample.com',
                    'merchant_poc_phone_number' => '9876556789',
                    'merchant_documents_address' => 'x, y, z',
                    'initial_cheque_value' => 100,
                    'account_type' => 'insignia',
                    'merchant_city' => 'Bangalore',
                    'comment' => 'abc',
                    'is_documents_walkthrough_complete' => true,
                    'merchant_region' => 'South',
                    'expected_monthly_gmv' => 10000,
                    'average_monthly_balance' => 0,
                    'business_category' => 'partnership',
                    'sales_team' => 'sme',
                    'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN,
                    'sales_poc_phone_number' => '1234554321'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                'status'      => 'created'
            ],
        ],
    ],

    'testCreateBankingAccountWithAdditionalDetails' => [
        'request'  => [
            'url'     => '/banking_accounts_admin',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [
                'channel' => 'rbl',
                'pincode' => '560034',
                'activation_detail' => [
                    'merchant_poc_name' => 'Sample Name',
                    'merchant_poc_designation' => 'Financial Consultant',
                    'merchant_poc_email' => 'sample@sample.com',
                    'merchant_poc_phone_number' => '9876556789',
                    'merchant_documents_address' => 'x, y, z',
                    'initial_cheque_value' => 100,
                    'account_type' => 'insignia',
                    'merchant_city' => 'Bangalore',
                    'comment' => 'abc',
                    'is_documents_walkthrough_complete' => true,
                    'merchant_region' => 'South',
                    'expected_monthly_gmv' => 10000,
                    'average_monthly_balance' => 0,
                    'business_category' => 'partnership',
                    'sales_team' => 'sme',
                    'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN,
                    'sales_poc_phone_number' => '1234554321',
                    'additional_details' => json_encode([
                        'green_channel' => true,
                        'entity_proof_documents'    => [
                            [
                                'document_type' => 'gst_certificate',
                                'file_id'       => 'test',
                            ],
                            [
                                'document_type' => 'business_pan',
                                'file_id'       => 'test',
                            ]
                        ],
                    ]),
                ]
            ],
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                'status'      => 'created',
                'banking_account_activation_details' => [
                    'additional_details' => [
                        'green_channel' => true,
                        'entity_proof_documents'    => [
                            [
                                'document_type' => 'gst_certificate',
                                'file_id'       => 'test',
                            ],
                            [
                                'document_type' => 'business_pan',
                                'file_id'       => 'test',
                            ]
                        ],
                    ],
                ]
            ],
        ],
    ],

    'testCreateBankingAccountWithActivationDetailFormDashboard' => [
        'request'  => [
            'url'     => '/banking_accounts_dashboard',
            'method'  => 'POST',
            'server' => [
                'X-Dashboard-User-Id' => '20000000000000',
            ],
            'content' => [
                'channel' => 'rbl',
                'pincode' => '560034',
                'activation_detail' => [
                    'merchant_poc_name' => 'Sample Name',
                    'merchant_poc_designation' => 'Financial Consultant',
                    'merchant_poc_email' => 'sample@sample.com',
                    'merchant_poc_phone_number' => '9876556789',
                    'merchant_documents_address' => 'x, y, z',
                    'initial_cheque_value' => 100,
                    'account_type' => 'insignia',
                    'merchant_city' => 'Bangalore',
                    'business_type' => 'ecommerce',
                    'is_documents_walkthrough_complete' => true,
                    'merchant_region' => 'South',
                    'expected_monthly_gmv' => 10000,
                    'average_monthly_balance' => 0,
                    'business_category' => 'partnership',
                    'sales_team' => 'self_serve',
                ]
            ],
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                'status'      => 'created'
            ],
        ],
    ],

    'testCreateBankingAccountAndSubmitFormMerchantDashboard' => [
        'request'  => [
            'url'     => '/banking_accounts_dashboard',
            'method'  => 'PATCH',
            'server' => [
                'X-Dashboard-User-Id' => '20000000000000',
            ],
            'content' => [
                'activation_detail' => [
                    'merchant_poc_name' => 'Sample Name',
                    'merchant_poc_designation' => 'Financial Consultant',
                    'merchant_poc_email' => 'sample@sample.com',
                    'merchant_poc_phone_number' => '9876556789',
                    'merchant_documents_address' => 'x, y, z',
                    'initial_cheque_value' => 100,
                    'account_type' => 'insignia',
                    'merchant_city' => 'Bangalore',
                    'business_type' => 'ecommerce',
                    'is_documents_walkthrough_complete' => true,
                    'merchant_region' => 'South',
                    'expected_monthly_gmv' => 10000,
                    'average_monthly_balance' => 0,
                    'business_category' => 'partnership',
                    'sales_team' => 'self_serve',
                    'declaration_step' => 1
                ]
            ],
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                'status'      => 'picked'
            ],
        ],
    ],

    'testUpdateBankingAccountFromDashboardServiceabilityExperiment' => [
        'request' => [
            'url' => '/banking_accounts_dashboard',
            'method' => 'PATCH',
            'server' => [
                'X-Dashboard-User-Id' => '20000000000000',
            ],
            'content' => [
                'pincode' => '560038'
            ]
        ],
        'response' => [
            'content' => [
                'pincode' => '560038'
            ]
        ]
    ],

    'testFreshDeskTicketForSelfServe' => [
        'request'  => [
            'url'     => '/banking_accounts_dashboard',
            'method'  => 'PATCH',
            'server' => [
                'X-Dashboard-User-Id' => '20000000000000',
            ],
            'content' => [
                'activation_detail' => [
                    'merchant_poc_name' => 'Sample Name',
                    'merchant_poc_designation' => 'Financial Consultant',
                    'merchant_poc_email' => 'sample@sample.com',
                    'merchant_poc_phone_number' => '9876556789',
                    'merchant_documents_address' => 'x, y, z',
                    'initial_cheque_value' => 100,
                    'account_type' => 'insignia',
                    'merchant_city' => 'Bangalore',
                    'business_type' => 'ecommerce',
                    'is_documents_walkthrough_complete' => true,
                    'merchant_region' => 'South',
                    'expected_monthly_gmv' => 10000,
                    'average_monthly_balance' => 0,
                    'business_category' => 'partnership',
                    'sales_team' => 'self_serve',
                    'declaration_step' => 1
                ]
            ],
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                'status'      => 'picked'
            ],
        ],
    ],

    'testFreshDeskTicketCreationOnBankingAccountUpdate' => [
        'request'  => [
            'url'     => '/banking_accounts_dashboard/bacc_{id}',
            'method'  => 'PATCH',
            'server' => [
                'X-Dashboard-User-Id' => '20000000000000',
            ],
            'content' => [
                'activation_detail' => [
                    'merchant_poc_name' => 'Sample Name',
                    'merchant_poc_designation' => 'Financial Consultant',
                    'merchant_poc_email' => 'sample@sample.com',
                    'merchant_poc_phone_number' => '9876556789',
                    'merchant_documents_address' => 'x, y, z',
                    'initial_cheque_value' => 100,
                    'account_type' => 'insignia',
                    'merchant_city' => 'Bangalore',
                    'business_type' => 'ecommerce',
                    'is_documents_walkthrough_complete' => true,
                    'merchant_region' => 'South',
                    'expected_monthly_gmv' => 10000,
                    'average_monthly_balance' => 0,
                    'business_category' => 'partnership',
                    'sales_team' => 'self_serve',
                ]
            ],
        ],
        'response' => [
            'content' => [
                'channel' => 'rbl',
                'status'  => 'created',
            ],
        ]
    ],

    'testFreshDeskTicketCreationOnActivationDetailUpdate' => [
        'request'  => [
            'url'     => '/banking_accounts_internal/activation/bacc_/details',
            'method'  => 'PATCH',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [
                'declaration_step' => 1,
                'additional_details' => [
                    'sales_pitch_completed' => 1
                ]
            ]
        ],
        'response' => [
            'content' => [
                'declaration_step' => 1,
                'additional_details' => json_encode(["sales_pitch_completed" => 0]),
            ]
        ]
    ],

    'testFreshDeskTicketNewMail' => [
        'request'  => [
            'url'     => '/banking_accounts_dashboard',
            'method'  => 'PATCH',
            'server' => [
                'X-Dashboard-User-Id' => '20000000000000',
            ],
            'content' => [
                'activation_detail' => [
                    'declaration_step' => 1
                ]
            ],
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                'status'      => 'picked'
            ],
        ],
    ],

    'testFreshDeskTicketforSalesAssistedFlow' => [
        'request'  => [
            'url'     => '/banking_accounts_dashboard',
            'method'  => 'PATCH',
            'server' => [
                'X-Dashboard-User-Id' => '20000000000000',
            ],
            'content' => [
                'activation_detail' => [
                    'merchant_poc_name' => 'Sample Name',
                    'merchant_poc_designation' => 'Financial Consultant',
                    'merchant_poc_email' => 'sample@sample.com',
                    'merchant_poc_phone_number' => '9876556789',
                    'merchant_documents_address' => 'x, y, z',
                    'initial_cheque_value' => 100,
                    'account_type' => 'insignia',
                    'merchant_city' => 'Bangalore',
                    'business_type' => 'ecommerce',
                    'is_documents_walkthrough_complete' => true,
                    'merchant_region' => 'South',
                    'expected_monthly_gmv' => 10000,
                    'average_monthly_balance' => 0,
                    'business_category' => 'partnership',
                    'sales_team' => 'self_serve',
                    'declaration_step' => 1
                ]
            ],
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                'status'      => 'picked'
            ],
        ],
    ],

    'testCreateBankingAccountAndSubmitAgain' => [
        'request'  => [
            'url'     => '/banking_accounts_dashboard',
            'method'  => 'PATCH',
            'server' => [
                'X-Dashboard-User-Id' => '20000000000000',
            ],
            'content' => [
                'activation_detail' => [
                    'merchant_poc_name' => 'Sample Name',
                    'merchant_poc_designation' => 'Financial Consultant',
                    'merchant_poc_email' => 'sample@sample.com',
                    'merchant_poc_phone_number' => '9876556789',
                    'merchant_documents_address' => 'x, y, z',
                    'initial_cheque_value' => 100,
                    'account_type' => 'insignia',
                    'merchant_city' => 'Bangalore',
                    'business_type' => 'ecommerce',
                    'is_documents_walkthrough_complete' => true,
                    'merchant_region' => 'South',
                    'expected_monthly_gmv' => 10000,
                    'average_monthly_balance' => 0,
                    'business_category' => 'partnership',
                    'sales_team' => 'self_serve',
                    'declaration_step' => 1
                ]
            ],
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                'status'      => 'picked'
            ],
        ],
    ],

    'testCreateBankingAccountWithUnserviceableBusinessCategoryFormDashboard' => [
        'request'  => [
            'url'     => '/banking_accounts_dashboard',
            'method'  => 'POST',
            'server' => [
                'X-Dashboard-User-Id' => '20000000000000',
            ],
            'content' => [
                'channel' => 'rbl',
                'pincode' => '560034',
                'activation_detail' => [
                    'business_category' => 'llp',
                    'sales_team'        => 'self_serve'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'serviceability' => true,
                'business_type_supported' => false,
                'errorMessage' => null
              ],
          ],
      ],

    'testCreateBankingAccountWithUnserviceablePincodeFormDashboard' => [
        'request'  => [
            'url'     => '/banking_accounts_dashboard',
            'method'  => 'POST',
            'server' => [
                'X-Dashboard-User-Id' => '20000000000000',
            ],
            'content' => [
                'channel' => 'rbl',
                'pincode' => '174103',
                'activation_detail' => [
                    'business_category' => 'llp',
                    'sales_team'        => 'self_serve'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'serviceability' => false,
                'business_type_supported' => false,
                'errorMessage' => 'The city field is required.;No Pincode Match Found!'
            ],
        ],
    ],

    'testCreateBankingAccountWithUnserviceableBusinessCategoryFromAdminDashboard' => [
        'request'  => [
            'url'     => '/banking_accounts_admin_dashboard',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [
                'channel' => 'rbl',
                'pincode' => '560034',
                'activation_detail' => [
                    'business_category' => 'llp',
                    'sales_team'        => 'self_serve'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'serviceability' => true,
                'business_type_supported' => false,
                'errorMessage' => null
            ],
        ],
    ],

    'testCreateBankingAccountWithServiceableBusinessCategoryFromAdminDashboard' => [
        'request'  => [
            'url'     => '/banking_accounts_admin_dashboard',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [
                'channel' => 'rbl',
                'pincode' => '560034',
                'activation_detail' => [
                    'business_category' => 'partnership',
                    'sales_team'        => 'self_serve'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'serviceability' => true,
                'business_type_supported' => true,
                'errorMessage' => null
            ],
        ],
    ],

    'testCheckServiceableByRBL' => [
        'request'  => [
            'url'     => '/banking_accounts/serviceability/rbl/pincode/221002',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                "serviceability" => true,
                "errorMessage" => null
            ],
        ],
    ],

    'testCheckWhitelistPincodeServiceableByIcic' => [
        'request'  => [
            'url'     => '/banking_accounts/serviceability/rbl/pincode/421004',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                "serviceability" => true,
                "errorMessage" => null
            ],
        ],
    ],

    'testCheckServiceableByRBLFromAdminDashboard' => [
        'request'  => [
            'url'     => '/banking_accounts_admin/serviceability/rbl/pincode/221002',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                "serviceability" => true,
                "errorMessage" => null
            ],
        ],
    ],

    'testCreateBankingAccountWithActivationDetailWithSalesTeamAsCapitalSme' => [
        'request'  => [
            'url'     => '/banking_accounts_admin',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [
                'channel' => 'rbl',
                'pincode' => '560034',
                'activation_detail' => [
                    'merchant_poc_name' => 'Sample Name',
                    'merchant_poc_designation' => 'Financial Consultant',
                    'merchant_poc_email' => 'sample@sample.com',
                    'merchant_poc_phone_number' => '9876556789',
                    'merchant_documents_address' => 'x, y, z',
                    'initial_cheque_value' => 100,
                    'account_type' => 'insignia',
                    'merchant_city' => 'Bangalore',
                    'comment' => 'abc',
                    'is_documents_walkthrough_complete' => true,
                    'merchant_region' => 'South',
                    'expected_monthly_gmv' => 10000,
                    'average_monthly_balance' => 0,
                    'business_category' => 'partnership',
                    'sales_team' => 'capital_sme',
                    'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN,
                    'sales_poc_phone_number' => '1234554321'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                'status'      => 'created'
            ],
        ],
    ],

    'testCreateBankingAccountWithActivationDetailWithSalesTeamAsNitPartnerships' => [
        'request'  => [
            'url'     => '/banking_accounts_admin',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [
                'channel' => 'rbl',
                'pincode' => '560034',
                'activation_detail' => [
                    'merchant_poc_name' => 'Test Name',
                    'merchant_poc_designation' => 'Financial Consultant',
                    'merchant_poc_email' => 'sample@sample.com',
                    'merchant_poc_phone_number' => '9876556789',
                    'merchant_documents_address' => 'x, y, z',
                    'initial_cheque_value' => 100,
                    'account_type' => 'insignia',
                    'merchant_city' => 'Bangalore',
                    'comment' => 'abc',
                    'is_documents_walkthrough_complete' => true,
                    'merchant_region' => 'South',
                    'expected_monthly_gmv' => 10000,
                    'average_monthly_balance' => 0,
                    'business_category' => 'partnership',
                    'sales_team' => 'nit_partnerships',
                    'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN,
                    'sales_poc_phone_number' => '1234554321'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                'status'      => 'created'
            ],
        ],
    ],

    'testCreateBankingAccountWithActivationDetailWithBusinessTypeAsOnePersonCompanies' => [
        'request'  => [
            'url'     => '/banking_accounts_admin',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [
                'channel' => 'rbl',
                'pincode' => '560034',
                'activation_detail' => [
                    'merchant_poc_name' => 'Sample Name',
                    'merchant_poc_designation' => 'Financial Consultant',
                    'merchant_poc_email' => 'sample@sample.com',
                    'merchant_poc_phone_number' => '9876556789',
                    'merchant_documents_address' => 'x, y, z',
                    'initial_cheque_value' => 100,
                    'account_type' => 'insignia',
                    'merchant_city' => 'Bangalore',
                    'comment' => 'abc',
                    'is_documents_walkthrough_complete' => true,
                    'merchant_region' => 'South',
                    'expected_monthly_gmv' => 10000,
                    'average_monthly_balance' => 0,
                    'business_category' => 'one_person_company',
                    'sales_team' => 'capital_sme',
                    'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN,
                    'sales_poc_phone_number' => '1234554321'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                'status'      => 'created'
            ],
        ],
    ],

    'testCreateBankingAccountWithActivationDetailFails' => [
        'request'  => [
            'url'     => '/banking_accounts_admin',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [
                'channel' => 'rbl',
                'pincode' => '560034',
                'activation_detail' => [
//                    'merchant_poc_name' => 'Sample Name',
//                    'merchant_poc_designation' => 'Financial Consultant',
                    'merchant_poc_email' => 'sample@sample.com',
                    'merchant_poc_phone_number' => '9876556789',
                    'merchant_documents_address' => 'x, y, z',
                    'initial_cheque_value' => 100,
                    'account_type' => 'insignia',
                    'merchant_city' => 'Bangalore',
                    'comment' => 'abc',
                    'is_documents_walkthrough_complete' => true,
                    'merchant_region' => 'South',
                    'expected_monthly_gmv' => 10000,
                    'average_monthly_balance' => 0,
                    'business_category' => 'partnership',
                    'sales_team' => 'sme',
                    'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN,
                    'sales_poc_phone_number' => '1234554321'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                'status'      => 'created'
            ],
        ],
    ],

    'testCreateBankingAccountWithUnserviceablePincode' => [
        'request'  => [
            'url'     => '/banking_accounts',
            'method'  => 'POST',
            'content' => [
                'channel' => 'rbl',
                'pincode' => '462016',
            ],
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                'status'      => 'created'
            ],
        ],
    ],

    'testCreateBankingAccountWithEmptyPincode' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The pincode field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateBankingAccountWithInvalidBank' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Not a valid channel: TEST',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSuccessRblCoCreatedLeadCreation' => [
        'request'  => [
            'url'     => '/banking_accounts/rbl/lead',
            'method'  => 'POST',
            'content' => [
                'NeoBankingLeadReq' => [
                    'Header' => [
                        'TranID'  => '1634732025132',
                        'Corp_ID' => 'WEIZMANNIM'
                    ],
                    'Body'   => [
                        'LeadID'                 => '550000',
                        'EmailAddress'           => 'Harshada.Mohite1@rblbank.com',
                        'Customer_Name'          => 'HarshadaMohite',
                        'Customer_Mobile_Number' => '9876767676',
                        'Customer_Address'       => 'Mulund',
                        'Customer_PinCode'       => '400080',
                        'Customer_City'          => 'Mulund'
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                "NeoBankingLeadResp" => [
                    'Header' => [
                        'TranID'        => '1634732025132',
                        'Corp_ID'       => 'WEIZMANNIM',
                        'Status'        => 'Success',
                        'StatusMessage' => 'Data Successfully Inserted'
                    ]
                ]

            ],
        ],
    ],

    'testGetRblCoCreatedLeadsAfterCreation' => [
        'request' => [
            'url'     => '/admin/banking_account?count=20&skip=0&application_type=co_created',
            'method'  => 'GET',
            'content' => [
                'expand' => ['merchant','merchant.merchantDetail'],
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                ],
            ],
        ]
    ],

    'testAdminResetPasswordOnSuccessRblCoCreatedLeadCreation' => [
        'request'  => [
            'url'     => '/banking_accounts/rbl/lead',
            'method'  => 'POST',
            'content' => [
                'NeoBankingLeadReq' => [
                    'Header' => [
                        'TranID'  => '1634732025132',
                        'Corp_ID' => 'WEIZMANNIM'
                    ],
                    'Body'   => [
                        'LeadID'                 => '550000',
                        'EmailAddress'           => 'Harshada.Mohite1@rblbank.com',
                        'Customer_Name'          => 'HarshadaMohite',
                        'Customer_Mobile_Number' => '9876767676',
                        'Customer_Address'       => 'Mulund',
                        'Customer_PinCode'       => '400080',
                        'Customer_City'          => 'Mulund'
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                "NeoBankingLeadResp" => [
                    'Header' => [
                        'TranID'        => '1634732025132',
                        'Corp_ID'       => 'WEIZMANNIM',
                        'Status'        => 'Success',
                        'StatusMessage' => 'Data Successfully Inserted'
                    ]
                ]

            ],
        ],
    ],

    'testFailureRblCoCreatedLeadCreation' => [
        'request'  => [
            'url'     => '/banking_accounts/rbl/lead',
            'method'  => 'POST',
            'content' => [
                'NeoBankingLeadReq' => [
                    'Header' => [
                        'TranID'  => '1634732025132',
                        'Corp_ID' => 'WEIZMANNIM'
                    ],
                    'Body'   => [
                        'LeadID'                 => '550000',
                        'EmailAddress'           => 'Harshada.Mohite1@rblbank.com',
                        'Customer_Name'          => '',
                        'Customer_Mobile_Number' => '9876767676',
                        'Customer_Address'       => 'Mulund',
                        'Customer_PinCode'       => '400080',
                        'Customer_City'          => 'Mulund'
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                    'Header' => [
                        'TranID'        => '1634732025132',
                        'Corp_ID'       => 'WEIZMANNIM',
                        'Status'        => 'Fail',
                        'ErrorDesc' => 'A schema validation error has occurred while validating the message tree,6008,1,1,213,cvc-minLength-valid: The length of value \"\" is \"0\" which is not valid with respect to the minLength facet with value \"1\" for type \"#Anonymous\".,/Root/XMLNSC/NeoBankingLeadReq/Body/Customer_Name'
                    ]
            ],
        ],
    ],

    'testDuplicateRblCoCreatedLeadCreation' => [
        'request'  => [
            'url'     => '/banking_accounts/rbl/lead',
            'method'  => 'POST',
            'content' => [
                'NeoBankingLeadReq' => [
                    'Header' => [
                        'TranID'  => '1634732025132',
                        'Corp_ID' => 'WEIZMANNIM'
                    ],
                    'Body'   => [
                        'LeadID'                 => '550000',
                        'EmailAddress'           => 'Harshada.Mohite1@rblbank.com',
                        'Customer_Name'          => 'HarshadaMohite',
                        'Customer_Mobile_Number' => '9876767676',
                        'Customer_Address'       => 'Mulund',
                        'Customer_PinCode'       => '400080',
                        'Customer_City'          => 'Mulund'
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'Header' => [
                    'TranID'    => '1634732025132',
                    'Corp_ID'   => 'WEIZMANNIM',
                    'Status'    => 'Fail',
                    'ErrorDesc' => 'EMAIL_ALREADY_EXIST'
                ],
            ],
        ],
    ],

    'testSuccessLeadCreationAndWebhookForAccountOpening' => [
        'request'  => [
            'url'     => '/banking_accounts/webhooks/account_info/rbl',
            'method'  => 'POST',
            'content' => [
                'RZPAlertNotiReq' => [
                    'Body' => [
                        'Account No.'       => '309002180853',
                        'Customer Name'     => 'INTERNET BANKING CA',
                        'Customer ID'       => 'Customer ID',
                        'Account Open Date' => '22-05-2019',
                        'RZP_Ref No'        => '15597',
                        'IFSC'              => 'RBLN0000090',
                        'Address1'          => 'RAM NAGAR',
                        'Address2'          => 'ADARSHA LANE',
                        'Address3'          => '.',
                        'CITY'              => 'MUMBAI',
                        'STATE'             => 'MAHARASH',
                        'COUNTRY'           => 'INDIA',
                        'PINCODE'           => '123456',
                        'Phone no.'         => '9899807189',
                        'Email Id'          => 'test@gmail.com'
                    ],
                    'Header' => [
                        'TranID' => '12345'
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'RZPAlertNotiRes' => [
                    'Header' => [
                        'TranID' => '12345'
                    ],
                    'Body' => [
                        'Status' => 'Success'
                    ]
                ]
            ],
        ],
    ],

    'testSuccessBankAccountInfoNotification' => [
        'request'  => [
            'url'     => '/banking_accounts/webhooks/account_info/rbl',
            'method'  => 'POST',
            'content' => [
                'RZPAlertNotiReq' => [
                    'Body' => [
                        'Account No.'       => '309002180853',
                        'Customer Name'     => 'INTERNET BANKING CA',
                        'Customer ID'       => 'Customer ID',
                        'Account Open Date' => '22-05-2019',
                        'RZP_Ref No'        => '15597',
                        'IFSC'              => 'HDFC0000090',
                        'Address1'          => 'RAM NAGAR',
                        'Address2'          => 'ADARSHA LANE',
                        'Address3'          => '.',
                        'CITY'              => 'MUMBAI',
                        'STATE'             => 'MAHARASH',
                        'COUNTRY'           => 'INDIA',
                        'PINCODE'           => '123456',
                        'Phone no.'         => '9899807189',
                        'Email Id'          => 'test@gmail.com'
                    ],
                    'Header' => [
                        'TranID' => '12345'
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'RZPAlertNotiRes' => [
                    'Header' => [
                        'TranID' => '12345'
                    ],
                    'Body' => [
                        'Status' => 'Success'
                    ]
                ]
            ],
        ],
    ],

    'testDoubleAccountOpeningWebhooksAllowedAfterManualIntervention' => [
        'request'  => [
            'url'     => '/banking_accounts/webhooks/account_info/rbl',
            'method'  => 'POST',
            'content' => [
                'RZPAlertNotiReq' => [
                    'Body' => [
                        'Account No.'       => '309002180853',
                        'Customer Name'     => 'INTERNET BANKING CA',
                        'Customer ID'       => 'Customer ID',
                        'Account Open Date' => '22-05-2019',
                        'RZP_Ref No'        => '15597',
                        'IFSC'              => 'HDFC0000090',
                        'Address1'          => 'RAM NAGAR',
                        'Address2'          => 'ADARSHA LANE',
                        'Address3'          => '.',
                        'CITY'              => 'MUMBAI',
                        'STATE'             => 'MAHARASH',
                        'COUNTRY'           => 'INDIA',
                        'PINCODE'           => '123456',
                        'Phone no.'         => '9899807189',
                        'Email Id'          => 'test@gmail.com'
                    ],
                    'Header' => [
                        'TranID' => '12345'
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'RZPAlertNotiRes' => [
                    'Header' => [
                        'TranID' => '12345'
                    ],
                    'Body' => [
                        'Status' => 'Success'
                    ]
                ]
            ],
        ],
    ],

    'testValidateAccountOpeningDateInWebhook' => [
        'request'  => [
            'url'     => '/banking_accounts/webhooks/account_info/rbl',
            'method'  => 'POST',
            'content' => [
                'RZPAlertNotiReq' => [
                    'Body' => [
                        'Account No.'       => '309002180853',
                        'Customer Name'     => 'INTERNET BANKING CA',
                        'Customer ID'       => 'Customer ID',
                        'Account Open Date' => '21-11-20',
                        'RZP_Ref No'        => '15597',
                        'IFSC'              => 'HDFC0000090',
                        'Address1'          => 'RAM NAGAR',
                        'Address2'          => 'ADARSHA LANE',
                        'Address3'          => '.',
                        'CITY'              => 'MUMBAI',
                        'STATE'             => 'MAHARASH',
                        'COUNTRY'           => 'INDIA',
                        'PINCODE'           => '123456',
                        'Phone no.'         => '9899807189',
                        'Email Id'          => 'test@gmail.com'
                    ],
                    'Header' => [
                        'TranID' => '1234'
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'RZPAlertNotiRes' => [
                    'Header' => [
                        'TranID' => '1234'
                    ],
                    'Body' => [
                        'Status' => 'Failure'
                    ]
                ]
            ],
        ],
    ],

    'createAccountOpeningSuccessfulWebhook' => [
        'request'  => [
            'url'     => '/banking_accounts/webhooks/account_info/rbl',
            'method'  => 'POST',
            'content' => [
                'RZPAlertNotiReq' => [
                    'Body' => [
                        'Account No.'       => '309002180853',
                        'Customer Name'     => 'INTERNET BANKING CA',
                        'Customer ID'       => 'Customer ID',
                        'Account Open Date' => '21-11-2020',
                        'RZP_Ref No'        => '15597',
                        'IFSC'              => 'HDFC0000090',
                        'Address1'          => 'RAM NAGAR',
                        'Address2'          => 'ADARSHA LANE',
                        'Address3'          => '.',
                        'CITY'              => 'MUMBAI',
                        'STATE'             => 'MAHARASH',
                        'COUNTRY'           => 'INDIA',
                        'PINCODE'           => '123456',
                        'Phone no.'         => '9899807189',
                        'Email Id'          => 'test@gmail.com'
                    ],
                    'Header' => [
                        'TranID' => '1234'
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'RZPAlertNotiRes' => [
                    'Header' => [
                        'TranID' => '1234'
                    ],
                    'Body' => [
                        'Status' => 'Success'
                    ]
                ]
            ],
        ],
    ],

    'testDataAmbiguityInWebhookWithSamePinCodeAndSameBusinessName' => [
        'request'  => [
            'url'     => '/banking_accounts/webhooks/account_info/rbl',
            'method'  => 'POST',
            'content' => [
                'RZPAlertNotiReq' => [
                    'Body' => [
                        'Account No.'       => '309002180853',
                        'Customer Name'     => 'INTERNET BANKING CA',
                        'Customer ID'       => 'Customer ID',
                        'Account Open Date' => '21-11-2020',
                        'RZP_Ref No'        => '15597',
                        'IFSC'              => 'HDFC0000090',
                        'Address1'          => 'RAM NAGAR',
                        'Address2'          => 'ADARSHA LANE',
                        'Address3'          => '.',
                        'CITY'              => 'MUMBAI',
                        'STATE'             => 'MAHARASH',
                        'COUNTRY'           => 'INDIA',
                        'PINCODE'           => '560034',
                        'Phone no.'         => '9899807189',
                        'Email Id'          => 'test@gmail.com'
                    ],
                    'Header' => [
                        'TranID' => '1234'
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'RZPAlertNotiRes' => [
                    'Header' => [
                        'TranID' => '1234'
                    ],
                    'Body' => [
                        'Status' => 'Success'
                    ]
                ]
            ],
        ],
    ],

    'testDataAmbiguityInWebhookWithSamePinCodeAndSameBusinessNameInUpperCase' => [
        'request'  => [
            'url'     => '/banking_accounts/webhooks/account_info/rbl',
            'method'  => 'POST',
            'content' => [
                'RZPAlertNotiReq' => [
                    'Body' => [
                        'Account No.'       => '309002180853',
                        'Customer Name'     => 'SKULL GAMERS',
                        'Customer ID'       => 'Customer ID',
                        'Account Open Date' => '21-11-2020',
                        'RZP_Ref No'        => '15597',
                        'IFSC'              => 'HDFC0000090',
                        'Address1'          => 'RAM NAGAR',
                        'Address2'          => 'ADARSHA LANE',
                        'Address3'          =>  '.',
                        'CITY'              => 'MUMBAI',
                        'STATE'             => 'MAHARASH',
                        'COUNTRY'           => 'INDIA',
                        'PINCODE'           => '560034',
                        'Phone no.'         => '9899807189',
                        'Email Id'          => 'test@gmail.com'
                    ],
                    'Header' => [
                        'TranID' => '1234'
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'RZPAlertNotiRes' => [
                    'Header' => [
                        'TranID' => '1234'
                    ],
                    'Body' => [
                        'Status' => 'Success'
                    ]
                ]
            ],
        ],
    ],

    'testDataAmbiguityInWebhookWithSamePinCodeAndDifferentBusinessName' => [
        'request'  => [
            'url'     => '/banking_accounts/webhooks/account_info/rbl',
            'method'  => 'POST',
            'content' => [
                'RZPAlertNotiReq' => [
                    'Body' => [
                        'Account No.'       => '309002180853',
                        'Customer Name'     => 'INTERNET BANKING CA',
                        'Customer ID'       => 'Customer ID',
                        'Account Open Date' => '21-11-2020',
                        'RZP_Ref No'        => '15597',
                        'IFSC'              => 'HDFC0000090',
                        'Address1'          => 'RAM NAGAR',
                        'Address2'          => 'ADARSHA LANE',
                        'Address3'          => '.',
                        'CITY'              => 'MUMBAI',
                        'STATE'             => 'MAHARASH',
                        'COUNTRY'           => 'INDIA',
                        'PINCODE'           => '560034',
                        'Phone no.'         => '9899807189',
                        'Email Id'          => 'test@gmail.com'
                    ],
                    'Header' => [
                        'TranID' => '1234'
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'RZPAlertNotiRes' => [
                    'Header' => [
                        'TranID' => '1234'
                    ],
                    'Body' => [
                        'Status' => 'Success'
                    ]
                ]
            ],
        ],
    ],

    'testDataAmbiguityInWebhookWithSamePinCodeAndSimilarityInBusinessNameLessThanRequiredPercent' => [
        'request'  => [
            'url'     => '/banking_accounts/webhooks/account_info/rbl',
            'method'  => 'POST',
            'content' => [
                'RZPAlertNotiReq' => [
                    'Body' => [
                        'Account No.'       => '309002180853',
                        'Customer Name'     => 'INTERNET CA',
                        'Customer ID'       => 'Customer ID',
                        'Account Open Date' => '21-11-2020',
                        'RZP_Ref No'        => '15597',
                        'IFSC'              => 'HDFC0000090',
                        'Address1'          => 'RAM NAGAR',
                        'Address2'          => 'ADARSHA LANE',
                        'Address3'          => '.',
                        'CITY'              => 'MUMBAI',
                        'STATE'             => 'MAHARASH',
                        'COUNTRY'           => 'INDIA',
                        'PINCODE'           => '560034',
                        'Phone no.'         => '9899807189',
                        'Email Id'          => 'test@gmail.com'
                    ],
                    'Header' => [
                        'TranID' => '1234'
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'RZPAlertNotiRes' => [
                    'Header' => [
                        'TranID' => '1234'
                    ],
                    'Body' => [
                        'Status' => 'Success'
                    ]
                ]
            ],
        ],
    ],

    'testAccountOpeningWebhookWithExistingAccountNumber' => [
        'request'  => [
            'url'     => '/banking_accounts/webhooks/account_info/rbl',
            'method'  => 'POST',
            'content' => [
                'RZPAlertNotiReq' => [
                    'Body' => [
                        'Account No.'       => '309002180853',
                        'Customer Name'     => 'INTERNET BANKING CA',
                        'Customer ID'       => 'Customer ID',
                        'Account Open Date' => '22-05-2019',
                        'RZP_Ref No'        => '15597',
                        'IFSC'              => 'HDFC0000090',
                        'Address1'          => 'RAM NAGAR',
                        'Address2'          => 'ADARSHA LANE',
                        'Address3'          => '.',
                        'CITY'              => 'MUMBAI',
                        'STATE'             => 'MAHARASH',
                        'COUNTRY'           => 'INDIA',
                        'PINCODE'           => '123456',
                        'Phone no.'         => '9899807189',
                        'Email Id'          => 'test@gmail.com'
                    ],
                    'Header' => [
                        'TranID' => '12345'
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'RZPAlertNotiRes' => [
                    'Header' => [
                        'TranID' => '12345'
                    ],
                    'Body' => [
                        'Status' => 'Failure'
                    ]
                ]
            ],
        ],
    ],

    'testRzpRefNumberNotExistScenarioInAccountOpeningWebhook' => [
        'request'  => [
            'url'     => '/banking_accounts/webhooks/account_info/rbl',
            'method'  => 'POST',
            'content' => [
                'RZPAlertNotiReq' => [
                    'Body' => [
                        'Account No.'       => '309002180853',
                        'Customer Name'     => 'INTERNET BANKING CA',
                        'Customer ID'       => 'Customer ID',
                        'Account Open Date' => '22-05-2019',
                        'RZP_Ref No'        => '00000',
                        'IFSC'              => 'HDFC0000090',
                        'Address1'          => 'RAM NAGAR',
                        'Address2'          => 'ADARSHA LANE',
                        'Address3'          => '.',
                        'CITY'              => 'MUMBAI',
                        'STATE'             => 'MAHARASH',
                        'COUNTRY'           => 'INDIA',
                        'PINCODE'           => '123456',
                        'Phone no.'         => '9899807189',
                        'Email Id'          => 'test@gmail.com'
                    ],
                    'Header' => [
                        'TranID' => '12345'
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'RZPAlertNotiRes' => [
                    'Header' => [
                        'TranID' => '12345'
                    ],
                    'Body' => [
                        'Status' => 'Failure'
                    ]
                ]
            ],
        ],
    ],

    'testResetWebhookDataCase' => [
        'request'  => [
            'url'     => 'banking_accounts/{id}/webhooks/account_info/reset',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'channel'       => 'rbl'
            ]
        ],
    ],

    'testUpdateBankingAccountToInitiated' => [
        'request'  => [
            'url'     => '/banking_account',
            'method'  => 'PATCH',
            'content' => [
                BankingAccount\Entity::STATUS => BankingAccount\Status::INITIATED,
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id'                  => '10000000000000',
                'channel'                      => 'rbl',
                BankingAccount\Entity::STATUS => BankingAccount\Status::INITIATED,
            ],
        ],
    ],

    'testUpdateBankingAccountPincode' => [
        'request'  => [
            'url'     => '/banking_accounts_dashboard',
            'method'  => 'PATCH',
            'server' => [
                'X-Dashboard-User-Id' => '20000000000000',
            ],
            'content' => [
                'pincode'   => '560031'
            ],
        ],
        'response' => [
            'content' => [
                'channel'                      => 'rbl',
                BankingAccount\Entity::PINCODE => '560031',
            ],
        ],
    ],

    'testBusinessPanValidation' => [
        'request'  => [
            'url'     => '/banking_accounts_dashboard',
            'method'  => 'PATCH',
            'content' => [
                'activation_detail' => [
                    BankingAccount\Activation\Detail\Entity::BUSINESS_PAN => 'RZP4A2345L',
                    BankingAccount\Activation\Detail\Entity::BUSINESS_NAME=> 'RZP.Co'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'channel'                      => 'rbl',
                BankingAccount\Entity::PINCODE => '560030',
                'banking_account_activation_details' => [
                    'business_pan_validation' => 'initiated'
                ]
            ],
        ],
    ],

    'testPanValidation' => [
        'request'  => [
            'url'     => '/banking_accounts_dashboard',
            'method'  => 'PATCH',
            'content' => [
                'activation_detail' => [
                    BankingAccount\Activation\Detail\Entity::BUSINESS_PAN => 'RZP4A2345L',
                    BankingAccount\Activation\Detail\Entity::BUSINESS_NAME=> 'RZP.Co'
                    ]
            ],
        ],
        'response' => [
            'content' => [
                'channel'                      => 'rbl',
                BankingAccount\Entity::PINCODE => '560030',
            ],
        ],
    ],

    'testPanValidationForPersonalPan' => [
        'request'  => [
            'url'     => '/banking_accounts_dashboard',
            'method'  => 'PATCH',
            'content' => [
                'activation_detail' => [
                    BankingAccount\Activation\Detail\Entity::BUSINESS_PAN => 'RZP4A2345L',
                    BankingAccount\Activation\Detail\Entity::BUSINESS_NAME => 'RZP.Co',
                    BankingAccount\Activation\Detail\Entity::MERCHANT_POC_NAME => 'Random Name',
                ]
            ],
        ],
        'response' => [
            'content' => [
                'channel'                      => 'rbl',
                BankingAccount\Entity::PINCODE => '560030',
            ],
        ],
    ],

    'testGetBankingAccounts' => [
        'request'  => [
            'url'     => '/banking_account',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                "entity" => "collection",
                "count" => 1,
                "items" => [
                    [
                        BankingAccount\Entity::CHANNEL => "rbl",
                        BankingAccount\Entity::STATUS => "created",
                        BankingAccount\Entity::SUB_STATUS => null,
                        BankingAccount\Entity::MERCHANT_ID => "10000000000000",
                        BankingAccount\Entity::ACCOUNT_NUMBER => null,
                        BankingAccount\Entity::ACCOUNT_IFSC => null,
                        BankingAccount\Entity::BANK_INTERNAL_STATUS => null,
                        BankingAccount\Entity::REFERENCE1 => null,
                        BankingAccount\Entity::ACCOUNT_TYPE => "current",
                        BankingAccount\Entity::ACCOUNT_CURRENCY => "INR",
                        BankingAccount\Entity::BENEFICIARY_EMAIL => null,
                        BankingAccount\Entity::BENEFICIARY_MOBILE => null,
                        BankingAccount\Entity::BENEFICIARY_NAME => null,
                        BankingAccount\Entity::PINCODE => "560030",
                        BankingAccount\Entity::BALANCE => null,
                        "bank_reference_number" => "10000",
                        "banking_account_ca_spoc_details" => [
                            "rm_name" => null,
                            "rm_phone_number" => null,
                            "sales_poc_phone_number" => null,
                            "sales_poc_name" => "test admin",
                            "sales_poc_email" => "superadmin@razorpay.com"
                        ]
                    ]
                ]
            ]
        ],
    ],

    'testGetBankingAccountsArchived' => [
        'request'  => [
            'url'     => '/admin/banking_account',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                "entity" => "collection",
                "count" => 0,
                "items" => []
            ]
        ],
    ],

    'testGetBankingAccount' => [
        'request'  => [
            'url'     => '/banking_accounts',
            'method'  => 'GET',
            'server' => [
                'X-Dashboard-User-Id' => '20000000000000',
            ],
        ],
        'response' => [
            'content' => [
                'channel'                      => 'rbl',
                BankingAccount\Entity::PINCODE => '560030',
                'banking_account_activation_details' => [
                    'merchant_poc_name' => 'Sample Name',
                    'merchant_poc_designation' => 'Financial Consultant',
                    'merchant_poc_email' => 'sample@sample.com',
                    'merchant_poc_phone_number' => '9876556789',
                    'merchant_documents_address' => 'x, y, z',
                    'business_category' => 'partnership',
                    'sales_team' => 'self_serve',
                ],
            ],
        ],
    ],

    'testGetBankingAccountByAccountTypeFromMOB' => [
        'request'  => [
            'url'    => '/banking_accounts?account_type=current',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                "entity" => "collection",
                "count"  => 1,
                "items"  => [
                    [
                        'channel'      => 'rbl',
                        'merchant_id'  => '10000000000000',
                        'account_type' => 'current',
                    ],
                ],
            ],
        ],
    ],

    'testGetCorpCardBankingAccountByAccountType' => [
        'request'  => [
            'url'    => '/banking_accounts?account_type[]=corp_card',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                "entity" => "collection",
                "count"  => 1,
                "items"  => [
                    [
                        'channel'      => 'rbl',
                        'merchant_id'  => '10000000000000',
                        'account_type' => 'corp_card',
                        'balance'      =>
                            [
                                'corp_card_details' =>
                                    [
                                        'entity_id'      => 'qaghsquiqasdwd',
                                        'account_number' => '10234561782934',
                                        'user_id'        => 'wgahkasyqsdghws',
                                    ]
                            ]
                    ]
                ],
            ],
        ],
    ],

    'testGetCorpCardBankingAccountNotFound' => [
        'request'  => [
            'url'    => '/banking_accounts?account_type[]=corp_card',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                "entity" => "collection",
                "count"  => 0,
                "items"  => [],
            ],
        ],
    ],

    'testGetBankingAccountByAccountTypes' => [
        'request'  => [
            'url'    => '/banking_accounts?account_type[]=nodal&account_type[]=current',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                "entity" => "collection",
                "count"  => 2,
                "items"  => [
                    [
                        'channel'      => 'rbl',
                        'merchant_id'  => '10000000000000',
                        'account_type' => 'nodal',
                    ],
                    [
                        'channel'      => 'icici',
                        'merchant_id'  => '10000000000000',
                        'account_type' => 'current',
                    ]
                ],
            ],
        ],
    ],

    'testGetBankingAccountInternalViaMob' => [
        'request'  => [
            'url'     => '/banking_accounts',
            'method'  => 'GET',
            'server' => [
                'X-Dashboard-User-Id' => '20000000000000',
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [
                'channel'                      => 'rbl',
                BankingAccount\Entity::PINCODE => '560030',
                'banking_account_activation_details' => [
                    'merchant_poc_name' => 'Sample Name',
                    'merchant_poc_designation' => 'Financial Consultant',
                    'merchant_poc_email' => 'sample@sample.com',
                    'merchant_poc_phone_number' => '9876556789',
                    'merchant_documents_address' => 'x, y, z',
                    'business_category' => 'partnership',
                    'sales_team' => 'self_serve',
                ],
            ],
        ],
    ],

    'testGetBankingAccountOfOtherMerchant' => [
        'request'  => [
            'url'     => '/banking_accounts',
            'method'  => 'GET',
            'server' => [
                'X-Dashboard-User-Id' => '20000000000000',
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ]
    ],

    'testGetBankingAccountForRmNotAssigned' => [
        'request'  => [
            'url'     => '/banking_accounts',
            'method'  => 'GET',
            'server' => [
                'X-Dashboard-User-Id' => '20000000000000',
            ],
        ],
        'response' => [
            'content' => [
                'channel'                      => 'rbl',
                BankingAccount\Entity::PINCODE => '560030',
                'banking_account_activation_details' => [
                    'merchant_poc_name' => 'Sample Name',
                    'merchant_poc_designation' => 'Financial Consultant',
                    'rm_name' => null
                ],
            ],
        ],
    ],

    'testUpdateBankingAccountToPicked' => [
        'request'  => [
            'url'     => '/banking_account',
            'method'  => 'PATCH',
            'content' => [
                BankingAccount\Entity::STATUS => BankingAccount\Status::PICKED,
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id'                  => '10000000000000',
                'channel'                      => 'rbl',
                BankingAccount\Entity::STATUS => BankingAccount\Status::PICKED,
            ],
        ],
    ],

    'testUpdatedStatusFromCreatedToCancelled' => [
        'request'  => [
            'url'     => '/banking_account',
            'method'  => 'PATCH',
            'content' => [
                BankingAccount\Entity::STATUS => BankingAccount\Status::CANCELLED,
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id'                  => '10000000000000',
                'channel'                      => 'rbl',
                BankingAccount\Entity::STATUS => BankingAccount\Status::CANCELLED,
            ],
        ],
    ],

    'testUpdatedStatusFromCreatedToPicked' => [
        'request'  => [
            'url'     => '/banking_account',
            'method'  => 'PATCH',
            'content' => [
                BankingAccount\Entity::STATUS => BankingAccount\Status::PICKED,
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id'                  => '10000000000000',
                'channel'                      => 'rbl',
                BankingAccount\Entity::STATUS => BankingAccount\Status::PICKED,
            ],
        ],
    ],

    'testUpdatedStatusFromProcessingToProcessed' => [
        'request'  => [
            'url'     => '/banking_account',
            'method'  => 'PATCH',
            'content' => [
                BankingAccount\Entity::STATUS                         => BankingAccount\Status::PROCESSED,
                BankingAccount\Entity::SUB_STATUS                     => BankingAccount\Status::API_ONBOARDING_PENDING,
                BankingAccount\Entity::BANK_INTERNAL_STATUS           => BankingAccount\Gateway\Rbl\Status::CLOSED,
                BankingAccount\Entity::ACCOUNT_IFSC                   => 'RATN0000156',
                BankingAccount\Entity::ACCOUNT_NUMBER                 => '309002180853',
                BankingAccount\Entity::BENEFICIARY_NAME               => 'INTERNET BANKING CA',
                BankingAccount\Entity::BANK_INTERNAL_REFERENCE_NUMBER => 'random',
                BankingAccount\Entity::BANK_REFERENCE_NUMBER          => '12345',
                BankingAccount\Entity::BENEFICIARY_ADDRESS1           => 'RAM NAGAR',
                BankingAccount\Entity::BENEFICIARY_ADDRESS2           => 'ADARSHA LANE',
                BankingAccount\Entity::BENEFICIARY_ADDRESS3           => '.',
                BankingAccount\Entity::ACCOUNT_ACTIVATION_DATE        => '1571119612',
                BankingAccount\Entity::BENEFICIARY_CITY               => 'MUMBAI',
                BankingAccount\Entity::BENEFICIARY_STATE              => 'MAHARASH',
                BankingAccount\Entity::BENEFICIARY_COUNTRY            => 'INDIA',
                BankingAccount\Entity::BENEFICIARY_MOBILE             => '1231231231',
                BankingAccount\Entity::BENEFICIARY_EMAIL              => 'test@razorpay.com',
                BankingAccount\Entity::BENEFICIARY_PIN                => '560030',
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id'                 => '10000000000000',
                'channel'                     => 'rbl',
                BankingAccount\Entity::STATUS => BankingAccount\Status::PROCESSED,
            ],
        ],
    ],

    'testUpdatedStatusFromInitiatedToAccountOpening' => [
        'request'  => [
            'url'     => '/banking_account',
            'method'  => 'PATCH',
            'content' => [
                BankingAccount\Entity::STATUS => BankingAccount\Status::ACCOUNT_OPENING,
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id'                  => '10000000000000',
                'channel'                      => 'rbl',
                BankingAccount\Entity::STATUS => BankingAccount\Status::ACCOUNT_OPENING,
            ],
        ],
    ],

    'testUpdatedStatusFromProcessingToRejected' => [
        'request'  => [
            'url'     => '/banking_account',
            'method'  => 'PATCH',
            'content' => [
                BankingAccount\Entity::STATUS => BankingAccount\Status::REJECTED,
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id'                  => '10000000000000',
                'channel'                      => 'rbl',
                BankingAccount\Entity::STATUS => BankingAccount\Status::REJECTED,
            ],
        ],
    ],

    'testUpdateBankingAccountToInitiatedWithInternalComments' => [
        'request'  => [
            'url'     => '/banking_account',
            'method'  => 'PATCH',
            'content' => [
                BankingAccount\Entity::STATUS            => BankingAccount\Status::INITIATED,
                BankingAccount\Entity::INTERNAL_COMMENT  => 'Sending Application to Bank'
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id'                  => '10000000000000',
                'channel'                      => 'rbl',
                BankingAccount\Entity::STATUS => BankingAccount\Status::INITIATED,
            ],
        ],
    ],

    'testUpdateAccountInfoWebhookInternally'  => [
        'request'  => [
            'url'     => '/banking_accounts/internal/webhooks/account_info/rbl',
            'method'  => 'POST',
            'content' => [
                'RZPAlertNotiReq' => [
                    'Body' => [
                        'Account No.'         => '309002180853',
                        'Customer Name'      => 'INTERNET BANKING CA',
                        'Customer ID'        => 'Customer ID',
                        'Account Open Date'  => '22-05-2019',
                        'RZP_Ref No'         => '15597',
                        'IFSC'               => 'HDFC0000090',
                        'Address1'             => 'RAM NAGAR',
                        'Address2'             => 'ADARSHA LANE',
                        'Address3'             => '.',
                        'CITY'               => 'MUMBAI',
                        'STATE'              => 'MAHARASH',
                        'COUNTRY'            => 'INDIA',
                        'PINCODE'            => '123456',
                        'Phone no.'           => '9899807189',
                        'Email Id'           => 'test@gmail.com'
                    ],
                    'Header' => [
                        'TranID' => '12345'
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'RZPAlertNotiRes' => [
                    'Header' => [
                        'TranID' => '12345'
                    ],
                    'Body' => [
                        'Status' => 'Success'
                    ]
                ]
            ],
        ],
    ],

    'testDoubleAccountOpeningWebhooks' => [
        'request'  => [
            'url'     => '/banking_accounts/webhooks/account_info/rbl',
            'method'  => 'POST',
            'content' => [
                'RZPAlertNotiReq' => [
                    'Body' => [
                        'Account No.'       => '319002180853',
                        'Customer Name'     => 'INTERNET BANKING CA',
                        'Customer ID'       => 'Customer ID',
                        'Account Open Date' => '22-05-2019',
                        'RZP_Ref No'        => '15597',
                        'IFSC'              => 'HDFC0000090',
                        'Address1'          => 'RAM NAGAR',
                        'Address2'          => 'ADARSHA LANE',
                        'Address3'          => '.',
                        'CITY'              => 'MUMBAI',
                        'STATE'             => 'MAHARASH',
                        'COUNTRY'           => 'INDIA',
                        'PINCODE'           => '123456',
                        'Phone no.'         => '9899807189',
                        'Email Id'          => 'test@gmail.com'
                    ],
                    'Header' => [
                        'TranID' => '12345'
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'RZPAlertNotiRes' => [
                    'Header' => [
                        'TranID' => '12345'
                    ],
                    'Body' => [
                        'Status' => 'Failure'
                    ]
                ]
            ],
        ],
    ],

    'testActivate' => [
        'request'  => [
            'url'     => '/banking_accounts/{id}/activate',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'channel'       => 'rbl',
                'status'        => 'activated',
                'reference1'    => 'MERCHANT_SUB_CORP'
            ]
        ],
    ],

    'testActivateWithLedgerShadow' => [
        'request'  => [
            'url'     => '/banking_accounts/{id}/activate',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'channel'       => 'rbl',
                'status'        => 'activated',
                'reference1'    => 'MERCHANT_SUB_CORP'
            ]
        ],
    ],

    'testActivateWithoutKYC' => [
        'request'  => [
            'url'     => '/banking_accounts/{id}/activate',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'channel'       => 'rbl',
                'status'        => 'activated',
                'reference1'    => 'MERCHANT_SUB_CORP'
            ]
        ],
    ],

    'testActivateFailedDueToFtsFailure' => [
        'request'  => [
            'url'     => '/banking_accounts/{id}/activate',
            'method'  => 'POST',
            'content' => [],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Operation could not be completed. Please try again',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR_BANKING_ACCOUNT_ACTIVATION_FAILED,
        ],
    ],
    'testActivateFailedDueToFtsDirectAccountCreationValidationFailure' => [
        'request'  => [
            'url'     => '/banking_accounts/{id}/activate',
            'method'  => 'POST',
            'content' => [],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Operation failed. FTS Account could not stored because of a validation error: '.'VALIDATION_ERROR',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR_DIRECT_FUND_ACCOUNT_AND_SOURCE_ACCOUNT_CREATION_VALIDATION_FAILED,
        ],
    ],
    'testActivateFailedDueToMozartGatewayException' => [
        'request'  => [
            'url'     => '/banking_accounts/{id}/activate',
            'method'  => 'POST',
            'content' => [],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Operation could not be completed. Please try again',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR_BANKING_ACCOUNT_ACTIVATION_FAILED,
        ],
    ],

    'testActivateFailedDueToMissingData' => [
        'request'  => [
            'url'     => '/banking_accounts/{id}/activate',
            'method'  => 'POST',
            'content' => [],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The account number field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFailedBankAccountInfoNotification' => [
        'request'  => [
            'url'     => '/banking_accounts/webhooks/account_info/rbl',
            'method'  => 'POST',
            'content' => [
                'RZPAlertNotiReq' => [
                    'Body' => [
                        'Account No.'           => '309002180853',
                        'Customer Name'         => 'INTERNET BANKING CA',
                        'Customer ID'           => 'Customer ID',
                        'Account Open Date'     => '22-05-2019',
                        'RZP_Ref No'            => '15597',
                        'IFSC'                  => 'HDFC0000090',
                        'Address1'              => 'RAM NAGAR',
                        'Address2'              => 'ADARSHA LANE',
                        'Address3'              => '.',
                        'CITY'                  => 'MUMBAI',
                        'STATE'                 => 'MAHARASH',
                        'COUNTRY'               => 'INDIA',
                        'PINCODE'               => '123456',
                        'Phone no.'             => '9899807189',
                        'Email Id'              => 'test@gmail.com'
                    ],
                    'Header' => [
                        'TranID' => '12345'
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'RZPAlertNotiRes' => [
                    'Header' => [
                        'TranID' => '12345'
                    ],
                    'Body' => [
                        'Status' => 'Failure'
                    ]
                ]
            ],
        ],
    ],

    'testUpdateAccountOpeningInfoWebhookDetailsForMissedWebhook' => [
        'request'  => [
            'url'     => '/banking_account',
            'method'  => 'PATCH',
            'content' => [
                BankingAccount\Entity::STATUS                           => BankingAccount\Status::PROCESSED,
                BankingAccount\Entity::BANK_INTERNAL_STATUS             => BankingAccount\Gateway\Rbl\Status::CLOSED,
                BankingAccount\Entity::ACCOUNT_IFSC                     => 'HDFC0000090',
                BankingAccount\Entity::ACCOUNT_NUMBER                   => '309002180853',
                BankingAccount\Entity::BENEFICIARY_NAME                 => 'INTERNET BANKING CA',
                BankingAccount\Entity::BANK_INTERNAL_REFERENCE_NUMBER   => 'random',
                BankingAccount\Entity::BANK_REFERENCE_NUMBER            => 'tobefilled',
                BankingAccount\Entity::BENEFICIARY_ADDRESS1             => 'RAM NAGAR',
                BankingAccount\Entity::BENEFICIARY_ADDRESS2             => 'ADARSHA LANE',
                BankingAccount\Entity::BENEFICIARY_ADDRESS3             => '.',
                BankingAccount\Entity::ACCOUNT_ACTIVATION_DATE          => '2019-06-22',
                BankingAccount\Entity::BENEFICIARY_CITY                 => 'MUMBAI',
                BankingAccount\Entity::BENEFICIARY_STATE                => 'MAHARASH',
                BankingAccount\Entity::BENEFICIARY_COUNTRY              => 'INDIA',
                BankingAccount\Entity::BENEFICIARY_MOBILE               => '9899807189',
                BankingAccount\Entity::BENEFICIARY_EMAIL                => 'test@gmail.com',
                BankingAccount\Entity::BENEFICIARY_PIN                  => '560030',
            ],
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                 BankingAccount\Entity::STATUS => BankingAccount\Status::PROCESSED,
            ],
        ],
    ],

    'testUpdateBankingAccountToUnserviceable' => [
        'request'  => [
            'url'     => '/banking_account',
            'method'  => 'PATCH',
            'content' => [
                BankingAccount\Entity::STATUS => BankingAccount\Status::UNSERVICEABLE,
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id'                  => '10000000000000',
                'channel'                      => 'rbl',
                 BankingAccount\Entity::STATUS => BankingAccount\Status::UNSERVICEABLE,
            ],
        ],
    ],

    'testupdateBankingAccountWithCommentViaMobWithAdminContext' => [
        'request'  => [
            'method'  => 'PATCH',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'updateBankingAccount' => [
        'request'  => [
            'url'     => '/banking_account',
            'method'  => 'PATCH',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'channel'     => 'rbl',
            ],
        ],
    ],

    'assertUpdateBankingAccountStatusFromToForNeostone' => [
        'request'  => [
            'url'     => '/banking_account',
            'method'  => 'PATCH',
            'content' => [
                BankingAccount\Entity::STATUS                   => '',
                BankingAccount\Entity::ACCOUNT_NUMBER           => '12345678910',
                BankingAccount\Entity::ACCOUNT_IFSC             => 'HDFC0009830',
                BankingAccount\Entity::BENEFICIARY_NAME         => 'test name',
                BankingAccount\Entity::BENEFICIARY_MOBILE       => '7899672680',
                BankingAccount\Entity::BENEFICIARY_EMAIL        => 'test@gmail.com',
                BankingAccount\Entity::BENEFICIARY_COUNTRY      => 'india',
                BankingAccount\Entity::BENEFICIARY_PIN          => '560030',
                BankingAccount\Entity::BENEFICIARY_STATE        => 'karanataka',
                BankingAccount\Entity::BENEFICIARY_CITY         => 'Bangalore',
                BankingAccount\Entity::BENEFICIARY_ADDRESS1     => 'add1',
                BankingAccount\Entity::BENEFICIARY_ADDRESS2     => 'add2',
                BankingAccount\Entity::BENEFICIARY_ADDRESS3     => 'add3',
                BankingAccount\Entity::ACCOUNT_ACTIVATION_DATE  => '1562749680'
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'channel'     => 'rbl',
                BankingAccount\Entity::STATUS => '',
            ],
        ],
    ],

    'assertUpdateBankingAccountStatusFromTo' => [
        'request'  => [
            'url'     => '/banking_account',
            'method'  => 'PATCH',
            'content' => [
                BankingAccount\Entity::STATUS                   => '',
                BankingAccount\Entity::ACCOUNT_NUMBER           => '12345678910',
                BankingAccount\Entity::ACCOUNT_IFSC             => 'HDFC0009830',
                BankingAccount\Entity::BENEFICIARY_NAME         => 'test name',
                BankingAccount\Entity::BENEFICIARY_MOBILE       => '7899672680',
                BankingAccount\Entity::BENEFICIARY_EMAIL        => 'test@gmail.com',
                BankingAccount\Entity::BENEFICIARY_COUNTRY      => 'india',
                BankingAccount\Entity::BENEFICIARY_PIN          => '560030',
                BankingAccount\Entity::BENEFICIARY_STATE        => 'karanataka',
                BankingAccount\Entity::BENEFICIARY_CITY         => 'Bangalore',
                BankingAccount\Entity::BENEFICIARY_ADDRESS1     => 'add1',
                BankingAccount\Entity::BENEFICIARY_ADDRESS2     => 'add2',
                BankingAccount\Entity::BENEFICIARY_ADDRESS3     => 'add3',
                BankingAccount\Entity::ACCOUNT_ACTIVATION_DATE  => '1562749680'
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'channel'     => 'rbl',
                BankingAccount\Entity::STATUS => '',
            ],
        ],
    ],

    'testUpdateBankingAccountStatusAsProcessed' => [
        'request'  => [
            'url'     => '/banking_account',
            'method'  => 'PATCH',
            'content' => [
                BankingAccount\Entity::STATUS                   => BankingAccount\Status::PROCESSED,
                BankingAccount\Entity::ACCOUNT_NUMBER           => '12345678910',
                BankingAccount\Entity::ACCOUNT_IFSC             => 'HDFC0009830',
                BankingAccount\Entity::BENEFICIARY_NAME         => 'test name',
                BankingAccount\Entity::BENEFICIARY_MOBILE       => '7899672680',
                BankingAccount\Entity::BENEFICIARY_EMAIL        => 'test@gmail.com',
                BankingAccount\Entity::BENEFICIARY_COUNTRY      => 'india',
                BankingAccount\Entity::BENEFICIARY_PIN          => '560030',
                BankingAccount\Entity::BENEFICIARY_STATE        => 'karanataka',
                BankingAccount\Entity::BENEFICIARY_CITY         => 'Bangalore',
                BankingAccount\Entity::BENEFICIARY_ADDRESS1     => 'add1',
                BankingAccount\Entity::BENEFICIARY_ADDRESS2     => 'add2',
                BankingAccount\Entity::BENEFICIARY_ADDRESS3     => 'add3',
                BankingAccount\Entity::ACCOUNT_ACTIVATION_DATE  => '1562749680'
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'channel'     => 'rbl',
                BankingAccount\Entity::STATUS => BankingAccount\Status::PROCESSED,
            ],
        ],
    ],

    'testUpdateBankingAccountDetails' => [
        'request'  => [
            'url'     => '/banking_account',
            'method'  => 'PATCH',
            'content' => [
                BankingAccount\Entity::DETAILS => [
                    BankingAccount\Gateway\Rbl\Fields::CLIENT_SECRET  => 'api_secret',
                    BankingAccount\Gateway\Rbl\Fields::CLIENT_ID      => 'api_key',
                ]
            ]
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                 BankingAccount\Entity::STATUS => BankingAccount\Status::PROCESSED,
            ],
        ],
    ],

    'testUpdateBankingAccountDetailsWithOverride' => [
        'request'  => [
            'url'     => '/banking_account',
            'method'  => 'PATCH',
            'content' => [
                BankingAccount\Entity::DETAILS => [
                    BankingAccount\Gateway\Rbl\Fields::CLIENT_ID     => 'api_key_two',
                ]
            ]
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                BankingAccount\Entity::STATUS => BankingAccount\Status::PROCESSED,
            ],
        ],
    ],

    'accountBalanceSuccess' => [
        'data' => [
            'PayGenRes' => [
                'Body' => [
                    'BalAmt' => [
                        'amountValue'  => '0',
                        'currencyCode' => '{}'
                    ]
                ],
                'Header' => [
                    'Approver_ID' => '',
                    'Corp_ID'     => '',
                    'Error_Cde'   => '',
                    'Error_Desc'  => '',
                    'Status'      => 'SUCCESS',
                    'TranID'      => '1234'
                ],
                'Signature' => [
                    'Signature' => 'Signature'
                ],
            ],

            'error'             => null,
            'external_trace_id' => '',
            'mozart_id'         => 'bk5pjbrc1osidogfb7jg',
            'next'              => '{}',
            'success'           => true
        ]
    ],

    'accountBalanceFailure' => [
        'data' => [
            'PayGenRes' => [
                'Body' => [
                    'BalAmt' => [
                        'amountValue'  => '0',
                        'currencyCode' => '{}'
                    ]
                ],
                'Header' => [
                    'Approver_ID' => '',
                    'Corp_ID'     => '',
                    'Error_Cde'   => 'ER022',
                    'Error_Desc'  => 'Request not valid for the given AccountId',
                    'Status'      => 'FAILED',
                    'TranID'      => '1234'
                ],
                'Signature' => [
                    'Signature' => 'Signature'
                ],
            ],

            'error'             => [
                'description'               => 'Request not valid for the given AccountId',
                'gateway_error_code'        => 'ER022',
                'gateway_error_description' => 'Request not valid for the given AccountId',
                'gateway_status_code'       => 200,
                'internal_error_code'       => 'TXN_NOT_ALLOWED'
            ],
            'external_trace_id' => '',
            'mozart_id'         => 'bk5pjbrc1osidogfb7jg',
            'next'              => '{}',
            'success'           => false
        ]
    ],

    'testUpdateBankingAccountStatusAsProcessedFailed' => [
        'request'  => [
            'url'     => '/banking_account',
            'method'  => 'PATCH',
            'content' => [
                BankingAccount\Entity::STATUS => BankingAccount\Status::PROCESSED,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The account number field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateBankingAccountIncorrectCurrentToPreviousStatus' => [
        'request'  => [
            'url'     => '/banking_account',
            'method'  => 'PATCH',
            'content' => [
                BankingAccount\Entity::STATUS => BankingAccount\Status::PROCESSING,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => sprintf('Status change from %s to %s not permitted',
                        BankingAccount\Status::transformFromInternalToExternal(BankingAccount\Status::CREATED),
                        BankingAccount\Status::transformFromInternalToExternal(BankingAccount\Status::PROCESSING)),
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testBankingAccountFetch' => [
        'request' => [
            'url'     => '/admin/banking_account',
            'method'  => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
            ],
        ],
    ],

    'testBankingAccountFetchForAccountNumber' => [
        'request' => [
            'url'     => '/admin/banking_account',
            'method'  => 'get',
            'content' => [
                'account_number' => '1234567808',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
            ],
        ],
    ],

    'testAdminFetchBankingAccountRequests' => [
        'request'  => [
            'url'     => '/admin/banking_account?count=20&skip=0&sales_team=self_serve&declaration_step=1&business_category=partnership',
            'method'  => 'GET',
            'content' => [
                'expand' => ['merchant','merchant.merchantDetail'],
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'merchant'      => [
                            'merchant_detail' => [
                                'contact_email' => 'test@rzp.com'
                            ]
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testSortBySlotBookingDate' => [
        'request'  => [
            'url'     => '/admin/banking_account?count=20&skip=0&sales_team=self_serve&declaration_step=1&business_category=partnership&sort_slot_booked=asc',
            'method'  => 'GET',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testFilterSlotBookingDate' => [
        'request'  => [
            'url'     => '/admin/banking_account?count=20&skip=0&sales_team=self_serve&declaration_step=1&business_category=partnership&sort_slot_booked=asc',
            'method'  => 'GET',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testFilterSlotBookingDateWithClarityContext' => [
        'request'  => [
            'url'     => '/admin/banking_account?count=20&clarity_context=completed',
            'method'  => 'GET',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
            ],
        ],
    ],

    'testFilterFromToSlotBookingDate' => [
        'request'  => [
            'url'     => '/admin/banking_account?count=20&skip=0&sales_team=self_serve&declaration_step=1&business_category=partnership&sort_slot_booked=asc',
            'method'  => 'GET',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testSortBankingAccountActivationCallLog' => [
        'request'  => [
            'url'     => '/admin/banking_account?count=20&skip=0&sales_team=self_serve&declaration_step=1&business_category=partnership&sort_follow_up_date=asc',
            'method'  => 'GET',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testFilterBankingAccountActivationCallFollowUpDate' => [
        'request'  => [
            'url'     => '/admin/banking_account?count=20&skip=0&sales_team=self_serve&declaration_step=1&business_category=partnership&sort_follow_up_date=asc',
            'method'  => 'GET',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testFetchBankingAccountRequests' => [
        'request'  => [
            'url'     => '/admin/banking_account',
            'method'  => 'GET',
            'content' => [
                'expand' => ['merchant','merchant.merchantDetail'],
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'merchant'      => [
                            'merchant_detail' => [
                                'contact_email' => 'test@rzp.com'
                            ]
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testBankingAccountFetchForCurrentAccount' => [
        'request'  => [
            'url'     => '/admin/banking_account',
            'method'  => 'GET',
            'content' => [
                'expand' => ['merchant','merchant.merchantDetail'],
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items' => [
                    [
                        'status'        => 'created',
                        'merchant'      => [
                            'merchant_detail' => [
                                'contact_email' => 'test@rzp.com'
                            ]
                        ]
                    ],
                ]
            ],
        ],
    ],

    'testBankingAccountFetchForMerchantName' => [
        'request' => [
            'url'     => '/admin/banking_account',
            'method'  => 'get',
            'content' => [
                'merchant_business_name' => '',
                'expand'                 => ['merchant','merchant.merchantDetail']
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'merchant' => [
                            'merchant_detail' => [
                                'business_name' => ''
                            ]
                        ]
                    ]
                ]
            ],
        ],
    ],

    'testBankingAccountFetchWithMerchantPromotion' => [
        'request' => [
            'url'     => '/admin/banking_account',
            'method'  => 'get',
            'content' => [
                'merchant_business_name' => '',
                'expand'                 => ['merchant','merchant.promotions.promotion']
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'merchant' => [
                            'promotions' => [
                                'items' => [
                                    [
                                        'promotion' => [
                                            'name' => 'RZPNEO'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
        ],
    ],

    'testBankingAccountFetchForMerchantNameMultipleMatch' => [
        'request' => [
            'url'     => '/admin/banking_account',
            'method'  => 'get',
            'content' => [
                'merchant_business_name' => 'test account',
                'expand'                 => ['merchant','merchant.merchantDetail']
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'merchant' => [
                            'merchant_detail' => [
                                'business_name' => 'test account 2'
                            ]
                        ]
                    ],
                    [
                        'merchant' => [
                            'merchant_detail' => [
                                'business_name' => 'Test ACCOUNT 1'
                            ]
                        ]
                    ]
                ]
            ],
        ],
    ],

    'testBankingAccountFetchForMerchantEmail' => [
        'request' => [
            'url'     => '/admin/banking_account',
            'method'  => 'get',
            'content' => [
                'merchant_email' => 'razorpay@testemail.com',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'merchant' => [
                            'email' => 'razorpay@testemail.com'
                        ]
                    ]
                ]
            ],
        ],
    ],

    'testBankingAccountFetchForRZPRefNo' => [
        'request' => [
            'url'     => '/admin/banking_account',
            'method'  => 'get',
            'content' => [
                'bank_reference_number' => '191919',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'bank_reference_number' => '191919',
                    ]
                ]
            ],
        ],
    ],

    'testBankingAccountFetchForMerchantPocCity' => [
        'request' => [
            'url'     => '/admin/banking_account',
            'method'  => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    []
                ]
            ],
        ],
    ],

    'testBankLmsEndToEndForRevivedLeadFilter' => [
        'request' => [
            'url'     => '/banking_accounts/rbl/lms/banking_account',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
            ],
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [],
            ],
            'status_code' => 200,
        ],
    ],

    'testBankingAccountFetchForFOSCities' => [
        'request' => [
            'url' => '/admin/banking_account?fos_city=Bengaluru',
            'method' => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'banking_account_activation_details' => [
                            'merchant_city' => 'Bengaluru'
                        ]
                    ]
                ]
            ],
        ],
    ],

    'testBankingAccountFetchForNonFOSCities' => [
        'request' => [
            'url' => '/admin/banking_account?fos_city=Non_FOS',
            'method' => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'banking_account_activation_details' => [
                            'merchant_city' => 'Indore'
                        ]
                    ]
                ]
            ],
        ],
    ],

    'testBankingAccountFetchForDocsWalkthrough' => [
        'request' => [
            'url'     => '/admin/banking_account',
            'method'  => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    []
                ]
            ],
        ],
    ],

    'testBankingAccountFetchForBankAccountType' => [
        'request' => [
            'url'     => '/admin/banking_account',
            'method'  => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    []
                ]
            ],
        ],
    ],

    'testFetchBankingAccountsOfCreatedStatus'  => [
        'request'  => [
            'url'     => '/admin/banking_account',
            'method'  => 'GET',
            'content' => [
                'expand' => ['merchant','merchant.merchantDetail'],
                'status' => 'created',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'admin'  => true,
                'items' => [
                    [
                        'status'        => 'created',
                        'merchant'      => [
                            'merchant_detail' => [
                                'contact_email' => 'test@rzp.com'
                            ]
                        ]
                    ],
                ]
            ],
        ],
    ],

    'testBankingAccountFetchForCurrentAccountFailure' => [
        'request'  => [
            'url'     => '/admin/banking_account',
            'method'  => 'GET',
            'content' => [
                'expand'       => ['merchant','merchant.merchantDetail'],
                'account_type' => 'current',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 0,
                'admin'  => true,
                'items'  => [],
            ],
        ],
    ],

    'testBankingAccountFetchOnProxyAuthForArchivedCA' => [
        'request'  => [
            'url'     => '/banking_accounts',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [

                ],
            ],
        ],
    ],

    'testBankingAccountFetchOnProxyAuthForOnlyOneArchivedCA' => [
        'request'  => [
            'url'     => '/banking_accounts',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [],
            ],
        ],
    ],

    'testBankingAccountFetchOnProxyAuth' => [
        'request'  => [
            'url'     => '/banking_accounts',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'account_number'    => '2224440041626905',
                        'status'            => 'created',
                        'balance'           => [
                            'balance'       => 200,
                            'currency'      => 'INR',
                        ]
                    ],
                    [
                        'account_number'    => '1234567808',
                        'status'            => 'created',
                        'balance'           => [
                            'balance'       => 100000,
                            'currency'      => 'INR',
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testBankingAccountFetchOnProxyAuthFromLedger' => [
        'request'  => [
            'url'     => '/banking_accounts',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'account_number'    => '2224440041626905',
                        'status'            => 'created',
                        'balance'           => [
                            'balance'       => 160,
                            'currency'      => 'INR',
                        ]
                    ],
                    [
                        'account_number'    => '1234567808',
                        'status'            => 'created',
                        'balance'           => [
                            'balance'       => 100000,
                            'currency'      => 'INR',
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testBankingAccountFetchOnPrivateAuth' => [
        'request'  => [
            'url'     => '/banking_accounts/activated',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'account_number'    => 'XXXXXXXXXXXX6905',
                        'status'            => 'activated',
                    ],
                ],
            ],
        ],
    ],

    'testBankingAccountFetchOnAppleWatchOAuth' => [
        'request'  => [
            'url'     => '/banking_accounts',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'balance' => [
                            'id'      => 'JBLee6cC0erMpg',
                            'balance' => 200
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testBankingAccountFetchCheckFieldLastFetchedAtInBalance' => [
        'request'  => [
            'url'     => '/banking_accounts',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'account_number'    => '2224440041626905',
                        'status'            => 'created',
                        'balance'           => [
                            'balance'       => 300,
                            'currency'      => 'INR',
                        ]
                    ],
                    [
                        'account_number'    => '1234567808',
                        'status'            => 'created',
                        'balance'           => [
                            'balance'       => 90000,
                            'currency'      => 'INR',
                        ]
                    ]
                ],
            ],
        ],
    ],

    'testBankingAccountSPOCDetailsOnBankingAccountFetch' => [
        'request'  => [
            'url'     => '/banking_accounts',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'account_number'    => '1234567890',
                        'status'            => 'created',
                        'balance'           => [
                            'balance'       => 90000,
                            'currency'      => 'INR',
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testBankingAccountSPOCDetailsOnBankingAccountFetchWithRmNameAsVague' => [
        'request'  => [
            'url'     => '/banking_accounts',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'account_number'    => '1234567890',
                        'status'            => 'created',
                        'balance'           => [
                            'balance'       => 90000,
                            'currency'      => 'INR',
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testBankingAccountSPOCDetailsOnBankingAccountFetchWithRmNameAsVagueWithCaseInSensitiveCheck' => [
        'request'  => [
            'url'     => '/banking_accounts',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'account_number'    => '1234567890',
                        'status'            => 'created',
                        'balance'           => [
                            'balance'       => 90000,
                            'currency'      => 'INR',
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testBankingAccountSPOCDetailsOnBankingAccountFetchWithRmNameAsEmpty' => [
        'request'  => [
            'url'     => '/banking_accounts',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'account_number'    => '1234567890',
                        'status'            => 'created',
                        'balance'           => [
                            'balance'       => 90000,
                            'currency'      => 'INR',
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testBulkAssignReviewersToBankingAccounts' => [
        'request'  => [
            'url'     => '/banking_accounts/reviewers',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'success'       =>  2,
                'failed'        =>  0,
                'failedItems'   =>  [],
            ],
        ],
    ],

    'testBulkAssignInvalidReviewersToBankingAccounts' => [
        'request'  => [
            'url'     => '/banking_accounts/reviewers',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'success'       =>  0,
                'failed'        =>  2,
                'error'   =>  'The id provided does not exist',
            ],
        ],
    ],

    'testBulkAssignReviewersToInvalidBankingAccounts' => [
        'request'  => [
            'url'     => '/banking_accounts/reviewers',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'success'       =>  0,
                'failed'        =>  2,
                'failedItems'   =>  [
                    [
                        'error'     => 'The id provided does not exist'
                    ],
                    [
                        'error'     => 'The id provided does not exist'
                    ],
                ],
            ],
        ],
    ],

    'testBulkAssignReviewersToPartiallyInvalidBankingAccountList' => [
        'request'  => [
            'url'     => '/banking_accounts/reviewers',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'success'       =>  1,
                'failed'        =>  1,
                'failedItems'   =>  [
                    [
                        'id'        => 'bacc_wrongCurAccId2',
                        'error'     => 'The id provided does not exist'
                    ],
                ],
            ],
        ],
    ],

    'testCreateActivationDetail' => [
        'request'  => [
            'url'     => '/banking_accounts/activation/{id}/details',
            'method'  => 'POST',
            'content' => [
                'merchant_poc_name' => 'Sample Name',
                'merchant_poc_designation' => 'Financial Consultant',
                'merchant_poc_email' => 'sample@sample.com',
                'merchant_poc_phone_number' => '9876556789',
                'merchant_documents_address' => 'x, y, z',
                'initial_cheque_value' => 100,
                'account_type' => 'insignia',
                'merchant_city' => 'Bangalore',
                'comment' => 'abc',
                'is_documents_walkthrough_complete' => true,
                'merchant_region' => 'South',
                'expected_monthly_gmv' => 10000,
                'average_monthly_balance' => 0,
                'business_category' => 'partnership',
                'sales_team' => 'sme',
                'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN,
                'sales_poc_phone_number' => '1234554321'
                ],
        ],
        'response' => [
            'content' => [
                'merchant_poc_name' => 'Sample Name'
                ],
        ],
    ],

    'testCreateBankingAccountActivationComment' => [
        'request' => [
            'url'     => '/banking_accounts/activation/{id}/comments',
            'method'  => 'POST',
            'content' => [
                'comment'           => 'this is a comment from Ops team',
                'source_team_type'  => 'internal',
                'source_team'       => 'ops',
                'added_at'          => '1593567500',
                'type'              => 'external'
            ],
        ],
        'response' => [
            'content' => [
                'comment'           => 'this is a comment from Ops team',
                'source_team_type'  => 'internal',
                'source_team'       => 'ops',
                'added_at'          => 1593567500,
                'admin'             => [
                    'name' => 'test admin'
                ]
            ],
        ],
    ],

    'testBankLmsEndToEndCommentsCreate' => [
        'request' => [
            'url'     => '/banking_accounts/rbl/lms/activation/{id}/comments',
            'method'  => 'POST',
            'content' => [
                'comment'           => '<p>this is a comment from RBL team</p>',
                'source_team_type'  => 'external',
                'source_team'       => 'bank',
                'added_at'          => '1593567500',
                'type'              => 'external'
            ],
        ],
        'response' => [
            'content' => [
                'comment'           => '<p>this is a comment from RBL team</p>',
                'source_team_type'  => 'external',
                'source_team'       => 'bank',
                'added_at'          => 1593567500,
                'user'             => [
                    'email' => 'random@rbl.com'
                ]
            ],
        ],
    ],

    'testUpdateActivationDetail' => [
        'request'  => [
            'url'     => '/banking_accounts/activation/{id}/details',
            'method'  => 'POST',
            'content' => [
                'merchant_poc_phone_number' => '1234554321',
                'expected_monthly_gmv' => '10000',
                'business_category' => 'partnership',
                'account_type' => 'zero_balance',
                'is_documents_walkthrough_complete' => true,
                'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN,
                'rm_name' => 'Test RM',
                'rm_phone_number' => '9234567890'
            ],
        ],
        'response' => [
            'content' => [
                'merchant_poc_name' => 'Sample Name',
                'merchant_poc_phone_number' => '1234554321',
                'expected_monthly_gmv' => '10000',
                'account_type' => 'zero_balance',
                "is_documents_walkthrough_complete" => '1',
            ],
        ],
    ],

    'testAddVerificationDate' => [
        'request'  => [
            'url'     => '/banking_accounts/activation/{id}/details',
            'method'  => 'POST',
            'content' => [
                'verification_date' => 1639960752,
            ],
        ],
        'response' => [
            'content' => [
                'verification_date' => '1639960752',
            ],
        ],
    ],

    'testUpdateActivationSlotBookingDetail' => [
        'request' => [
            'url'     => '/banking_accounts/activation/{id}/details/slot_booking',
            'method'  => 'POST',
            'content' => [
                "admin_email"           => "superadmin@razorpay.com",
                "booking_date_and_time" => 1639960752,
                "additional_details"    => [
                    "booking_id" => "SRF2345"
                ]
            ],
        ],
        'response' => [
            'content' => [
                "booking_date_and_time" => '1639960752',
            ],
        ],
    ],

    'testGetSlotBookingDetails' => [
        'request' => [
            'url'     => '/booking/slot',
            'method'  => 'POST',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                "booking_date_and_time" => 1639960752,
                "booking_id"            => "SRF2345"
            ],
        ],
    ],

    'testUpdateActivationDetailIfNameUpdated' => [
        'request'  => [
            'url'     => '/banking_accounts/activation/{id}/details',
            'method'  => 'POST',
            'content' => [
                'merchant_poc_name' => 'Sample',
                'merchant_poc_phone_number' => '1234554321',
                'expected_monthly_gmv' => '10000',
                'business_category' => 'sole_proprietorship',
                'account_type' => 'zero_balance',
                'is_documents_walkthrough_complete' => true,
                'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN,
                'rm_name' => 'Test RM',
                'rm_phone_number' => '9234567890'
            ],
        ],
        'response' => [
            'content' => [
                'merchant_poc_name' => 'Sample',
                'merchant_poc_phone_number' => '1234554321',
                'expected_monthly_gmv' => '10000',
                'account_type' => 'zero_balance',
                "is_documents_walkthrough_complete" => '1',
            ],
        ],
    ],

    'testUpdateAdditionalDetailUpdated' => [
        'request'  => [
            'url'     => '/banking_accounts/activation/{id}/details',
            'method'  => 'PATCH',
            'content' => [
                'merchant_poc_name' => 'Sample',
                'merchant_poc_phone_number' => '1234554321',
                'expected_monthly_gmv' => '10000',
                'business_category' => 'sole_proprietorship',
                'account_type' => 'zero_balance',
                'is_documents_walkthrough_complete' => true,
                'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN,
                'rm_name' => 'Test RM',
                'rm_phone_number' => '9234567890',
                'additional_details' => ["green_channel" => true],
            ],
        ],
        'response' => [
            'content' => [
                'merchant_poc_name' => 'Sample',
                'merchant_poc_phone_number' => '1234554321',
                'expected_monthly_gmv' => '10000',
                'account_type' => 'zero_balance',
                "is_documents_walkthrough_complete" => '1',
                'additional_details' => ["green_channel" => true],
            ],
        ],
    ],

    'testUpdateAdditionalDetailswithDifferentValues' => [
        'request'  => [
            'url'     => '/banking_accounts/activation/{id}/details',
            'method'  => 'PATCH',
            'content' => [
                'merchant_poc_name' => 'Sample',
                'merchant_poc_phone_number' => '1234554321',
                'expected_monthly_gmv' => '10000',
                'business_category' => 'sole_proprietorship',
                'account_type' => 'zero_balance',
                'is_documents_walkthrough_complete' => true,
                'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN,
                'rm_name' => 'Test RM',
                'rm_phone_number' => '9234567890',
                'additional_details' => [
                    'api_onboarding_login_date' => '26-Jun-2020',
                    'entity_proof_documents'    => [
                        [
                            'document_type' => 'gst_certificate',
                            'file_id'       => 'test',
                        ],
                        [
                            'document_type' => 'business_pan',
                            'file_id'       => 'test',
                        ]
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'merchant_poc_name' => 'Sample',
                'merchant_poc_phone_number' => '1234554321',
                'expected_monthly_gmv' => '10000',
                'account_type' => 'zero_balance',
                "is_documents_walkthrough_complete" => '1',
                'additional_details' => [
                    'api_onboarding_login_date' => '1593109800',
                    'entity_proof_documents'    => [
                        [
                            'document_type' => 'gst_certificate',
                            'file_id'       => 'test',
                        ],
                        [
                            'document_type' => 'business_pan',
                            'file_id'       => 'test',
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testUpdateActivationDetailForNeostoneFlow' => [
        'request'  => [
            'content' => [
                'merchant_poc_phone_number' => '1234554321',
                'merchant_poc_name' => 'Sample Name',
                'expected_monthly_gmv' => '10000',
                'account_type' => 'zero_balance',
                'is_documents_walkthrough_complete' => true,
                'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN,
                'rm_name' => 'Test RM',
                'rm_phone_number' => '9234567890'
            ],
        ],
        'response' => [
            'content' => [
                'merchant_poc_name' => 'Sample Name',
                'merchant_poc_phone_number' => '1234554321',
                'expected_monthly_gmv' => '10000',
                'account_type' => 'zero_balance',
                "is_documents_walkthrough_complete" => '1',
            ],
        ],
    ],

    'testFreshDeskTicketforSalesAssistedFlowFromAdminDashboard' => [
        'request'  => [
            'content' => [
                'merchant_poc_name' => 'Sample Name',
                'merchant_poc_designation' => 'Financial Consultant',
                'merchant_poc_email' => 'sample@sample.com',
                'merchant_poc_phone_number' => '1234554321',
                'merchant_documents_address' => 'x, y, z',
                'expected_monthly_gmv' => '10000',
                'initial_cheque_value' => 100,
                'account_type' => 'insignia',
                'merchant_city' => 'Bangalore',
                'business_type' => 'ecommerce',
                'merchant_region' => 'South',
                'average_monthly_balance' => 0,
                'business_category' => 'partnership',
                'sales_team' => 'self_serve',
                'declaration_step' => 1,
                 "is_documents_walkthrough_complete" => true
            ],
        ],
        'response' => [
            'content' => [
                'merchant_poc_name' => 'Sample Name',
                'merchant_poc_phone_number' => '1234554321',
                'expected_monthly_gmv' => '10000',
                'account_type' => 'insignia',
                "is_documents_walkthrough_complete" => '1'
            ],
        ],
    ],

    'testUpdateActivationDetailForNeostoneFlowIfNameUpdated' => [
        'request'  => [
            'content' => [
                'merchant_poc_phone_number' => '1234554321',
                'merchant_poc_name' => 'Sample',
                'expected_monthly_gmv' => '10000',
                'account_type' => 'zero_balance',
                'is_documents_walkthrough_complete' => true,
                'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN,
                'rm_name' => 'Test RM',
                'rm_phone_number' => '9234567890'
            ],
        ],
        'response' => [
            'content' => [
                'merchant_poc_name' => 'Sample',
                'merchant_poc_phone_number' => '1234554321',
                'expected_monthly_gmv' => '10000',
                'account_type' => 'zero_balance',
                "is_documents_walkthrough_complete" => '1',
            ],
        ],
    ],

    'testUpdateActivationDetailWithRmNameAsVague' => [
        'request'  => [
            'url'     => '/banking_accounts/activation/{id}/details',
            'method'  => 'POST',
            'content' => [
                'merchant_poc_phone_number' => '1234554322',
                'expected_monthly_gmv' => '100000',
                'business_category' => 'partnership',
                'account_type' => 'zero_balance',
                'is_documents_walkthrough_complete' => true,
                'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN,
                'rm_name' => 'RM Not Map By BM',
                'rm_phone_number' => '9234567891'
            ],
        ],
        'response' => [
            'content' => [
                'merchant_poc_name' => 'Sample Name',
                'merchant_poc_phone_number' => '1234554322',
                'expected_monthly_gmv' => '100000',
                'account_type' => 'zero_balance',
                "is_documents_walkthrough_complete" => '1',
            ],
        ],
    ],

    'testUpdateActivationDetailWithRmNameAsVagueWithCaseInSensitiveCheck' => [
        'request'  => [
            'url'     => '/banking_accounts/activation/{id}/details',
            'method'  => 'POST',
            'content' => [
                'merchant_poc_phone_number' => '1234554322',
                'expected_monthly_gmv' => '100000',
                'business_category' => 'partnership',
                'account_type' => 'zero_balance',
                'is_documents_walkthrough_complete' => true,
                'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN,
                'rm_name' => 'rm not map',
                'rm_phone_number' => '9234567891'
            ],
        ],
        'response' => [
            'content' => [
                'merchant_poc_name' => 'Sample Name',
                'merchant_poc_phone_number' => '1234554322',
                'expected_monthly_gmv' => '100000',
                'account_type' => 'zero_balance',
                "is_documents_walkthrough_complete" => '1',
            ],
        ],
    ],

    'testUpdateActivationDetailWithRmNameAsEmpty' => [
        'request'  => [
            'url'     => '/banking_accounts/activation/{id}/details',
            'method'  => 'POST',
            'content' => [
                'merchant_poc_phone_number' => '1234554322',
                'expected_monthly_gmv' => '100000',
                'business_category' => 'partnership',
                'account_type' => 'zero_balance',
                'is_documents_walkthrough_complete' => true,
                'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN,
                'rm_name' => '',
                'rm_phone_number' => '9234567891'
            ],
        ],
        'response' => [
            'content' => [
                'merchant_poc_name' => 'Sample Name',
                'merchant_poc_phone_number' => '1234554322',
                'expected_monthly_gmv' => '100000',
                'account_type' => 'zero_balance',
                "is_documents_walkthrough_complete" => '1',
            ],
        ],
    ],

    'testCreateBankingAccountActivationCommentViaBatch' => [
        'request' => [
            'url'     => '/banking_accounts/activation/details/batch',
            'method'  => 'POST',
            'content' => [
                'comment'           => 'this is a comment from Ops team',
                'source_team_type'  => 'internal',
                'source_team'       => 'ops',
                'added_at'          => '1593567500',
                'bank_reference_number' => '',
                'channel'           => 'rbl',
                'admin_id'          => ''
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'success'
            ],
        ],
    ],

    'testUpdateActivationDetailWithRmNameAndPhoneNumber' => [
        'request'  => [
            'url'     => '/banking_accounts/activation/{id}/details',
            'method'  => 'POST',
            'content' => [
                'merchant_poc_name' => 'Sample Name',
                'merchant_poc_phone_number' => '9876543210',
                'expected_monthly_gmv' => '100000',
                'business_category' => 'partnership',
                'account_type' => 'zero_balance',
                'is_documents_walkthrough_complete' => true,
                'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN,
                'rm_name' => 'Razorpay test',
                'rm_phone_number' => '9234567891'
            ],
        ],
        'response' => [
            'content' => [
                'merchant_poc_name' => 'Sample Name',
                'merchant_poc_phone_number' => '9876543210',
                'expected_monthly_gmv' => '100000',
                'account_type' => 'zero_balance',
                "is_documents_walkthrough_complete" => '1',
                'rm_name' => 'Razorpay test',
                'rm_phone_number' => '9234567891'
            ],
        ],
    ],

    'testCreateBankingAccountActivationCommentAndUpdateStatusViaBatch' => [
        'request' => [
            'url'     => '/banking_accounts/activation/details/batch',
            'method'  => 'POST',
            'content' => [
                'comment'           => 'this is a comment from Ops team',
                'source_team_type'  => 'internal',
                'source_team'       => 'ops',
                'added_at'          => '1593567500',
                'bank_reference_number' => '',
                'channel'           => 'rbl',
                'admin_id'          => '',
                'status'            => 'Razorpay Processing'
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'success'
            ],
        ],
    ],

    'assertUpdateViaBatch' => [
        'request' => [
            'url'     => '/banking_accounts/activation/details/batch',
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
                'status' => 'success'
            ],
        ],
    ],

    'testGetBankingAccountActivationComment' => [
        'request' => [
            'url'     => '/banking_accounts/activation/{id}/comments?expand[]=admin',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'comment'           => 'this is a comment from Ops team',
                        'source_team_type'  => 'internal',
                        'source_team'       => 'ops',
                        'added_at'          => 1593567500,
                        'admin'             => [
                            'name' => 'test admin'
                        ]
                    ]
                ]
            ]
        ],
    ],

    'testGetBankingAccountActivationCallLog' => [
        'request' => [
            'url'     => '/banking_accounts/activation/{id}/call_logs?expand[]=admin',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'admin_id'  => 'RzrpySprAdmnId', 'date_and_time' => 1631008860,
                        'comment'   => [
                            'admin_id'         => 'RzrpySprAdmnId',
                            'comment'          => 'this is a comment from Ops team',
                            'source_team_type' => 'internal',
                            'source_team'      => 'ops',
                            'type'             => 'internal',
                            'added_at'         => 1631008860,
                        ],
                        'admin'     => [
                            'id' => 'admin_RzrpySprAdmnId'
                        ],
                        'state_log' => [
                        ],
                    ],
                ]
            ],
        ],
    ],

    'testGetBankingAccountActivationCallLogForMoreThanOne' => [
        'request' => [
            'url'     => '/banking_accounts/activation/{id}/call_logs?expand[]=admin',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'admin_id'  => 'RzrpySprAdmnId',
                        'comment'   => [
                            'admin_id'         => 'RzrpySprAdmnId',
                            'comment'          => 'this is a comment from Ops team',
                            'source_team_type' => 'internal',
                            'source_team'      => 'ops',
                            'type'             => 'internal',
                        ],
                        'admin'     => [
                            'id' => 'admin_RzrpySprAdmnId'
                        ],
                    ],
                    [
                        'admin_id'  => 'RzrpySprAdmnId',
                        'comment'   => [
                            'admin_id'         => 'RzrpySprAdmnId',
                            'comment'          => 'this is a comment from Ops team',
                            'source_team_type' => 'internal',
                            'source_team'      => 'ops',
                            'type'             => 'internal',
                        ],
                        'admin'     => [
                            'id' => 'admin_RzrpySprAdmnId'
                        ],
                    ],
                ]
            ],
        ],
    ],

    'testGetBankingAccountActivationCallLogForMoreThanOneForSameStatus' => [
        'request' => [
            'url'     => '/banking_accounts/activation/{id}/call_logs?expand[]=admin',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'admin_id'  => 'RzrpySprAdmnId',
                        'comment'   => [
                            'admin_id'         => 'RzrpySprAdmnId',
                            'comment'          => 'this is a comment from Ops team',
                            'source_team_type' => 'internal',
                            'source_team'      => 'ops',
                            'type'             => 'internal',
                        ],
                        'admin'     => [
                            'id' => 'admin_RzrpySprAdmnId'
                        ],
                    ],
                    [
                        'admin_id'  => 'RzrpySprAdmnId',
                        'comment'   => [
                            'admin_id'         => 'RzrpySprAdmnId',
                            'comment'          => 'this is a comment from Ops team',
                            'source_team_type' => 'internal',
                            'source_team'      => 'ops',
                            'type'             => 'internal',
                            'added_at'         => 1631008860,
                        ],
                        'admin'     => [
                            'id' => 'admin_RzrpySprAdmnId'
                        ],
                    ],
                ]
            ],
        ],
    ],

    'testUpdateBankingAccountAssignee' => [
        'request'  => [
            'url'     => '/banking_account',
            'method'  => 'PATCH',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id'                  => '10000000000000',
                'channel'                      => 'rbl',
            ],
        ],
    ],

    'assertBankingAccountFetchCommon' => [
        'request' => [
            'url'     => '/admin/banking_account',
            'method'  => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
            ],
        ],
    ],

    'testBankingAccountFetchForAssigneeBankOps' => [
        'request' => [
            'url'     => '/admin/banking_account',
            'method'  => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
            ],
        ],
    ],

    'testResolveBankingAccountActivationComment' => [
        'request' => [
            'url'     => '/admin/banking_account',
            'method'  => 'patch',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testBankingAccountExternalCommentsMIS' => [
        'request' => [
            'url'     => '/banking_accounts/activation/mis/download',
            'method'  => 'GET',
            'content' => [
                'mis_type' => 'external_comments',
                'assignee_team' => 'bank'
            ],
        ],
        'response' => [
            'content' => [

            ]
        ],
    ],

    'testBankingAccountLeadsMIS' => [
        'request' => [
            'url'     => '/banking_accounts/activation/mis/download',
            'method'  => 'GET',
            'content' => [
                'mis_type' => 'leads',
                'assignee_team' => 'bank'
            ],
        ],
        'response' => [
            'content' => [

            ]
        ],
    ],

    'testCitiesForAutoComplete' => [
        'request' => [
            'url'     => '/cities',
            'method'  => 'GET',
            'content' => [
            ]
        ],
        'response' => [
            'content' => [
            ]
        ],
    ],

    'testSpocDailyUpdates' => [
        'request' => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'url'     => '/banking_accounts/activation/spoc/daily-updates',
            'method'  => 'POST',
            'content' => [
            ]
        ],
        'response' => [
            'content' => []
        ],
    ],

    'testSendOtpToContact' => [
    'request' => [
        'server' => [
            'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
        ],
        'url'     => '/otp/send',
        'method'  => 'POST',
        'content' => [
            'action' => 'verify_contact',
            'contact_mobile' => 9999999999
        ]
    ],
    'response' => [
        'content' => [
            // 'token' => 'BUIj3m2Nx2VvVj'
        ]
    ],
    ],

    'testVerifyOtpForContact' => [
        'request' => [
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'url'     => '/banking_accounts/verify_otp/',
            'method'  => 'POST',
            'content' => [
                'otp' => '0007',
                'action' => 'verify_contact',
                'contact_mobile' => 9999999999,
                'token' => 'BUIj3m2Nx2VvVj'
            ]
        ],
        'response' => [
            'content' => [
                'channel'                      => 'rbl',
                BankingAccount\Entity::PINCODE => '560030',
                'banking_account_activation_details' => [
                    'contact_verified' => 1
                ],
            ],
          ],
      ],
    'testFetchBankingAccountForPayoutService' => [
        'request' => [
            'url'     => '/banking_accounts/',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                'status'                => 'created',
                'channel'               => 'yesbank',
                'merchant_id'           => '10000000000000',
                'account_type'          => 'shared',
                'balance_type'          => 'banking',
            ],
        ],
    ],

    'testFetchNonExistentBankingAccountForPayoutService' => [
        'request' => [
            'url'     => '/banking_accounts/',
            'method'  => 'GET',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'No db records found.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND,
        ],
    ],

    'testFetchNonExistentBankingAccountForPayoutServiceWithBalanceId' => [
        'request' => [
            'url'     => '/banking_accounts_balance_id/',
            'method'  => 'GET',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'No db records found.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND,
        ],
    ],

    'testFetchBankingAccountForPayoutServiceInvalidMerchantId' => [
        'request' => [
            'url'     => '/banking_accounts/',
            'method'  => 'GET',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The merchant id must be 14 characters.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFetchBankingAccountForPayoutServiceInvalidAccountNumber' => [
        'request' => [
            'url'     => '/banking_accounts/',
            'method'  => 'GET',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The account number must be between 5 and 40 characters.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFetchBankingAccountWithBalanceIdForPayoutService' => [
        'request'  => [
            'url'    => '/banking_accounts_balance_id/',
            'method' => 'GET',
        ],
        'response' => [
            'content'     => [
                'status'       => 'created',
                'channel'      => 'yesbank',
                'merchant_id'  => '10000000000000',
                'account_type' => 'shared',
                'balance_type' => 'banking',
            ],
            'status_code' => 200,
        ],
    ],

    'testFetchBankingAccountForPayoutServiceWithInvalidBalanceId' => [
        'request'   => [
            'url'    => '/banking_accounts/',
            'method' => 'GET',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The balance id must be 14 characters.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFetchBankingAccountBeneficiaryViaAccountNumberandIfsc' => [
        'request' => [
            'url'     => '/banking_accounts_beneficiary/',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                'beneficiary_name'           => 'ACME PVT Ltd',
            ],
        ],
    ],

    'testFetchBankingAccountBeneficiaryViaAccountNumberandInvalidIfsc' => [
        'request' => [
            'url'     => '/banking_accounts/',
            'method'  => 'GET',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The account ifsc must be 11 characters.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFetchBankingAccountBeneficiaryViaInvalidAccountNumber' => [
        'request' => [
            'url'     => '/banking_accounts/',
            'method'  => 'GET',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The account number must be between 5 and 40 characters.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testNotifyToSPOC' => [
        'request' => [
            'url'     => '/banking_accounts/send_notification',
            'method'  => 'GET',
        ],
        'response'  => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testNotifyToSPOCForMerchantPreparingDoc' => [
        'request' => [
            'url'     => '/banking_accounts/send_notification',
            'method'  => 'GET',
        ],
        'response'  => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testNotifyToSPOCForDiscrepancyInDoc' => [
        'request' => [
            'url'     => '/banking_accounts/send_notification',
            'method'  => 'GET',
        ],
        'response'  => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testBankLmsEndToEnd' => [
        'request' => [
            'url'     => '/banking_accounts/rbl/lms/banking_account',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
            ],
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [],
            ],
            'status_code' => 200,
        ],
    ],

    'testBankLmsEndToEndForFilters' => [
        'request' => [
            'url'     => '/banking_accounts/rbl/lms/banking_account',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
            ],
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [],
            ],
            'status_code' => 200,
        ],
    ],

    'testBankLmsEndToEndForFilterByBankPoc' => [
        'request' => [
            'url'     => '/banking_accounts/rbl/lms/banking_account',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
            ],
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [],
            ],
            'status_code' => 200,
        ],
    ],

    'testBankLmsEndToEndForBusinessCategory' => [
        'request' => [
            'url'     => '/banking_accounts/rbl/lms/banking_account',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
            ],
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [],
            ],
            'status_code' => 200,
        ],
    ],

    'testBankLmsEndToEndForGreenChannel' => [
        'request' => [
            'url'     => '/banking_accounts/rbl/lms/banking_account',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
            ],
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [],
            ],
            'status_code' => 200,
        ],
    ],

    'testBankLmsEndToEndForFeetOnStreet' => [
        'request' => [
            'url'     => '/banking_accounts/rbl/lms/banking_account',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
            ],
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [],
            ],
            'status_code' => 200,
        ],
    ],

    'testBankLmsEndToEndSortBySentToBankDate' => [
        'request' => [
            'url'     => '/banking_accounts/rbl/lms/banking_account',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
            ],
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [],
            ],
            'status_code' => 200,
        ],
    ],

    'testBankLmsEndToEndForLeadReceivedDateFilters' => [
        'request' => [
            'url'     => '/banking_accounts/rbl/lms/banking_account',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
            ],
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [],
            ],
            'status_code' => 200,
        ],
    ],

    'testBankLmsEndToEndForLeadReceivedDateFiltersNegativecase' => [
        'request' => [
            'url'     => '/banking_accounts/rbl/lms/banking_account',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
            ],
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'entity' => 'collection',
                'count'  => 0,
                'items'  => [],
            ],
            'status_code' => 200,
        ],
    ],

    'testBankLmsBankAccountFetchWithFromDocketEstimatedDeliveryDateFilter' => [
        'request' => [
            'url'     => '/banking_accounts/rbl/lms/banking_account',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
            ],
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [],
            ],
            'status_code' => 200,
        ],
    ],

    'testBankLmsEndToEndPartnerChangeAssignee' => [
        'request' => [
            'url' => '/banking_accounts/rbl/lms/banking_account',
            'method' => 'PATCH',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'content' => [
                'activation_detail' => [
                    'assignee_team' => 'bank',
                    'comment' => [
                        'source_team' => 'bank',
                        'added_at' => '1663065060',
                        'comment' => '<p>something</p>',
                        'source_team_type' => 'external',
                        'type' => 'external'
                    ]
                ]
            ],

        ],
        'response' => [
            'content' => [
                'banking_account_activation_details' => [
                    'assignee_team' => 'bank',
                ],
            ],
            'status_code' => 200,
        ],
    ],


    'testBankLmsEndToEndPatchLead' => [
        'request' => [
            'url'     => '/banking_accounts/rbl/lms/banking_account',
            'method'  => 'PATCH',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
            ],
            'content' => [
                'status' => 'verification_call',
                'sub_status' => 'assigned_to_pcarm',

                'activation_detail' => [

                    'account_login_date' => 1658921126,
                    'account_open_date' => 1661106600,

                    'additional_details' => [

                        'api_onboarded_date' => 1661279400,
                        'api_onboarding_login_date' => 1660242600
                    ],

                    'customer_appointment_date' => 1660761000,
                    'rm_assignment_type' => 'insignia',
                    'rm_employee_code' => '35155',
                    'rm_name' => 'Gopal Nachimuthu',
                    'rm_phone_number' => '9604549972',
                    'branch_code' => '202',

                    'doc_collection_date' => 1660847400,

                    'account_opening_ir_close_date' => 1661279400,
                    'account_opening_ftnr' => false,
                    'account_opening_ftnr_reasons' => 'AO BM/BOM Sign/Stamp/Emp_No/Approval Missing,AO Scanning Issue',

                    'api_ir_closed_date' => 1661365800,
                    'api_onboarding_ftnr' => false,
                    'api_onboarding_ftnr_reasons' => 'API BM/BOM Sign/Stamp/Emp_No/Approval Missing,API RRT/Attachment/Details Issue,API Scanning Issue',

                    'rbl_activation_details' => [
                        'account_opening_ir_number' => '1658921126',
                        'account_opening_tat_exception' => true,
                        'account_opening_tat_exception_reason' => 'XYZ',
                        'api_docs_delay_reason' => 'Lorem ipsum, dolor sit amet consectetur adipisicing elit. Labore ea magnam velit harum porro facere ducimus, aliquam sint maxime repellat, beatae unde illum nulla iste quasi consequatur ipsa delectus deserunt. Lorem ipsum dolor sit amet consectetur, adipisicing elit. Amet officia itaque adipisci cum quod ad soluta ducimus natus, repellendus maiores perferendis fuga voluptatum repudiandae expedita, facere eveniet blanditiis cupiditate vel',
                        'api_docs_received_with_ca_docs' => true,
                        'api_ir_number' => 'IR00019266582',
                        'api_onboarding_tat_exception' => true,
                        'api_onboarding_tat_exception_reason' => 'XYZ',
                        'case_login_different_locations' => true,
                        'ip_cheque_value' => 12345,
                        'ir_number' => 'IR00019266579',
                        'lead_ir_number' => 'IR00019266580',
                        'lead_referred_by_rbl_staff' => true,
                        'office_different_locations' => false,
                        'promo_code' => 'RZPAY',
                        'revised_declaration' => true,
                        'sr_number' => 'IR00019266581',
                        'upi_credential_not_done_remarks' => 'Lorem ipsum, dolor sit amet consectetur adipisicing elit. Labore ea magnam velit harum porro facere ducimus, aliquam sint maxime repellat, beatae unde illum nulla iste quasi consequatur ipsa delectus deserunt. Lorem ipsum dolor sit amet consectetur, adipisicing elit. Amet officia itaque adipisci cum quod ad soluta ducimus natus, repellendus maiores perferendis fuga voluptatum repudiandae expedita, facere eveniet blanditiis cupiditate vel'
                    ]
                ]
            ],
        ],
        'response'  => [
            'content' => [
                'account_type' => 'current',
                'status' => 'doc_collection',
                'sub_status' => 'visit_due',
                'banking_account_activation_details' => [

                    'account_login_date' => 1658921126,
                    'account_open_date' => 1661106600,

                    'customer_appointment_date' => 1660761000,
                    'rm_assignment_type' => 'insignia',
                    'rm_employee_code' => '35155',
                    'rm_name' => 'Gopal Nachimuthu',
                    'rm_phone_number' => '9604549972',
                    'branch_code' => '202',

                    'doc_collection_date' => 1660847400,
                    'doc_collection_tat' => 24,

                    'account_opening_ir_close_date' => 1661279400,
                    'account_opening_ftnr' => 0,
                    'account_opening_ftnr_reasons' => 'AO BM/BOM Sign/Stamp/Emp_No/Approval Missing,AO Scanning Issue',
                    'account_opening_tat' => 30,

                    'api_ir_closed_date' => 1661365800,
                    'api_onboarding_ftnr' => 0,
                    'api_onboarding_ftnr_reasons' => 'API BM/BOM Sign/Stamp/Emp_No/Approval Missing,API RRT/Attachment/Details Issue,API Scanning Issue',

                    'rbl_activation_details' => [
                        'account_opening_ir_number' => '1658921126',
                        'account_opening_tat_exception' => true,
                        'account_opening_tat_exception_reason' => 'XYZ',
                        'api_docs_delay_reason' => 'Lorem ipsum, dolor sit amet consectetur adipisicing elit. Labore ea magnam velit harum porro facere ducimus, aliquam sint maxime repellat, beatae unde illum nulla iste quasi consequatur ipsa delectus deserunt. Lorem ipsum dolor sit amet consectetur, adipisicing elit. Amet officia itaque adipisci cum quod ad soluta ducimus natus, repellendus maiores perferendis fuga voluptatum repudiandae expedita, facere eveniet blanditiis cupiditate vel',
                        'api_docs_received_with_ca_docs' => true,
                        'api_ir_number' => 'IR00019266582',
                        'api_onboarding_tat_exception' => true,
                        'api_onboarding_tat_exception_reason' => 'XYZ',
                        'case_login_different_locations' => true,
                        'ip_cheque_value' => 12345,
                        'ir_number' => 'IR00019266579',
                        'lead_ir_number' => 'IR00019266580',
                        'lead_referred_by_rbl_staff' => true,
                        'office_different_locations' => false,
                        'promo_code' => 'RZPAY',
                        'revised_declaration' => true,
                        'sr_number' => 'IR00019266581',
                        'upi_credential_not_done_remarks' => 'Lorem ipsum, dolor sit amet consectetur adipisicing elit. Labore ea magnam velit harum porro facere ducimus, aliquam sint maxime repellat, beatae unde illum nulla iste quasi consequatur ipsa delectus deserunt. Lorem ipsum dolor sit amet consectetur, adipisicing elit. Amet officia itaque adipisci cum quod ad soluta ducimus natus, repellendus maiores perferendis fuga voluptatum repudiandae expedita, facere eveniet blanditiis cupiditate vel'
                    ]

                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testBankLmsEndToEndForFetchById' => [
        'request'  => [
            'url'     => '/banking_accounts/rbl/lms/banking_account',
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'content' => [],
        ],
        'response' => [
            'content'     => [
                'status' => 'initiated',
                'banking_account_activation_details' => [
                ]
            ],
            'status_code' => 200,
        ],
    ],

    'testBankLmsEndToEndCommentsFetch' => [
        'request'  => [
            'url'     => '/banking_accounts/rbl/lms/activation/{id}/comments',
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'content' => [],
        ],
        'response' => [
            'content'     => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [],
            ],
            'status_code' => 200,
        ],
    ],

    'testBankLmsEndToEndActivity' => [
        'request'  => [
            'url'     => '/banking_accounts/rbl/lms/activation/{id}/activity',
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'content' => [],
        ],
        'response' => [
            'content'     => [
                'count'  => 2, // only comments for now
                'items'  => [],
            ],
            'status_code' => 200,
        ],
    ],

    'testAssignPartnerBulk' => [
        'request'  => [
            'url'     => '/banking_accounts/rbl/lms/banking_account/assign_partner',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content'     => [
                'success' => true
            ],
            'status_code' => 200,
        ],
    ],

    'testBankLmsEndToEndAfterDetachingSubMerchant' => [
        'request' => [
            'url'     => '/banking_accounts/rbl/lms/banking_account',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
            ],
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'entity' => 'collection',
                'count'  => 0,
                'items'  => [],
            ],
            'status_code' => 200,
        ],
    ],

    'testBankingAccountLeadsMISDownloadByBank' => [
        'request' => [
            'url'     => '/banking_accounts/rbl/lms/activation/mis/download',
            'method'  => 'GET',
            'content' => [
                'mis_type' => 'leads',
                'assignee_team' => 'bank'
            ],
        ],
        'response' => [
            'content' => [

            ]
        ],
    ],

    'testBankingAccountLeadsMISRequestByBank' => [
        'request' => [
            'url'     => '/banking_accounts/rbl/lms/activation/mis/send_report',
            'method'  => 'GET',
            'content' => [
                'assignee_team' => 'bank'
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'success',
                'message' => 'Report will be sent over email in a few mins.'
            ],
            'status_code' => 200,
        ],
    ],

    'testCustomerAppointmentDateOptions' => [
        'request' => [
            'url'     => '/banking_accounts/customer_appointment_dates/{city}',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                'city' => 'ghaziabad',
                'startDate' => '2023-01-21',
                'rblBankHolidays' => [
                    '2023-01-26'
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testArchiveAccount' => [
        'request' => [
            'url'      => '/banking_account/{id}/archive',
            'method'   => 'POST',
        ],
        'response'  => [
            'content'  => [],
            'status_code' => 200,
        ]
    ],

    'testArchiveICICIAndActivateRBL' => [
        'request'  => [
            'url'     => '/bas/merchant/10000000000000/banking_accounts',
            'method'  => 'POST',
            'content' => [
                RZP\Models\BankingAccountService\Constants::ACCOUNT_NUMBER => '12345678903833',
                RZP\Models\BankingAccountService\Constants::CHANNEL        => 'icici',
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testMetroPublishForBankingAccountUpdate' => [
        'request'  => [
            'url'     => '/banking_accounts_dashboard',
            'method'  => 'PATCH',
            'server' => [
                'X-Dashboard-User-Id' => '20000000000000',
            ],
            'content' => [
                'activation_detail' => [
                    'merchant_poc_name' => 'Sample Name',
                    'merchant_poc_designation' => 'Financial Consultant',
                    'merchant_poc_email' => 'sample@sample.com',
                    'merchant_poc_phone_number' => '9876556789'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                'status'      => 'created'
            ],
        ],
    ],

    'testCreateBankingAccountWithActivationDetailFromMOB' => [
        'request'  => [
            'url'     => '/banking_accounts_lms_mob',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [
                'channel' => 'rbl',
                'pincode' => '560034',
                'activation_detail' => [
                    'merchant_poc_name' => 'Sample Name',
                    'merchant_poc_designation' => 'Financial Consultant',
                    'merchant_poc_email' => 'sample@sample.com',
                    'merchant_poc_phone_number' => '9876556789',
                    'merchant_documents_address' => 'x, y, z',
                    'initial_cheque_value' => 100,
                    'account_type' => 'insignia',
                    'merchant_city' => 'Bangalore',
                    'comment' => 'abc',
                    'is_documents_walkthrough_complete' => true,
                    'merchant_region' => 'South',
                    'expected_monthly_gmv' => 10000,
                    'average_monthly_balance' => 0,
                    'business_category' => 'partnership',
                    'sales_team' => 'sme',
                    'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN,
                    'sales_poc_phone_number' => '1234554321'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                'status'      => 'created'
            ],
        ],
    ],

    'testUpdateBankingAccountWithActivationDetailFromMOB' => [
        'request'  => [
            'url'     => '/banking_accounts_lms_mob',
            'method'  => 'PATCH',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [
                'activation_detail' => [
                    'merchant_poc_name' => 'Sample Name',
                    'merchant_poc_designation' => 'Financial Consultant',
                    'merchant_poc_email' => 'sample@sample.com',
                    'merchant_poc_phone_number' => '9876556789',
                    'merchant_documents_address' => 'x, y, z',
                    'initial_cheque_value' => 100,
                    'account_type' => 'insignia',
                    'merchant_city' => 'Bangalore',
                    'is_documents_walkthrough_complete' => true,
                    'merchant_region' => 'South',
                    'expected_monthly_gmv' => 10000,
                    'average_monthly_balance' => 0,
                    'business_category' => 'partnership',
                    'sales_team' => 'sme',
                    'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN,
                    'sales_poc_phone_number' => '1234554321'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                'status'      => 'created'
            ],
        ],
    ],

    'testUpdateBankingAccountActivationDetailsViaMOB' => [
        'request'  => [
            'method'  => 'PATCH',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [
                'merchant_poc_name' => 'Sample Name',
                'merchant_poc_designation' => 'Financial Consultant',
                'merchant_poc_email' => 'sample@sample.com',
                'merchant_poc_phone_number' => '9876556789',
                'merchant_documents_address' => 'x, y, z',
                'initial_cheque_value' => 100,
                'account_type' => 'insignia',
                'merchant_city' => 'Bangalore',
                'is_documents_walkthrough_complete' => true,
                'merchant_region' => 'South',
                'expected_monthly_gmv' => 10000,
                'average_monthly_balance' => 0,
                'business_category' => 'partnership',
                'sales_team' => 'sme',
                'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN,
                'sales_poc_phone_number' => '1234554321'
            ],
        ],
        'response' => [
            'content' => [
                'merchant_poc_name' => 'Sample Name',
                'merchant_poc_designation' => 'Financial Consultant',
                'merchant_poc_email' => 'sample@sample.com',
                'merchant_poc_phone_number' => '9876556789',
                'merchant_documents_address' => 'x, y, z',
                'account_type' => 'insignia',
                'merchant_city' => 'Bangalore',
                'merchant_region' => 'South',
                'business_category' => 'partnership',
            ],
        ],
    ],

    'testSkipDwtComputeAndSave' => [
        'request'  => [
            'method'  => 'PATCH',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [
                'declaration_step' => 1
            ],
        ],
    ],

    'testUpdateBankingAccountActivationDetailsShouldMoveSubstatusToInitiateDocketIfSkipDwtExpAndDwtComplete' => [
        'request'  => [
            'url'     => '/banking_accounts_internal/activation/10000000000000/details',
            'method'  => 'PATCH',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [
                'additional_details' => [
                    'dwt_completed_timestamp' => '99999999'
                ]
            ],
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],

    'testCreateBankingAccountWithActivationDetailWithBusinessTypeAsTrust' => [
        'request'  => [
            'url'     => '/banking_accounts_admin',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [
                'channel' => 'rbl',
                'pincode' => '560034',
                'activation_detail' => [
                    'merchant_poc_name' => 'Sample Name',
                    'merchant_poc_designation' => 'Financial Consultant',
                    'merchant_poc_email' => 'sample@sample.com',
                    'merchant_poc_phone_number' => '9876556789',
                    'merchant_documents_address' => 'x, y, z',
                    'initial_cheque_value' => 100,
                    'account_type' => 'insignia',
                    'merchant_city' => 'Bangalore',
                    'comment' => 'abc',
                    'is_documents_walkthrough_complete' => true,
                    'merchant_region' => 'South',
                    'expected_monthly_gmv' => 10000,
                    'average_monthly_balance' => 0,
                    'business_category' => 'trust',
                    'sales_team' => 'capital_sme',
                    'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN,
                    'sales_poc_phone_number' => '1234554321'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                'status'      => 'created'
            ],
        ],
    ],

    'testCreateBankingAccountWithActivationDetailWithBusinessTypeAsSociety' => [
        'request'  => [
            'url'     => '/banking_accounts_admin',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [
                'channel' => 'rbl',
                'pincode' => '560034',
                'activation_detail' => [
                    'merchant_poc_name' => 'Sample Name',
                    'merchant_poc_designation' => 'Financial Consultant',
                    'merchant_poc_email' => 'sample@sample.com',
                    'merchant_poc_phone_number' => '9876556789',
                    'merchant_documents_address' => 'x, y, z',
                    'initial_cheque_value' => 100,
                    'account_type' => 'insignia',
                    'merchant_city' => 'Bangalore',
                    'comment' => 'abc',
                    'is_documents_walkthrough_complete' => true,
                    'merchant_region' => 'South',
                    'expected_monthly_gmv' => 10000,
                    'average_monthly_balance' => 0,
                    'business_category' => 'society',
                    'sales_team' => 'capital_sme',
                    'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN,
                    'sales_poc_phone_number' => '1234554321'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                'status'      => 'created'
            ],
        ],
    ],

    'testPreventFreshDeskTicketCreationForNonSalesLedFromMOB' => [
        'request'  => [
            'url'     => '/banking_accounts_dashboard',
            'method'  => 'POST',
            'content' => [
                'channel' => 'rbl',
                'pincode' => '560038',
                'activation_detail' => [
                    'business_category' => 'partnership',
                    'sales_team' => 'self_serve'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                'status'      => 'created'
            ],
        ],
    ],

    'testFreshDeskTicketCreationBehaviourForSalesLed' => [
        'request' => [
            'url'    => '/banking_accounts_internal/activation/{id}/details',
            'method' => 'PATCH',
            'content' => [],
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testGetOpsMxPocsList' => [
        'request'  => [
            'url'         => '/banking_accounts/activation/ops_mx_pocs',
            'method'      => 'GET',
        ],
        'response' => [
            'status_code' => 200,
            'success'     => 'true',
            'content'     => [
                [
                    'id'     => 'randomMxPocsId',
                    'email'  => 'nuhaid.pasha@cnx.razorpay.com',
                ],
            ],
        ],
    ],

    'testAssignOpsMxPocToBankingAccount' => [
        'request'  => [
            'url'         => '/admin/banking_account/bacc_{id}',
            'method'      => 'GET',
            'content'     => [
                'expand'      => ['opsMxPocs'],
            ],
        ],
        'response' => [
            'status_code' => 200,
            'success'     => 'true',
            'content'     => [
                'id'          => 'bacc_{id}',
                'ops_mx_pocs' => [
                    'entity'    => 'collection',
                    'count'     => 1,
                    'admin'     => true,
                    'items'     => [
                        [
                            'id'    => 'admin_randomMxPocsId',
                            'email' => 'randomcnxemail@cnx.razorpay.com',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testExcludeTerminatedAccountsBankingAccountList' => [
        'request' => [
            'url'       => '/banking_accounts',
            'method'    => 'GET'
        ],
        'response'  => [
            'status_code'   => 200,
            'content'       => [
                'count' => 0
            ]
        ]
    ],

    'testExcludeTerminatedAccountsQueryParamAdminFetch' => [
        'request' => [
            'url'       => '/admin/banking_account?exclude_status[]=created',
            'method'    => 'GET'
        ],
        'response'  => [
            'status_code'   => 200,
            'content'       => [
                'count' => 0
            ]
        ]
    ]

];
