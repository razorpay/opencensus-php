<?php

use RZP\Error\ErrorCode;
use RZP\Models\BankingAccount;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

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

    'testUpdatedStatusFromProcessingToUnserviceable' => [
        'request'  => [
            'url'     => '/banking_account',
            'method'  => 'PATCH',
            'content' => [
                BankingAccount\Entity::STATUS => BankingAccount\Status::UNSERVICEABLE,
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id'                 => '10000000000000',
                'channel'                     => 'rbl',
                BankingAccount\Entity::STATUS => BankingAccount\Status::UNSERVICEABLE,
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

    'testUpdatedStatusFromProcessingToProcessed' => [
        'request'  => [
            'url'     => '/banking_account',
            'method'  => 'PATCH',
            'content' => [
                BankingAccount\Entity::STATUS                         => BankingAccount\Status::PROCESSED,
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
                        'Status' => 'Success'
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

    'testUpdateBankingAccount' => [
        'request'  => [
            'url'     => '/banking_account',
            'method'  => 'PATCH',
            'content' => [
                BankingAccount\Entity::STATUS => BankingAccount\Status::INITIATED,
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'channel'     => 'rbl',
                BankingAccount\Entity::STATUS => BankingAccount\Status::INITIATED,
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
                    'description' => 'Status change not permitted',
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
    ]
];
