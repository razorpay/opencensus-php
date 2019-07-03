<?php

use RZP\Error\ErrorCode;
use RZP\Models\BankingAccount;
use RZP\Error\PublicErrorCode;

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
                        'FORACID'           => '309002180853',
                        'ACCT_NAME'         => 'INTERNET BANKING CA',
                        'CIF_ID'            => 'CIF_ID',
                        'ACTIVATION_DATE'   => '22-MAY-2019',
                        'REF_NUM_1'         => '15597',
                        'IFSC'              => 'HDFC0000090',
                        'ADDR_1'            => 'RAM NAGAR',
                        'ADDR_2'            => 'ADARSHA LANE',
                        'ADDR_3'            => '.',
                        'CITY'              => 'MUMBAI',
                        'STATE'             => 'MAHARASH',
                        'COUNTRY'           => 'INDIA',
                        'PINCODE'           => '123456',
                        'PHONE_NUM'         => '9899807189',
                        'EMAIL_ID'          => 'test@gmail.com'
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

    'testUpdateAccountInfoWebhookInternally'  => [
        'request'  => [
            'url'     => '/banking_accounts/internal/webhooks/account_info/rbl',
            'method'  => 'POST',
            'content' => [
                'RZPAlertNotiReq' => [
                    'Body' => [
                        'FORACID'           => '309002180853',
                        'ACCT_NAME'         => 'INTERNET BANKING CA',
                        'CIF_ID'            => 'CIF_ID',
                        'ACTIVATION_DATE'   => '22-MAY-2019',
                        'REF_NUM_1'         => '15597',
                        'IFSC'              => 'HDFC0000090',
                        'ADDR_1'            => 'RAM NAGAR',
                        'ADDR_2'            => 'ADARSHA LANE',
                        'ADDR_3'            => '.',
                        'CITY'              => 'MUMBAI',
                        'STATE'             => 'MAHARASH',
                        'COUNTRY'           => 'INDIA',
                        'PINCODE'           => '123456',
                        'PHONE_NUM'         => '9899807189',
                        'EMAIL_ID'          => 'test@gmail.com'
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
                        'FORACID'           => '319002180853',
                        'ACCT_NAME'         => 'INTERNET BANKING CA',
                        'CIF_ID'            => 'CIF_ID',
                        'ACTIVATION_DATE'   => '22-MAY-2019',
                        'REF_NUM_1'         => '15597',
                        'IFSC'              => 'HDFC0000090',
                        'ADDR_1'            => 'RAM NAGAR',
                        'ADDR_2'            => 'ADARSHA LANE',
                        'ADDR_3'            => '.',
                        'CITY'              => 'MUMBAI',
                        'STATE'             => 'MAHARASH',
                        'COUNTRY'           => 'INDIA',
                        'PINCODE'           => '123456',
                        'PHONE_NUM'         => '9899807189',
                        'EMAIL_ID'          => 'test@gmail.com'
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

    'testStoreMerchantCredentials' => [
        'request'  => [
            'url'     => '/banking_accounts/{id}/credentials',
            'method'  => 'POST',
            'content' => [
                'subcorp_id'            => 'MERCHANT_SUB_CORP',
                'subcorp_user_id'       => 'MERCHANT_1234',
                'subcorp_user_password' => 'MERCHANT_TEST_PASSWORD'
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ]
        ],
    ],

    'testFailedBankAccountInfoNotification' => [
        'request'  => [
            'url'     => '/banking_accounts/webhooks/account_info/rbl',
            'method'  => 'POST',
            'content' => [
                'RZPAlertNotiReq' => [
                    'Body' => [
                        'FORACID'           => '309002180853',
                        'ACCT_NAME'         => 'INTERNET BANKING CA',
                        'CIF_ID'            => 'CIF_ID',
                        'ACTIVATION_DATE'   => '22-MAY-2019',
                        'REF_NUM_1'         => '15597',
                        'IFSC'              => 'HDFC0000090',
                        'ADDR_1'            => 'RAM NAGAR',
                        'ADDR_2'            => 'ADARSHA LANE',
                        'ADDR_3'            => '.',
                        'CITY'              => 'MUMBAI',
                        'STATE'             => 'MAHARASH',
                        'COUNTRY'           => 'INDIA',
                        'PINCODE'           => '123456',
                        'PHONE_NUM'         => '9899807189',
                        'EMAIL_ID'          => 'test@gmail.com'
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'RZPAlertNotiRes' => [
                    'Header' => [
                        'TranID' => null
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

    'testStoreMerchantCredentialsFailed' => [
        'request'  => [
            'url'     => '/banking_accounts/{id}/credentials',
            'method'  => 'POST',
            'content' => [
                'subcorp_id'            => 'MERCHANT_SUB_CORP',
                'subcorp_user_id'       => 'MERCHANT_1234',
                'subcorp_user_password' => 'MERCHANT_TEST_PASSWORD'
            ],
        ],
        'response' => [
            'content' => [
                'success' => false,
            ]
        ],
    ],

    'testUpdateBankingAccount' => [
        'request'  => [
            'url'     => '/banking_account',
            'method'  => 'PATCH',
            'content' => [
                BankingAccount\Entity::STATUS               => BankingAccount\Status::PROCESSED,
                BankingAccount\Entity::BANK_INTERNAL_STATUS => BankingAccount\Gateway\Rbl\Status::CLOSED
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
];
