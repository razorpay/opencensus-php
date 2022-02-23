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
                    'additional_details' => json_encode(["green_channel" => true]),
                ]
            ],
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                'status'      => 'created',
                'banking_account_activation_details' => [
                    'additional_details' => json_encode(["green_channel" => true]),
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
                'status'      => 'created'
            ],
        ],
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
                'status'      => 'created'
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
                'status'      => 'created'
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
                'status'      => 'created'
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
                'errorMessage' => null
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

    'testUpdatedStatusFromInitiatedToProcessing' => [
        'request'  => [
            'url'     => '/banking_account',
            'method'  => 'PATCH',
            'content' => [
                BankingAccount\Entity::STATUS => BankingAccount\Status::PROCESSING,
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id'                  => '10000000000000',
                'channel'                      => 'rbl',
                BankingAccount\Entity::STATUS => BankingAccount\Status::PROCESSING,
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
                'additional_details' => ["api_onboarding_login_date" => "26-Jun-2020"],
            ],
        ],
        'response' => [
            'content' => [
                'merchant_poc_name' => 'Sample',
                'merchant_poc_phone_number' => '1234554321',
                'expected_monthly_gmv' => '10000',
                'account_type' => 'zero_balance',
                "is_documents_walkthrough_complete" => '1',
                'additional_details' => ["green_channel" => false,"api_onboarding_login_date"=> '1593109800'],
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
                'is_documents_walkthrough_complete' => true,
                'merchant_region' => 'South',
                'average_monthly_balance' => 0,
                'business_category' => 'partnership',
                'sales_team' => 'self_serve',
                'declaration_step' => 1,
                 "is_documents_walkthrough_complete" => '1',
            ],
        ],
        'response' => [
            'content' => [
                'merchant_poc_name' => 'Sample Name',
                'merchant_poc_phone_number' => '1234554321',
                'expected_monthly_gmv' => '10000',
                'account_type' => 'insignia',
                "is_documents_walkthrough_complete" => '1',
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
];
