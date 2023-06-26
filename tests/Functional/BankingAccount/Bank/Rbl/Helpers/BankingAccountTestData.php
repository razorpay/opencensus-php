<?php

use RZP\Error\ErrorCode;
use RZP\Models\BankingAccount;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\BankingAccount\Gateway\Rbl as RblGateway;

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
                'count'  => 10,
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
                'success'       =>  1,
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
                'success'       =>  2,
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
                'count'  => 10,
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
                'count'  => 10,
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
                'count'  => 10,
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
                'count'  => 10,
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
                'count'  => 10,
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
                'count'  => 10,
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
                'count'  => 10,
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
                'count'  => 10,
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
                'count'  => 10,
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
                'count'  => 10,
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

    'testApiToBasDtoAdapter' => [

        'apiInput' => [
            'account_ifsc'                    => 'SBIN01234',
            'account_number'                  => '12341678786950',
            'status'                          => 'doc_collection',
            'sub_status'                      => 'in_review',
            'beneficiary_pin'                 => '110093',
            'bank_internal_status'            => 'closed',
            'pincode'                         => '110093',
            'balance_id'                      => null,
            'bank_internal_reference_number'  => '203128886',
            'beneficiary_name'                => 'Y-AXIS GROUP OF INDUSTRIES',
            'account_currency'                => 'INR',
            'beneficiary_email'               => 'SENDMAIL4ADIL@GMAIL.COM',
            'beneficiary_mobile'              => '+91(0)9560569604',
            'beneficiary_city'                => 'NEWDE',
            'beneficiary_state'               => 'DLI',
            'beneficiary_country'             => 'IN',
            'beneficiary_address1'            => 'G F PLOT NO 12 KH NO 24 19 B 557',
            'beneficiary_address2'            => 'MAIN 33 FUTA ROAD RAJIV NAGAR',
            'beneficiary_address3'            => 'MANDOLI EXTN NEAR BUDH BAZAR DELHI',
            'bank_reference_number'           => '46436',
            'account_activation_date'         => 1678786950,
            'username'                        => 'ldap_id',
            'password'                        => 'ldap_password',
            'reference1'                      => 'corp_id',
            'details' => [
                'client_id' => 'CLIENT_ID',
                'client_secret' => 'CLIENT_SECRET',
                'merchant_email' => 'MERCHANT_EMAIL',
                'merchant_password' => 'MERCHANT_PASSWORD',
            ],
            'activation_detail' => [
                'merchant_poc_name' => 'MERCHANT_POC_NAME',
                'merchant_poc_email' => 'yaxisgroupofindustries@gmail.com',
                'merchant_poc_designation' => 'Proprietor',
                'merchant_poc_phone_number' => '+919560569604',
                'merchant_documents_address' => 'B-557, G/F PLOT NO-12 KH NO-24/19, MAIN 33FUTA ROAD RAJIV NAGAR MANDOLI EXTN NEAR BUDH BAZAR, DELHI, North East Delhi, Delhi, 110093',
                'merchant_city' => 'eastdelhi',
                'merchant_region' => 'north',
                'business_category' => 'sole_proprietorship',
                'average_monthly_balance' => 20000,
                'expected_monthly_gmv' => 500000,
                'initial_cheque_value' => 20000,
                'account_type' => 'business_plus',
                'is_documents_walkthrough_complete' => 0,
                'declaration_step' => 1,
                'sales_team' => 'sme',
                'created_at' => 1678187524,
                'sales_poc_phone_number' => '8882777606',
                'sales_poc_id' => '',
                'comment' => 'mx available at lcoation',
                'assignee_team' => 'ops',
                'rm_name' => 'Pankaj Mishra',
                'rm_phone_number' => '9315383526',
                'account_open_date' => 1678732200,
                'account_login_date' => 1678386600,
                'business_name' => 'Y-AXIS GROUP OF INDUSTRIES',
                'business_type' => 'default',
                'business_pan' => 'BESPA1234K',
                'merchant_state' => 'delhi',
                'contact_verified' => 0,
                'business_pan_validation' => null,
                'booking_date_and_time' => 1678873350,
                // 'application_type' => null, // unused
                'verification_date' => null,
                'bank_poc_user_id' => 'K6yvvIv3nxpcRp',

                'additional_details' => [

                    'calendly_slot_booking_completed' => 1,
                    'booking_id' => '12341',
                    'dwt_completed_timestamp' => 1678873350,
                    'dwt_scheduled_timestamp' => 1678873350,
                    'skip_mid_office_call' => true,
                    'appointment_source' => 'sales',
                    'sent_docket_automatically' => false,
                    'reasons_to_not_send_docket' => [
                        'PoE Not Verified',
                        'Entity Name Mismatch',
                        'Entity Type Mismatch',
                        'Unexpected State Change Log',
                        'Application with Duplicate Merchant Name',
                    ],
                    'docket_not_delivered_reason' => 'Wrong Setup Form',
                    'dwt_response' => 'What type is this even??',

                    'is_documents_walkthrough_complete' => 0,
                    'sales_pitch_completed' => 1,
                    'entity_mismatch_status' => 'entity_name_mismatch',
                    'green_channel' => true,
                    'cin' => 'CIN',
                    'gstin' => '07AYMPA5163E2ZL',
                    'llpin' => '',
                    'skip_dwt' => 1,
                    'feet_on_street' => true,
                    'api_onboarded_date' => null,
                    'mid_office_poc_name' => null,
                    'docket_delivered_date' => null,
                    'docket_estimated_delivery_date' => null,
                    'docket_requested_date' => '',
                    'courier_tracking_id' => '',
                    'courier_service_name' => '',
                    'gstin_prefilled_address' => 1,
                    'api_onboarding_login_date' => null,
                    'application_initiated_from' => 'X_DASHBOARD',
                    'account_opening_webhook_date' => 1678873350,
                    'agree_to_allocated_bank_and_amb' => 1,
                    'rbl_new_onboarding_flow_declarations' => [
                        'seal_available' => 1,
                        'signboard_available' => 1,
                        'signatories_available_at_preferred_address' => 1,
                        'available_at_preferred_address_to_collect_docs' => 1
                    ],
                    'business_details' => [
                        'model' => 'Fabric, Needlework, Piece Goods, and Sewing Stores',
                        'category' => 'ECOMMERCE',
                        'sub_category' => 'fabric_and_sewing_stores'
                    ],
                    'proof_of_entity' => ['source' => 'gstin', 'status' => 'verified'],
                    'proof_of_address' => ['source' => 'gstin', 'status' => 'verified'],

                    'verified_addresses' => [
                        [
                            'source' => 'gstin',
                            'address' => 'B-557, G/F PLOT NO-12 KH NO-24/19, MAIN 33FUTA ROAD RAJIV NAGAR MANDOLI EXTN NEAR BUDH BAZAR, DELHI, North East Delhi, Delhi, 110093',
                            'addressDetails' => [
                                'address_city' => null,
                                'address_state' => null,
                                'address_country' => null,
                                'address_email_id' => null,
                                'address_landmark' => null,
                                'address_locality' => null,
                                'address_pin_code' => '110093',
                                'address_street_name' => null,
                                'address_house_number' => null,
                                'address_building_name' => null,
                                'address_contact_number' => null
                            ]
                        ]
                    ],
                    'verified_constitutions' => [
                        ['constitution' => 'PUBLIC_LIMITED', 'source' => 'gstin']
                    ],
                    'entity_proof_documents' => [
                        [
                            'file_id' => 'file_LOckRD3Auj6ksw',
                            'document_type' => 'gst_certificate'
                        ]
                    ],
                ],
                'rbl_activation_details' => [
                    'revised_declaration' => false,
                    'office_different_locations' => false,
                    'lead_referred_by_rbl_staff' => true,
                    'bank_due_date' => 1678905000,
                    'lead_ir_number' => null,
                    'bank_poc_assigned_date' => 1678352209,
                    'ip_cheque_value' => null,
                    'api_docs_delay_reason' => '',
                    'api_docs_received_with_ca_docs' => true,
                    'sr_number' => null,
                    'account_opening_ir_number' => 'IR00022515189',
                    'case_login_different_locations' => false,
                    'api_ir_number' => null,
                    'upi_credential_not_done_remarks' => null,
                    'promo_code' => 'RZPAY',
                    'aof_shared_with_mo' => false,
                    'wa_message_sent_date' => null,
                    'first_calling_time' => '5 to 6',
                    'pcarm_manager_name' => 'Avijit Shrivastava ',
                    'wa_message_response_date' => null,
                    'aof_not_shared_reason' => 'Already Login',
                    'api_onboarding_tat_exception_reason' => null,
                    'account_opening_tat_exception_reason' => 'compliance Issue',
                    'ca_beyond_tat' => false,
                    'ca_beyond_tat_dependency' => '',
                    'lead_referred_by_rbl_staff' => false,
                    'api_onboarding_tat_exception' => null,
                    'account_opening_tat_exception' => true,
                    'aof_shared_discrepancy' => '',
                    'ca_service_first_query' => '1.on rrt high risk rating by compliance is not mentioned. APPLICANT FOUND IN NEGATIVE LIST ODG452595087230310202422178-ADIL',
                ],
                'branch_code' => '281',
                'customer_appointment_date' => 1678386600,
                'rm_employee_code' => '',
                'rm_assignment_type' => 'pcarm',
                'doc_collection_date' => 1678300200,
                'account_opening_ir_close_date' => null,
                'account_opening_ftnr' => 1,
                'account_opening_ftnr_reasons' => 'AO Negative List/Compliance/Legal/CIBIL',
                'api_ir_closed_date' => null,
                'ldap_id_mail_date' => null,
                'api_onboarding_ftnr' => null,
                'api_onboarding_ftnr_reasons' => null,
                'upi_credential_received_date' => null,
                'rzp_ca_activated_date' => null,
            ]
        ],

        'expectedBasInput' => [
            'banking_account' => [
                'ifsc' => 'SBIN01234',
                'account_number' => '12341678786950',
                // 'balance_id' => null, // not added as part of update
                'account_currency' => 'INR',
                'beneficiary_details' => [
                    'name' => 'Y-AXIS GROUP OF INDUSTRIES',
                    'pincode' => '110093',
                    'email' => 'SENDMAIL4ADIL@GMAIL.COM',
                    'mobile' => '+91(0)9560569604',
                    'city' => 'NEWDE',
                    'state' => 'DLI',
                    'country' => 'IN',
                    'address1' => 'G F PLOT NO 12 KH NO 24 19 B 557',
                    'address2' => 'MAIN 33 FUTA ROAD RAJIV NAGAR',
                    'address3' => 'MANDOLI EXTN NEAR BUDH BAZAR DELHI'
                ],
                'metadata' => [
                    'bank_account_open_date' => 1678786950
                ],
            ],
            'banking_account_application' => [
                'application_status' => 'doc_collection',
                'sub_status' => 'in_review',
                'bank_status' => 'closed',
                'application_number' => '203128886',
                'application_tracking_id' => '46436',
                'average_monthly_balance' => 20000,
                'expected_monthly_gmv' => 500000,
                'metadata' => [
                    'initial_cheque_value' => 20000,
                    'declaration_step' => 1,
                    'additional_details' => [
                        'calendly_slot_booking_completed' => 1,
                        'booking_id' => '12341',
                        'booking_date_and_time' => 1678873350,
                        'dwt_completed_timestamp' => 1678873350,
                        'dwt_scheduled_timestamp' => 1678873350,
                        // 'skip_mid_office_call' => true,
                        // 'appointment_source' => 'sales',
                        // 'sent_docket_automatically' => false,
                        // 'reasons_to_not_send_docket' => [
                        //     'PoE Not Verified',
                        //     'Entity Name Mismatch',
                        //     'Entity Type Mismatch',
                        //     'Unexpected State Change Log',
                        //     'Application with Duplicate Merchant Name',
                        // ],
                        'docket_not_delivered_reason' => 'Wrong Setup Form',
                        'dwt_response' => 'What type is this even??',

                        'is_documents_walkthrough_complete' => 0,
                        'sales_pitch_completed' => 1,
                        'entity_mismatch_status' => 'entity_name_mismatch',
                        'green_channel' => true,
                        'cin' => 'CIN',
                        'gstin' => '07AYMPA5163E2ZL',
                        'llpin' => '',
                        'skip_dwt' => 1,
                        'feet_on_street' => true,
                        'api_onboarded_date' => null,
                        'mid_office_poc_name' => null,
                        'docket_delivered_date' => null,
                        'docket_estimated_delivery_date' => null,
                        'docket_requested_date' => '',
                        'courier_tracking_id' => '',
                        'courier_service_name' => '',
                        'gstin_prefilled_address' => 1,
                        'api_onboarding_login_date' => null,
                        'application_initiated_from' => 'X_DASHBOARD',
                        'account_opening_webhook_date' => 1678873350,
                        'agree_to_allocated_bank_and_amb' => 1,
                        'rbl_new_onboarding_flow_declarations' => [
                            'seal_available' => 1,
                            'signboard_available' => 1,
                            'signatories_available_at_preferred_address' => 1,
                            'available_at_preferred_address_to_collect_docs' => 1
                        ],
                        'business_details' => [
                            'model' => 'Fabric, Needlework, Piece Goods, and Sewing Stores',
                            'category' => 'ECOMMERCE',
                            'sub_category' => 'fabric_and_sewing_stores'
                        ],
                        'proof_of_entity' => ['source' => 'gstin', 'status' => 'verified'],
                        'proof_of_address' => ['source' => 'gstin', 'status' => 'verified'],
                        'verified_addresses' => [
                            [
                                'source' => 'gstin',
                                'address' => 'B-557, G/F PLOT NO-12 KH NO-24/19, MAIN 33FUTA ROAD RAJIV NAGAR MANDOLI EXTN NEAR BUDH BAZAR, DELHI, North East Delhi, Delhi, 110093',
                                'addressDetails' => [
                                    'address_city' => null,
                                    'address_state' => null,
                                    'address_country' => null,
                                    'address_email_id' => null,
                                    'address_landmark' => null,
                                    'address_locality' => null,
                                    'address_pin_code' => '110093',
                                    'address_street_name' => null,
                                    'address_house_number' => null,
                                    'address_building_name' => null,
                                    'address_contact_number' => null
                                ]
                            ]
                        ],
                        'verified_constitutions' => [
                            ['constitution' => 'PUBLIC_LIMITED', 'source' => 'gstin']
                        ]
                    ],
                    'verification_date' => null
                ],
                'bank_account_type' => 'business_plus',
                'sales_team' => 'SME',
                'assignee_team' => 'ops',
                'application_specific_fields' => [
                    'entity_proof_documents' => [
                        ['file_id' => 'file_LOckRD3Auj6ksw', 'document_type' => 'gst_certificate']
                    ]
                ]
            ],
            'business' => [
                'name' => 'Y-AXIS GROUP OF INDUSTRIES',
                'registered_address' => 'B-557, G/F PLOT NO-12 KH NO-24/19, MAIN 33FUTA ROAD RAJIV NAGAR MANDOLI EXTN NEAR BUDH BAZAR, DELHI, North East Delhi, Delhi, 110093',
                'constitution' => 'SOLE_PROPRIETORSHIP',
                'pan_number' => 'BESPA1234K',
                'industry_type' => 'default',
                'registered_address_details' => [
                    'address_pin_code' => '110093',
                    'address_city' => 'eastdelhi',
                    'address_region' => 'north',
                    'address_state' => 'delhi'
                ],
            ],
            'credentials' => [
                'auth_username' => 'ldap_id',
                'auth_password' => 'ldap_password',
                'corp_id' => 'corp_id',
                'client_id' => 'CLIENT_ID',
                'client_secret' => 'CLIENT_SECRET',
                'email' => 'MERCHANT_EMAIL',
                'dev_portal_password' => 'MERCHANT_PASSWORD'
            ],
            'person' => [
                'first_name' => 'MERCHANT_POC_NAME',
                'email_id' => 'yaxisgroupofindustries@gmail.com',
                'role_in_business' => 'Proprietor',
                'phone_number' => '+919560569604'
            ],
            'account_managers' => [
                'sales_poc' => [
                    'rzp_admin_id' => Org::SUPER_ADMIN,
                    'phone_number' => '8882777606',
                ],
                'bank_poc' => ['rzp_admin_id' => 'K6yvvIv3nxpcRp']
            ],
            'partner_bank_application' => [
                'branch_code' => '281',
                'rm_details' => [
                    'rm_name' => 'Pankaj Mishra',
                    'rm_phone_number' => '9315383526',
                    'rm_employee_code' => '',
                    'rm_assignment_type' => 'pcarm',
                    'pcarm_manager_name' => 'Avijit Shrivastava '
                ],
                'lead_details' => [
                    'office_different_locations' => false,
                    'bank_due_date' => 1678905000,
                    'lead_ir_number' => null,
                    'bank_poc_assigned_date' => 1678352209,
                    'case_login_different_locations' => false
                ],
                'doc_collection_details' => [
                    'customer_appointment_date' => 1678386600,
                    'doc_collection_date' => 1678300200,
                    'ip_cheque_value' => null,
                    'api_docs_delay_reason' => '',
                    'api_docs_received_with_ca_docs' => true,
                    // 'customer_appointment_booking_date' => 1678683839 // Not added as input
                ],
                'account_opening_details' => [
                    'account_opening_ir_close_date' => null,
                    'account_open_date' => 1678732200,
                    'account_opening_ftnr' => 1,
                    'account_opening_ftnr_reasons' => 'AO Negative List/Compliance/Legal/CIBIL',
                    'sr_number' => null,
                    'account_opening_ir_number' => 'IR00022515189',
                    'account_opening_tat_exception_reason' => 'compliance Issue'
                ],
                'api_onboarding_details' => [
                    'api_ir_closed_date' => null,
                    'ldap_id_mail_date' => null,
                    'api_onboarding_ftnr' => null,
                    'api_onboarding_ftnr_reasons' => null,
                    'api_ir_number' => null,
                    'api_onboarding_tat_exception' => null,
                    'api_onboarding_tat_exception_reason' => null
                ],
                'account_activation_details' => [
                    'upi_credential_received_date' => null,
                    'rzp_ca_activated_date' => null,
                    'upi_credential_not_done_remarks' => null
                ],
                'auxiliary_details' => [
                    'revised_declaration' => false,
                    'promo_code' => 'RZPAY',
                    'lead_referred_by_rbl_staff' => true,
                    'aof_shared_with_mo' => false,
                    'wa_message_sent_date' => null,
                    'first_calling_time' => '5 to 6',
                    'wa_message_response_date' => null,
                    'aof_not_shared_reason' => 'Already Login',
                    'ca_beyond_tat' => false,
                    'ca_beyond_tat_dependency' => '',
                    'lead_referred_by_rbl_staff' => false,
                    'aof_shared_discrepancy' => '',
                    'ca_service_first_query' => '1.on rrt high risk rating by compliance is not mentioned. APPLICANT FOUND IN NEGATIVE LIST ODG452595087230310202422178-ADIL'
                ],
            ]
        ],
    ],

    'testGetRblApplicationFromMob' => [
        'request' => [
            'url'    => '/banking_accounts_internal/bacc_JuLWj2OnFAcg72',
            'method' => 'GET',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ]
        ],
        'response' => [
            'content' => [
                'id' => 'bacc_JuLWj2OnFAcg72',
            ]
        ]
    ],

    'testGetRblApplicationFromAdminLms' => [
        'request' => [
            'url'    => '/admin_lms/banking_accounts/bacc_JuLWj2OnFAcg72',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'id' => 'bacc_JuLWj2OnFAcg72',
                'merchant_id' => '10000000000000',
                'merchant' => [
                    'id' => '10000000000000'
                ]
            ]
        ]
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

    'testGetRblApplicationFromPartnerLms' => [
        'request' => [
            'url'    => '/banking_accounts/rbl/lms/banking_account/bacc_JuLWj2OnFAcg72',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'id' => 'bacc_JuLWj2OnFAcg72',
                'merchant_id' => '10000000000000',
                'merchant_name' => 'Z-AXIS GROUP OF INDUSTRIES'
            ]
        ]
    ],

    'testFetchRblApplicationsFromAdminLms' => [
        'request' => [
            'url'    => '/admin_lms/banking_accounts',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                [
                    'id' => 'bacc_JuLWj2OnFAcg72'
                ]
            ],
        ]
    ],

    'testFetchRblApplicationsFromPartnerLms' => [
        'request' => [
            'url'    => '/banking_accounts/rbl/lms/banking_account',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 10,
                'items'  => [
                    [
                        'id' => 'bacc_JuLWj2OnFAcg72'
                    ]
                ],
            ]
        ]
    ],

    'testRblOnBasUpdateFromAdminLms' => [
        'request'  => [
            'url'     => '/banking_accounts/bacc_JuLWj2OnFAcg72',
            'method'  => 'PATCH',
            'content' => [
                RZP\Models\BankingAccount\Entity::STATUS     => RZP\Models\BankingAccount\Status::PICKED,
                RZP\Models\BankingAccount\Entity::SUB_STATUS => RZP\Models\BankingAccount\Status::NONE,
            ],
        ],
        'response' => [
            'content' => [
                RZP\Models\BankingAccount\Entity::STATUS => RZP\Models\BankingAccount\Status::PICKED,
                'channel'                      => 'rbl',
            ]
        ],
    ],

    'testRblOnBasUpdateFromMerchantDashboard' => [
        'request'  => [
            'url'     => '/banking_accounts_dashboard/bacc_{id}',
            'method'  => 'PATCH',
            'server' => [
                'X-Dashboard-User-Id' => '20000000000000',
            ],
            'content' => [
                'activation_detail' => [
                    'additional_details' => [
                        'agree_to_allocated_bank_and_amb' => 1,
                    ],
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

    'rblOnBasUpdate' => [
        'request'  => [
            'url'     => '/banking_accounts/bacc_JuLWj2OnFAcg72',
            'method'  => 'PATCH',
            'content' => [
                'status' => 'picked',
                'sub_status' => 'none',
                'activation_detail' => [
                    'additional_details' => [
                        'docket_delivered_date' => '1666204200',
                    ],
                ]
            ],
        ],
        'response' => [
            'content' => [
                'channel' => 'rbl',
                'status'  => 'picked',
                'sub_status'  => 'none',
            ],
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
    ],

    'testRblonBasAssignBankPoc' => [
        'request' => [
            'url'    => '/banking_accounts/rbl/lms/activation/bacc_JuLWj2OnFAcg72/bank_poc',
            'method' => 'PATCH',
            'content' => [
                'bank_poc_user_id' => 'abcde'
            ]
        ],
        'response' => [
            'content' => [
                'id' => 'bacc_JuLWj2OnFAcg72'
            ]
        ]
    ],

    'testRblOnBasWebhook' => [
        'request' => [
            'url'    => '/banking_accounts/internal/webhooks/account_info/rbl',
            'method' => 'POST',
            'content' => [
                'RZPAlertNotiReq' => [
                    'Header' => [ 
                        'TranID' => '220128134659', 
                        'Corp_ID' => 'RZPAY' 
                    ],
                    'Body' => [
                        'Account No.' => '4099834512998',
                        'Customer Name' => 'Umakant Vashishtha',
                        'Customer ID' => '203107174',
                        'Account Open Date' => '09-06-2023',
                        'IFSC' => 'RATN0000438',
                        'RZP_Ref No' => '26180',
                        'Address1' => 'SHOP NO 28 SHRI KRISHNA VIHAR',
                        'Address2' => 'NEAR GANESH NAGAR NIWARU ROAD',
                        'Address3' => 'JHOTWARA',
                        'CITY' => 'JAIPU',
                        'COUNTRY' => 'IN',
                        'STATE' => 'RAJ',
                        'PINCODE' => '302012',
                        'Phone no.' => '9899807189',
                        'Email Id' => 'rbl-on-bas@gmail.com'
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                RblGateway\Fields::RZP_ALERT_NOTIFICATION_RESPONSE => [
                    RblGateway\Fields::HEADER => [
                        RblGateway\Fields::TRAN_ID => '12345',
                    ],
                    RblGateway\Fields::BODY   =>[
                        RblGateway\Fields::STATUS => 'Success'
                    ]
                ],
            ]
        ]
    ],

    'testActivateRblApplication' => [
        'request' => [
            'url'    => '/banking_accounts/bacc_JuLWj2OnFAcg72/activate',
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'id' => 'bacc_JuLWj2OnFAcg72'
            ]
        ]
    ]
];
