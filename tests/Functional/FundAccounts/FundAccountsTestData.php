<?php

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\BankAccount\Entity as BankAccount;
use RZP\Models\FundAccount\Entity as FundAccount;
use RZP\Models\FundAccount\Validation\Entity as Validation;

return [
    'testGetFundAccounts' => [
        'request'  => [
            'url'    => '/fund_accounts/fa_100000000000fa',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'id'           => 'fa_100000000000fa',
                'entity'       => 'fund_account',
                'active'       => true,
                'account_type' => 'bank_account',
            ],
        ],
    ],

    'testGetFundAccountForPayoutsService' => [
        'request'  => [
            'url'    => '/fund_accounts_internal/fa_100000000000fa',
            'method' => 'GET',
            'server' => [
                'HTTP_' . \RZP\Http\RequestHeader::X_RAZORPAY_ACCOUNT => '10000000000000',
            ],

        ],
        'response' => [
            'content' => [
                'id'           => 'fa_100000000000fa',
                'entity'       => 'fund_account',
                'active'       => true,
                'account_type' => 'bank_account',
            ],
        ],
    ],

    'testGetCardFundAccountForPayoutsService' => [
        'request'  => [
            'url'    => '/fund_accounts_internal/fa_100000000000fa',
            'method' => 'GET',
            'server' => [
                'HTTP_' . \RZP\Http\RequestHeader::X_RAZORPAY_ACCOUNT => '10000000000000',
            ],

        ],
        'response' => [
            'content' => [
                'id'           => 'fa_100000000000fa',
                'entity'       => 'fund_account',
                'active'       => true,
                'account_type' => 'card',
            ],
        ],
    ],

    'testFetchFundAccounts' => [
        'request'  => [
            'url'    => '/fund_accounts',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 3,
                'items'  => [
                    [
                        'id'           => 'fa_100000000003fa',
                        'entity'       => 'fund_account',
                        'active'       => true,
                        'account_type' => 'vpa',
                        'vpa'      => [
                        ],
                    ],
                    [
                        'id'           => 'fa_100000000002fa',
                        'entity'       => 'fund_account',
                        'active'       => true,
                        'account_type' => 'bank_account',
                        'bank_account'      => [
                            'ifsc'           => 'RZPB0000000',
                            'account_number' => '10010101011',
                        ],
                    ],
                    [
                        'id'           => 'fa_100000000001fa',
                        'entity'       => 'fund_account',
                        'active'       => true,
                        'account_type' => 'bank_account',
                        'bank_account'      => [
                            'ifsc'           => 'RZPB0000000',
                            'account_number' => '10010101011',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testCreateFundAccountInactiveContact' => [
        'request'   => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Fund accounts cannot be created on an inactive contact',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateFundAccountBankAccount' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111'
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateFundAccountBankAccountWithOldIfsc' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'LAVB0000791',
                    'name'           => 'Sagnik Saha',
                    'account_number' => '12345678998',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'DBSS0IN0791',
                    'name'           => 'Sagnik Saha',
                    'account_number' => '12345678998'
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateFundAccountBankAccountWithFeatureFlagEnabled' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'FINO0001023',
                    'name'           => 'Chirag C',
                    'account_number' => '111000371',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'FINO0009001',
                    'name'           => 'Chirag C',
                    'account_number' => '111000371',
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testDuplicateFundAccountCreationWithFeatureFlagEnabled' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000001contact',
                'bank_account'      => [
                    'ifsc'           => 'FINO0001023',
                    'name'           => 'Chirag Chiranjib',
                    'account_number' => '111000371',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'FINO0009001',
                    'name'           => 'Chirag C',
                    'account_number' => '111000371',
                ],
            ],
            'status_code' => 200
        ],
    ],

    'testDuplicateFundAccountCreationOfOldHashWithFeatureFlagEnabled' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111'
                ],
            ],
            'status_code' => 200
        ],
    ],

    'testDuplicateFundAccountCreationWithNoHashAndFeatureFlagEnabled' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0000011',
                    'name'           => 'Chirag C',
                    'account_number' => '111000371',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0000011',
                    'name'           => 'Chirag C',
                    'account_number' => '111000371',
                ],
            ],
            'status_code' => 200
        ],
    ],

    'testDuplicateFundAccountCreationWithDifferentContactsAndFeatureFlagEnabled' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000001contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit jain',
                    'account_number' => '111000111',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111'
                ],
            ],
            'status_code' => 200
        ],
    ],

    'testCreateFundAccountBankAccountThreeCharName' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Ann',
                    'account_number' => '111000111',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Ann',
                    'account_number' => '111000111'
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateFundAccountBankAccountWithEmptyArray' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'  => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   =>  'The bank account field is required.',
                ],
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateFundAccountBankAccountWithInvalidName' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'JsJVgnkqpMrNVab9NAZsvd5yDPsZoyO2uZ86b9F65yo84HI3PX0KfcCxss2heTOKtyta6BMJoDuioskMLkck2I3NP1EGIqOzWxABUXPz2ObPECgKj2i5VTgeHZI37',
                    'account_number' => '111000111'
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'  => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   =>  'The name must be between 3 and 120 characters.',
                ],
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateFundAccountBankAccountWithInvalidIfsc' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => '0007105',
                    'name'           => 'Amit',
                    'account_number' => '111000111'
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'  => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   =>  'The ifsc must be 11 characters.',
                ],
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateFundAccountBankAccountWithOldIfscMappedToNewIfsc' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'ORBC0101316',
                    'name'           => 'Amit M',
                    'account_number' => '111000111',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'PUNB0131610',
                    'name'           => 'Amit M',
                    'account_number' => '111000111'
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateFundAccountBankAccountWithNbsp' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'ORBC0101316',
                    'name'           => 'Tanmay Hospitality and Solution ',
                    'account_number' => '111000111',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'PUNB0131610',
                    'name'           => 'Tanmay Hospitality and Solution',
                    'account_number' => '111000111'
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateFundAccountBankAccountWithExtraSpacesAtStartAndEndOfBeneName' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => ' Razorpay Ltd. ',
                    'account_number' => '111000111',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Razorpay Ltd.',
                    'account_number' => '111000111'
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateFundAccountBankAccountWithInvalidAccountNumber' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '0'
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'  => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   =>  'The account number must be between 5 and 35 characters.',
                ],
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateFundAccountBankAccountPublic' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Jayesh',
                    'account_number' => '111000111',
                ],
            ],
            'url'     => '/fund_accounts/public',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'account_type' => "bank_account",
                'bank_account' => [
                    'ifsc' => "SBIN0007105",
                    'bank_name' =>  "State Bank of India",
                    'name' =>  "Jayesh",
                    'account_number' => "XXXXX0111",
                ]
            ],
            'status_code' => 201
        ],
    ],

    'testCreateFundAccountBankAccountBeneficiaryNotRequired' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111'
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateFundAccountBankAccountBeneficiaryFailed' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_invalidcontact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_invalidcontact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111'
                ],
            ],
        ],
    ],

    'testCreateVpa' => [
        'request'  => [
            'content' => [
                'account_type' => 'vpa',
                'contact_id'   => 'cont_1000000contact',
                'vpa'      => [
                    'address' => 'amitm@upi',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'vpa',
                'contact_id'   => 'cont_1000000contact',
                'vpa'      => [
                    'address' => 'amitm@upi',
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateVpaWithNewRegex' => [
        'request'  => [
            'content' => [
                'account_type' => 'vpa',
                'contact_id'   => 'cont_1000000contact',
                'vpa'      => [
                    'address' => '50100177856195@HDFC0000041.ifsc.npci',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'vpa',
                'contact_id'   => 'cont_1000000contact',
                'vpa'      => [
                    'address' => '50100177856195@HDFC0000041.ifsc.npci',
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateWalletAccountFundAccount' => [
        'request'  => [
            'content' => [
                'account_type'  => 'wallet',
                'contact_id'    => 'cont_1000000contact',
                'wallet'  => [
                    'provider' => 'amazonpay',
                    'phone'    => '+918124632237',
                    'email'    => 'test@gmail.com',
                    'name'     => 'test',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'wallet',
                'contact_id'   => 'cont_1000000contact',
                'wallet'  => [
                    'provider' => 'amazonpay',
                    'phone'    => '+918124632237',
                    'email'    => 'test@gmail.com',
                    'name'     => 'test',
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateWalletAccountFundAccountWithIncorrectAccountType' => [
        'request'  => [
            'content' => [
                'account_type'  => 'wallet',
                'contact_id'    => 'cont_1000000contact',
                'vpa' => [
                    'address' => 'chirag@upi'
                ]
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Account type doesn\'t match the details provided',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateFundAccountWithIncorrectAccountType' => [
        'request'  => [
            'content' => [
                'account_type'  => 'bank_account',
                'contact_id'    => 'cont_1000000contact',
                'card'         => [
                    'name'         => 'shk',
                    'number'       => '4111111111111111',
                    'expiry_month' => 4,
                    'expiry_year'  => 2025
                ]
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Account type doesn\'t match the details provided',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateWalletAccountFundAccountPhoneNull' => [
        'request'  => [
            'content' => [
                'account_type'  => 'wallet',
                'contact_id'    => 'cont_1000000contact',
                'wallet'  => [
                    'provider' => 'amazonpay',
                    'phone'    => null,
                    'email'    => 'test@gmail.com',
                    'name'     => 'test',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The phone field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateWalletAccountFundAccountPhoneEmpty' => [
        'request'  => [
            'content' => [
                'account_type'  => 'wallet',
                'contact_id'    => 'cont_1000000contact',
                'wallet'  => [
                    'provider' => 'amazonpay',
                    'phone'    => '',
                    'email'    => 'test@gmail.com',
                    'name'     => 'test',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The phone field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateWalletAccountFundAccountModeCapital' => [
        'request'  => [
            'content' => [
                'account_type'  => 'wallet',
                'contact_id'    => 'cont_1000000contact',
                'wallet'  => [
                    'provider' => 'AMAZONPAY',
                    'phone'    => '+918124632237',
                    'email'    => 'test@gmail.com',
                    'name'     => 'test',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The selected provider is invalid.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateWalletAccountFundAccountForNonwhitelistedMerchant' => [
        'request'  => [
            'content' => [
                'account_type'  => 'wallet',
                'contact_id'    => 'cont_1000000contact',
                'wallet'  => [
                    'provider' => 'amazonpay',
                    'phone'    => '+919999999999',
                    'email'    => 'test@gmail.com',
                    'name'     => 'test',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Creating a Fund Account of wallet type is not permitted',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_WALLET_ACCOUNT_FUND_ACCOUNT_CREATION_NOT_PERMITTED,
        ],
    ],

    'testCreateWalletAccountFundAccountPhoneFormat1' => [
        'request'  => [
            'content' => [
                'account_type'  => 'wallet',
                'contact_id'    => 'cont_1000000contact',
                'wallet'  => [
                    'provider' => 'amazonpay',
                    'phone'    => '+91-8124632237',
                    'email'    => 'test@gmail.com',
                    'name'     => 'test',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'wallet',
                'contact_id'   => 'cont_1000000contact',
                'wallet'  => [
                    'provider' => 'amazonpay',
                    'phone'    => '+918124632237',
                    'email'    => 'test@gmail.com',
                    'name'     => 'test',
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateWalletAccountFundAccountPhoneFormat2' => [
        'request'  => [
            'content' => [
                'account_type'  => 'wallet',
                'contact_id'    => 'cont_1000000contact',
                'wallet'  => [
                    'provider' => 'amazonpay',
                    'phone'    => '08124632237',
                    'email'    => 'test@gmail.com',
                    'name'     => 'test',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'wallet',
                'contact_id'   => 'cont_1000000contact',
                'wallet'  => [
                    'provider' => 'amazonpay',
                    'phone'    => '+918124632237',
                    'email'    => 'test@gmail.com',
                    'name'     => 'test',
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateWalletAccountFundAccountPhoneFormat3' => [
        'request'  => [
            'content' => [
                'account_type'  => 'wallet',
                'contact_id'    => 'cont_1000000contact',
                'wallet'  => [
                    'provider' => 'amazonpay',
                    'phone'    => '8124632237',
                    'email'    => 'test@gmail.com',
                    'name'     => 'test',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'wallet',
                'contact_id'   => 'cont_1000000contact',
                'wallet'  => [
                    'provider' => 'amazonpay',
                    'phone'    => '+918124632237',
                    'email'    => 'test@gmail.com',
                    'name'     => 'test',
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateCard' => [
        'request'  => [
            'content' => [
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card' => [
                    'name' => 'shk',
                    'number' => '4111111111111111',
                    'expiry_month' => 4,
                    'expiry_year' => 2025
                ]
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card'      => [
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateRuPayCard' => [
        'request'  => [
            'content' => [
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card' => [
                    'name' => 'shk',
                    'number' => '6521591827203121',
                    'expiry_month' => 4,
                    'expiry_year' => 2025
                ]
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card'      => [
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateCardBeneficiaryVerified' => [
        'request'  => [
            'content' => [
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card' => [
                    'name' => 'shk',
                    'number' => '4111111111111111',
                    'expiry_month' => 4,
                    'expiry_year' => 2025
                ]
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card'      => [
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateCardBeneficiaryFailed' => [
        'request'  => [
            'content' => [
                'account_type' => 'card',
                'contact_id'   => 'cont_invalidcontact',
                'card' => [
                    'name' => 'shk',
                    'number' => '4111111111111111',
                    'expiry_month' => 4,
                    'expiry_year' => 2025
                ]
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'card',
                'contact_id'   => 'cont_invalidcontact',
                'card'      => [
                ],
            ],
            'status_code' => 201
        ],
    ],


    'testCreateCardAndVpa' => [
        'request'  => [
            'content' => [
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card' => [
                    'name' => 'shk',
                    'number' => '4111111111111111',
                    'expiry_month' => 4,
                    'expiry_year' => 2025
                ],
                'vpa' => [
                    'address' => 'mehulk@upi'
                ]
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Only one of card, vpa, bank_account or wallet can be present'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateWithoutContactOrCustomer' => [
        'request'   => [
            'content' => [
                'account_type' => 'vpa',
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The contact id field is required when customer id is not present.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateFundAccountInvalidVpa' => [
        'request'   => [
            'content' => [
                'account_type' => 'vpa',
                'contact_id'   => 'cont_1000000contact',
                'vpa'      => [
                    'address' => 'amitm',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid VPA. Please enter a valid Virtual Payment Address',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
        ],
    ],

    'testCreateFundAccountInvalidBankIfsc' => [
        'request'   => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIQ0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111'
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid IFSC Code in Bank Account',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateFromCustomer' => [
        'request'  => [
            'content' => [
                'account_type' => 'vpa',
                'customer_id'  => 'cust_1000facustomer',
                'vpa'      => [
                    'address' => 'amitm@upi',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'vpa',
                'customer_id'  => 'cust_1000facustomer',
                'vpa'      => [
                    'address' => 'amitm@upi',
                ],
            ],
        ],
    ],

    'testUpdateFundAccount' => [
        'request'  => [
            'content' => [
                'active' => '0'
            ],
            'url'     => '/fund_accounts/fa_100000000000fa',
            'method'  => 'PATCH'
        ],
        'response' => [
            'content' => [
                'id'     => 'fa_100000000000fa',
                'entity' => 'fund_account',
                'active' => false
            ],
        ]
    ],

    'testInternalContactUpdateFailsForProxyAuth' => [
        'request'   => [
            'content' => [
                'active' => false,
            ],
            'url'     => '/fund_accounts/fa_100000000000fa',
            'method'  => 'PATCH'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Updating an internal Razorpay Fund Account is not permitted',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNAL_FUND_ACCOUNT_UPDATE_NOT_PERMITTED,
        ],
    ],

    'testInternalContactUpdateAllowedForInternalAuth' => [
        'request'   => [
            'server' => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'content' => [
                'active' => '0',
            ],
            'url'     => '/fund_accounts_internal/fa_100000000000fa',
            'method'  => 'PATCH'
        ],
        'response' => [
            'content' => [
                "active" => false
            ],
        ]
    ],

    'testInternalContactUpdateFailsForRZPFees' => [
        'request'   => [
            'server' => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'content' => [
                'active' => '0',
            ],
            'url'     => '/fund_accounts_internal/fa_100000000000fa',
            'method'  => 'PATCH'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Updating an internal Razorpay Fund Account is not permitted',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNAL_FUND_ACCOUNT_UPDATE_NOT_PERMITTED,
        ],
    ],

    'testDeleteFundAccount' => [
        'request'  => [
            'url'    => '/fund_accounts/fa_100000000000fa',
            'method' => 'DELETE'
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The requested URL was not found on the server.',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testBulkFundAccount' => [
        'request'   => [
            'url'     => '/fund_accounts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => 'Sample rzp1',
                        'account_IFSC'      => 'SBIN0007106',
                        'account_number'    => '1234567890',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp1',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'abc123',
                        'place'             => 'Bangalore',
                        'state'             => 'Karnataka'
                    ],
                    'idempotency_key'       => 'batch_abc123'
                ],
                [
                    'fund'  => [
                        'account_type'      => 'vpa',
                        'account_name'      => 'Sample rzp2',
                        'account_IFSC'      => '',
                        'account_number'    => '',
                        'account_vpa'       => '123@ybl'
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp2',
                        'email'             => '',
                        'mobile'            => '',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => '',
                        'place'             => '',
                        'state'             => ''
                    ],
                    'idempotency_key'       => 'batch_abc124'
                ],
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => 'Sample rzp3',
                        'account_IFSC'      => 'HDFC0003780',
                        'account_number'    => '1234567891',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp3',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'xyz123',
                        'place'             => 'Hyderabad',
                        'state'             => 'Telengana'
                    ],
                    'idempotency_key'       => 'batch_abc125'
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'entity' => 'collection',
                'count'  => 3,
                'items'  => [
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'bank_account',
                        'bank_account'          => [
                            'ifsc'              => 'SBIN0007106',
                            'bank_name'         => 'State Bank of India',
                            'name'              => 'Sample rzp1',
                            'account_number'    => '1234567890',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc123'
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'vpa',
                        'vpa'                   => [
                            'address'           => '123@ybl',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc124'
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'bank_account',
                        'bank_account'          => [
                            'ifsc'              => 'HDFC0003780',
                            'bank_name'         => 'HDFC Bank',
                            'name'              => 'Sample rzp3',
                            'account_number'    => '1234567891',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc125'
                    ]
                ]
            ],
        ],
    ],

    'testBulkFundAccountWithOldIfsc' => [
        'request'   => [
            'url'     => '/fund_accounts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => 'Sample rzp1',
                        'account_IFSC'      => 'SBIN0007106',
                        'account_number'    => '1234567890',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp1',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'abc123',
                        'place'             => 'Bangalore',
                        'state'             => 'Karnataka'
                    ],
                    'idempotency_key'       => 'batch_abc123'
                ],
                [
                    'fund'  => [
                        'account_type'      => 'vpa',
                        'account_name'      => 'Sample rzp2',
                        'account_IFSC'      => '',
                        'account_number'    => '',
                        'account_vpa'       => '123@ybl'
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp2',
                        'email'             => '',
                        'mobile'            => '',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => '',
                        'place'             => '',
                        'state'             => ''
                    ],
                    'idempotency_key'       => 'batch_abc124'
                ],
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => 'Sample rzp3',
                        'account_IFSC'      => 'CORP0000100',
                        'account_number'    => '1234567891',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp3',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'xyz123',
                        'place'             => 'Hyderabad',
                        'state'             => 'Telengana'
                    ],
                    'idempotency_key'       => 'batch_abc125'
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'entity' => 'collection',
                'count'  => 3,
                'items'  => [
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'bank_account',
                        'bank_account'          => [
                            'ifsc'              => 'SBIN0007106',
                            'bank_name'         => 'State Bank of India',
                            'name'              => 'Sample rzp1',
                            'account_number'    => '1234567890',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc123'
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'vpa',
                        'vpa'                   => [
                            'address'           => '123@ybl',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc124'
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'bank_account',
                        'bank_account'          => [
                            'ifsc'              => 'UBIN0901008',
                            'bank_name'         => 'Union Bank of India',
                            'name'              => 'Sample rzp3',
                            'account_number'    => '1234567891',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc125'
                    ]
                ]
            ],
        ],
    ],

    'testBulkFundAccountWithInvalidContactId' => [
        'request'   => [
            'url'     => '/fund_accounts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => 'Sample rzp1',
                        'account_IFSC'      => 'SBIN0007106',
                        'account_number'    => '1234567890',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'id'                => 'invalid',
                        'type'              => 'vendor',
                        'name'              => 'Test rzp1',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'abc123',
                        'place'             => 'Bangalore',
                        'state'             => 'Karnataka'
                    ],
                    'idempotency_key'       => 'batch_abc123'
                ],
                [
                    'fund'  => [
                        'account_type'      => 'vpa',
                        'account_name'      => 'Sample rzp2',
                        'account_IFSC'      => '',
                        'account_number'    => '',
                        'account_vpa'       => '123@ybl'
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp2',
                        'email'             => '',
                        'mobile'            => '',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => '',
                        'place'             => '',
                        'state'             => ''
                    ],
                    'idempotency_key'       => 'batch_abc124'
                ],
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => 'Sample rzp3',
                        'account_IFSC'      => 'HDFC0003780',
                        'account_number'    => '1234567891',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp3',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'xyz123',
                        'place'             => 'Hyderabad',
                        'state'             => 'Telengana'
                    ],
                    'idempotency_key'       => 'batch_abc125'
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'entity' => 'collection',
                'count'  => 3,
                'items'  => [
                    [
                        'http_status_code'      => 400,
                        'error'                 => [
                            'description'       => 'The id provided does not exist',
                            'code'              => 'BAD_REQUEST_ERROR'
                        ],
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'vpa',
                        'vpa'                   => [
                            'address'           => '123@ybl',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc124'
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'bank_account',
                        'bank_account'          => [
                            'ifsc'              => 'HDFC0003780',
                            'bank_name'         => 'HDFC Bank',
                            'name'              => 'Sample rzp3',
                            'account_number'    => '1234567891',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc125'
                    ]
                ]
            ],
        ],
    ],

    'testBulkFundAccountWithValidContactId' => [
        'request'   => [
            'url'     => '/fund_accounts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => 'Sample rzp1',
                        'account_IFSC'      => 'SBIN0007106',
                        'account_number'    => '1234567890',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'id'                => 'cont_1000001contact',
                        'type'              => 'vendor',
                        'name'              => 'Test rzp1',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'abc123',
                        'place'             => 'Bangalore',
                        'state'             => 'Karnataka'
                    ],
                    'idempotency_key'       => 'batch_abc123',
                    'contact_id'            => 'cont_1000001contact'
                ],
                [
                    'fund'  => [
                        'account_type'      => 'vpa',
                        'account_name'      => 'Sample rzp2',
                        'account_IFSC'      => '',
                        'account_number'    => '',
                        'account_vpa'       => '123@ybl'
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp2',
                        'email'             => '',
                        'mobile'            => '',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => '',
                        'place'             => '',
                        'state'             => ''
                    ],
                    'idempotency_key'       => 'batch_abc124'
                ],
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => 'Sample rzp3',
                        'account_IFSC'      => 'HDFC0003780',
                        'account_number'    => '1234567891',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp3',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'xyz123',
                        'place'             => 'Hyderabad',
                        'state'             => 'Telengana'
                    ],
                    'idempotency_key'       => 'batch_abc125'
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'entity' => 'collection',
                'count'  => 3,
                'items'  => [
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'bank_account',
                        'bank_account'          => [
                            'ifsc'              => 'SBIN0007106',
                            'bank_name'         => 'State Bank of India',
                            'name'              => 'Sample rzp1',
                            'account_number'    => '1234567890',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc123'
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'vpa',
                        'vpa'                   => [
                            'address'           => '123@ybl',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc124'
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'bank_account',
                        'bank_account'          => [
                            'ifsc'              => 'HDFC0003780',
                            'bank_name'         => 'HDFC Bank',
                            'name'              => 'Sample rzp3',
                            'account_number'    => '1234567891',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc125'
                    ]
                ]
            ],
        ],
    ],

    'testBulkFundAccountWithPrivateAuthFailed' => [
        'request'   => [
            'url'     => '/fund_accounts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => 'Sample rzp1',
                        'account_IFSC'      => 'SBIN0007106',
                        'account_number'    => '1234567890',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'id'                => 'cont_1000001contact',
                        'type'              => 'vendor',
                        'name'              => 'Test rzp1',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'idempotency_key'       => 'batch_abc123',
                    'contact_id'            => 'cont_1000001contact'
                ],
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The requested URL was not found on the server.',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testBulkFundAccountWithSameContact' => [
        'request'   => [
            'url'     => '/fund_accounts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => 'Sample rzp1',
                        'account_IFSC'      => 'SBIN0007106',
                        'account_number'    => '1234567890',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'type'              => 'customer',
                        'name'              => 'Test rzp1',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'abc123',
                        'place'             => 'Bangalore',
                        'state'             => 'Karnataka'
                    ],
                    'idempotency_key'       => 'batch_abc123',
                ],
                [
                    'fund'  => [
                        'account_type'      => 'vpa',
                        'account_name'      => 'Sample rzp2',
                        'account_IFSC'      => '',
                        'account_number'    => '',
                        'account_vpa'       => '123@ybl'
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp2',
                        'email'             => '',
                        'mobile'            => '',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => '',
                        'place'             => '',
                        'state'             => ''
                    ],
                    'idempotency_key'       => 'batch_abc124'
                ],
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => 'Sample rzp2',
                        'account_IFSC'      => 'SBIN0007106',
                        'account_number'    => '1234567890',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'type'              => 'customer',
                        'name'              => 'Test rzp1',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'xyz123',
                        'place'             => 'Hyderabad',
                        'state'             => 'Telengana'
                    ],
                    'idempotency_key'       => 'batch_abc125'
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'entity' => 'collection',
                'count'  => 3,
                'items'  => [
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'bank_account',
                        'bank_account'          => [
                            'ifsc'              => 'SBIN0007106',
                            'bank_name'         => 'State Bank of India',
                            'name'              => 'Sample rzp1',
                            'account_number'    => '1234567890',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc123'
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'vpa',
                         'vpa'                   => [
                            'address'           => '123@ybl',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc124'
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'bank_account',
                        'bank_account'          => [
                            'ifsc'              => 'SBIN0007106',
                            'bank_name'         => 'State Bank of India',
                            'name'              => 'Sample rzp2',
                            'account_number'    => '1234567890',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc125'
                    ]
                ]
            ],
        ],
    ],
    'testBulkFundAccountWithSameFundAccount' => [
        'request'   => [
            'url'     => '/fund_accounts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => 'Sample rzp1',
                        'account_IFSC'      => 'SBIN0007106',
                        'account_number'    => '1234567890',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'type'              => 'customer',
                        'name'              => 'Test rzp1',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'abc123',
                        'place'             => 'Bangalore',
                        'state'             => 'Karnataka'
                    ],
                    'idempotency_key'       => 'batch_abc123',
                ],
                [
                    'fund'  => [
                        'account_type'      => 'vpa',
                        'account_name'      => 'Sample rzp2',
                        'account_IFSC'      => '',
                        'account_number'    => '',
                        'account_vpa'       => '123@ybl'
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp2',
                        'email'             => '',
                        'mobile'            => '',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => '',
                        'place'             => '',
                        'state'             => ''
                    ],
                    'idempotency_key'       => 'batch_abc124'
                ],
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => 'Sample rzp1',
                        'account_IFSC'      => 'SBIN0007106',
                        'account_number'    => '1234567890',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'type'              => 'customer',
                        'name'              => 'Test rzp1',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'xyz123',
                        'place'             => 'Hyderabad',
                        'state'             => 'Telengana'
                    ],
                    'idempotency_key'       => 'batch_abc123'
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'entity' => 'collection',
                'count'  => 3,
                'items'  => [
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'bank_account',
                        'bank_account'          => [
                            'ifsc'              => 'SBIN0007106',
                            'bank_name'         => 'State Bank of India',
                            'name'              => 'Sample rzp1',
                            'account_number'    => '1234567890',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc123'
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'vpa',
                        'vpa'                   => [
                            'address'           => '123@ybl',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc124'
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'bank_account',
                        'bank_account'          => [
                            'ifsc'              => 'SBIN0007106',
                            'bank_name'         => 'State Bank of India',
                            'name'              => 'Sample rzp1',
                            'account_number'    => '1234567890',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc123'
                    ]
                ]
            ],
        ],
    ],
    'testBulkFundAccountWithSameIdempotencyKey' => [
        'request'   => [
            'url'     => '/fund_accounts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => 'Sample rzp1',
                        'account_IFSC'      => 'SBIN0007106',
                        'account_number'    => '1234567890',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp1',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'abc123',
                        'place'             => 'Bangalore',
                        'state'             => 'Karnataka'
                    ],
                    'idempotency_key'       => 'batch_abc123'
                ],
                [
                    'fund'  => [
                        'account_type'      => 'vpa',
                        'account_name'      => 'Sample rzp2',
                        'account_IFSC'      => '',
                        'account_number'    => '',
                        'account_vpa'       => '123@ybl'
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp2',
                        'email'             => '',
                        'mobile'            => '',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => '',
                        'place'             => '',
                        'state'             => ''
                    ],
                    'idempotency_key'       => 'batch_abc124'
                ],
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => 'Sample rzp3',
                        'account_IFSC'      => 'HDFC0003780',
                        'account_number'    => '1234567891',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp3',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'xyz123',
                        'place'             => 'Hyderabad',
                        'state'             => 'Telengana'
                    ],
                    'idempotency_key'       => 'batch_abc123'
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'entity' => 'collection',
                'count'  => 3,
                'items'  => [
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'bank_account',
                        'bank_account'          => [
                            'ifsc'              => 'SBIN0007106',
                            'bank_name'         => 'State Bank of India',
                            'name'              => 'Sample rzp1',
                            'account_number'    => '1234567890',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc123'
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'vpa',
                        'vpa'                   => [
                            'address'           => '123@ybl',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc124'
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'bank_account',
                        'bank_account'          => [
                            'ifsc'              => 'SBIN0007106',
                            'bank_name'         => 'State Bank of India',
                            'name'              => 'Sample rzp1',
                            'account_number'    => '1234567890',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc123'
                    ]
                ]
            ],
        ],
    ],

    'testDuplicateFundAccountCreationOnApiForBankAccount' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111',
                     ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
            ],
            'status_code' => 200
        ],
    ],

    'testCreateSingleCharacterHandleOfVpa' => [
        'request'  => [
            'content' => [
                'account_type' => 'vpa',
                'contact_id'   => 'cont_1000000contact',
                'vpa'      => [
                    'address' => 'a@upi',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'vpa',
                'contact_id'   => 'cont_1000000contact',
                'vpa'      => [
                    'address' => 'a@upi',
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testDuplicateFundAccountCreationOnApiForVpa' => [
        'request'  => [
            'content' => [
                'account_type' => 'vpa',
                'contact_id'   => 'cont_1000000contact',
                'vpa'      => [
                    'address' => 'a.mitm@upi',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'vpa',
                'contact_id'   => 'cont_1000000contact',
                'vpa'      => [
                    'address' => 'a.mitm@upi',
                ],
            ],
            'status_code' => 200
        ]
    ],

    'testCreateVpaWithDot' => [
        'request'  => [
            'content' => [
                'account_type' => 'vpa',
                'contact_id'   => 'cont_1000000contact',
                'vpa'      => [
                    'address' => 'a.mitm@upi',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'vpa',
                'contact_id'   => 'cont_1000000contact',
                'vpa'      => [
                    'address' => 'a.mitm@upi',
                ],
            ],
            'status_code' => 201
        ]
    ],

    'testDuplicateFundAccountCreationOnDashboardForVpa' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
            ],
            'status_code' => 201
        ],
    ],

    'testDuplicateFundAccountCreationOnDashboardForBankAccount' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
            ],
            'status_code' => 200
        ],
    ],

    'testFundAccountDuplicatesForDifferentContacts' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
    ],

    'testBulkFundAccountForMerchantBehindRazorx' => [
        'request'   => [
            'url'     => '/fund_accounts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => 'Sample rzp1',
                        'account_IFSC'      => 'SBIN0007106',
                        'account_number'    => '1234567890',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp1',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'abc123',
                        'place'             => 'Bangalore',
                        'state'             => 'Karnataka'
                    ],
                    'idempotency_key'       => 'batch_abc123'
                ],
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => 'Sample rzp1',
                        'account_IFSC'      => 'SBIN0007106',
                        'account_number'    => '1234567890',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp1',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => '',
                        'place'             => '',
                        'state'             => ''
                    ],
                    'idempotency_key'       => 'batch_abc124'
                ],
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => 'Sample rzp3',
                        'account_IFSC'      => 'HDFC0003780',
                        'account_number'    => '1234567891',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp3',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'xyz123',
                        'place'             => 'Hyderabad',
                        'state'             => 'Telengana'
                    ],
                    'idempotency_key'       => 'batch_abc125'
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'entity' => 'collection',
                'count'  => 3,
                'items'  => [
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'bank_account',
                        'bank_account'          => [
                            'ifsc'              => 'SBIN0007106',
                            'bank_name'         => 'State Bank of India',
                            'name'              => 'Sample rzp1',
                            'account_number'    => '1234567890',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc123'
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'bank_account',
                        'bank_account'          => [
                            'ifsc'              => 'SBIN0007106',
                            'bank_name'         => 'State Bank of India',
                            'name'              => 'Sample rzp1',
                            'account_number'    => '1234567890',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc124'
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'bank_account',
                        'bank_account'          => [
                            'ifsc'              => 'HDFC0003780',
                            'bank_name'         => 'HDFC Bank',
                            'name'              => 'Sample rzp3',
                            'account_number'    => '1234567891',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc125'
                    ]
                ]
            ],
        ],
    ],


    'testFundAccountsWithExpiredKey' => [
        'request'  => [],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_API_KEY_EXPIRED,
                ],
            ],
            'status_code' => 401,
        ]
    ],


    'testCreateFundAccountInvalidAccountType' => [
        'request'   => [
            'content' => [
                'account_type' => 'random',
                'contact_id'   => 'cont_1000000contact',
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Not a valid account type: random',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateFundAccountFromInactiveCustomer' => [
        'request'  => [
            'content' => [
                'account_type' => 'vpa',
                'customer_id'  => 'cust_1000facustomer',
                'vpa'      => [
                    'address' => 'mehulk@upi',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Fund accounts cannot be created on an inactive customer',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateCardFundAccountFeatureS2SNotEnabled' => [
        'request'   => [
            'content' => [
                'account_type' => 'card',
                'contact_id'  => 'cont_1000000contact',
                'card'      => [
                    'number' => '1234432112344321',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'card is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateCardFundAccountFeaturePayoutToCardsNotEnabled' => [
        'request'   => [
            'content' => [
                'account_type' => 'card',
                'contact_id'  => 'cont_1000000contact',
                'card'      => [
                    'number' => '1234432112344321',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'card is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateCardFundAccountWithNameAsNumeric' => [
        'request'   => [
            'content'  => [
                'account_type'  => 'card',
                'contact_id'    => 'cont_1000000contact',
                'card'  => [
                    'name'  => 998,
                    'number'  => '1234432112344321',
                ],
            ],
            'url'   => '/fund_accounts',
            'method'   => 'POST'
        ],
        'response'  => [
            'content'   => [
                'error' => [
                    'code'  => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'The card.name format is invalid.',
                ],
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateCardFundAccountWithNameAsAlphanumeric' => [
        'request'  => [
            'content' => [
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card' => [
                    'name' => 'shk g7799',
                    'number' => '4111111111111111',
                    'expiry_month' => 4,
                    'expiry_year' => 2025
                ]
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'   => [
                'error' => [
                    'code'  => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'The card.name format is invalid.',
                ],
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateNonSavedCardFundAccount' => [
        'request'  => [
            'content' => [
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card'         => [
                    'name'       => 'chirag',
                    'number'     => '4111111111111111',
                    'input_type' => 'card'
                ]
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content'     => [
                'entity'       => 'fund_account',
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card'         => [
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateNonSavedCardFundAccountWithInvalidVaultTokenGenerated' => [
        'request'   => [
            'content' => [
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card'         => [
                    'name'       => 'chirag',
                    'number'     => '4111111111111111',
                    'input_type' => 'card'
                ]
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Card not supported for fund account creation',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_CARD_NOT_SUPPORTED_FOR_FUND_ACCOUNT,
        ],
    ],

    'testCreateRzpSavedCardFundAccount' => [
        'request'  => [
            'content' => [
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card'         => [
                    'token_id'       => 'token_100000000token',
                    'input_type'     => 'razorpay_token',
                    'token_provider' => 'razorpay'
                ]
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content'     => [
                'entity'       => 'fund_account',
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card'         => [
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateRzpSavedCardFundAccountWithTokenEntityOfDifferentMid' => [
        'request'  => [
            'content' => [
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card'         => [
                    'token_id'       => 'token_100000000token',
                    'input_type'     => 'razorpay_token',
                    'token_provider' => 'razorpay'
                ]
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Token not supported for fund account creation.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_CARD_NOT_SUPPORTED_FOR_FUND_ACCOUNT,
        ],
    ],

    'testCreateSavedCardOtherTSPFundAccount' => [
        'request'  => [
            'content' => [
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card'         => [
                    'number'         => '4610151724696781',
                    'expiry_month'   => 8,
                    'expiry_year'    => 2025,
                    'input_type'     => 'service_provider_token',
                    'token_provider' => 'xyz'
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content'     => [
                'entity'       => 'fund_account',
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card'         => [
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateSavedCardOtherTSPFundAccountWithInvalidVaultTokenAssociated' => [
        'request'   => [
            'content' => [
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card'         => [
                    'name'           => 'chirag',
                    'number'         => '4610151724696781',
                    'expiry_month'   => 11,
                    'expiry_year'    => 2024,
                    'input_type'     => 'service_provider_token',
                    'token_provider' => 'xyz'
                ]
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Card not supported for fund account creation',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_CARD_NOT_SUPPORTED_FOR_FUND_ACCOUNT,
        ],
    ],

    'testCreateSavedCardOtherTSPFundAccountWithInvalidTokenPan' => [
        'request'   => [
            'content' => [
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card'         => [
                    'name'           => 'chirag',
                    'number'         => '4111111111111111',
                    'expiry_month'   => 11,
                    'expiry_year'    => 2024,
                    'input_type'     => 'service_provider_token',
                    'token_provider' => 'xyz'
                ]
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Token not supported for fund account creation.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_CARD_NOT_SUPPORTED_FOR_FUND_ACCOUNT,
        ],
    ],

    'testCreateCardFundAccountWithSpecialCharName' => [
        'request'  => [
            'content' => [
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card' => [
                    'name' => 'Mr. asd fg',
                    'number' => '4111111111111111',
                    'expiry_month' => 4,
                    'expiry_year' => 2025
                ]
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card'      => [
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateFundAccountWithVariousInputTypeValidation' => [
        'request'   => [
            'content' => [
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card'         => [
                ]
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'   => [
                'error' => [
                    'code'  => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => '',
                ],
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testBulkFundAccountCard' => [
        'request'   => [
            'url'     => '/contacts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'fund'  => [
                        'account_type'      => 'card',
                        'account_number'    => '1234567890',
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp1',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'abc123',
                        'place'             => 'Bangalore',
                        'state'             => 'Karnataka'
                    ],
                    'idempotency_key'       => 'batch_abc123'
                ],
                [
                    'fund'  => [
                        'account_type'      => 'vpa',
                        'account_name'      => 'Sample rzp2',
                        'account_IFSC'      => '',
                        'account_number'    => '',
                        'account_vpa'       => '123@ybl'
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp2',
                        'email'             => '',
                        'mobile'            => '',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => '',
                        'place'             => '',
                        'state'             => ''
                    ],
                    'idempotency_key'       => 'batch_abc124'
                ],
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => 'Sample rzp3',
                        'account_IFSC'      => 'HDFC0003780',
                        'account_number'    => '1234567891',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp3',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'xyz123',
                        'place'             => 'Hyderabad',
                        'state'             => 'Telengana'
                    ],
                    'idempotency_key'       => 'batch_abc125'
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'entity' => 'collection',
                'count'  => 3,
                'items'  => [
                    [
                        'idempotency_key' =>  'batch_abc123',
                        'http_status_code' => 400,
                        'error' => [
                            'description' => 'Invalid value for fund account type - card',
                            'code' => 'BAD_REQUEST_ERROR',
                        ]
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'vpa',
                        'vpa'                   => [
                            'address'           => '123@ybl',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc124'
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'bank_account',
                        'bank_account'          => [
                            'ifsc'              => 'HDFC0003780',
                            'bank_name'         => 'HDFC Bank',
                            'name'              => 'Sample rzp3',
                            'account_number'    => '1234567891',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc125'
                    ]
                ]
            ],
        ],
    ],

    'testBulkFundAccountAmazonPay' => [
        'request'   => [
            'url'     => '/contacts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'fund'  => [
                        'account_type'          => 'wallet',
                        'account_phone_number'  => '+919988998897',
                        'account_email'         => 'sample@sample.com',
                        'account_provider'      => 'amazonpay'
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp3',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'xyz123',
                        'place'             => 'Hyderabad',
                        'state'             => 'Telengana'
                    ],
                    'idempotency_key'       => 'batch_abc123'
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'wallet',
                        'wallet'                   => [
                            'phone'           => '+919988998897',
                            'provider'        => 'amazonpay',
                            'email'           => 'sample@sample.com'
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc123'
                    ],
                ]
            ],
        ],
    ],

    'testBulkFundAccountAmazonPayWithoutProvider' => [
        'request'   => [
            'url'     => '/contacts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'fund'  => [
                        'account_type'      => 'wallet',
                        'account_phone_number'     => '+919988998897',
                        'account_email'     => 'sample@sample.com',
                        'account_provider'  => ''
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp3',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'xyz123',
                        'place'             => 'Hyderabad',
                        'state'             => 'Telengana'
                    ],
                    'idempotency_key'       => 'batch_abc123'
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'idempotency_key' =>  'batch_abc123',
                        'http_status_code' => 400,
                        'error' => [
                            'description' => 'Wallet provider is not supported',
                            'code' => 'BAD_REQUEST_ERROR',
                        ]
                    ],
                ]
            ],
        ],
    ],

    'testBulkFundAccountAmazonPayWithoutEmail' => [
        'request'   => [
            'url'     => '/contacts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'fund'  => [
                        'account_type'             => 'wallet',
                        'account_phone_number'     => '+919988998897',
                        'account_email'            => '',
                        'account_provider'         => 'amazonpay'
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp3',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'xyz123',
                        'place'             => 'Hyderabad',
                        'state'             => 'Telengana'
                    ],
                    'idempotency_key'       => 'batch_abc123'
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'wallet',
                        'wallet'                   => [
                            'phone'           => '+919988998897',
                            'provider'        => 'amazonpay'
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc123'
                    ],
                ]
            ],
        ],
    ],

    'testBulkFundAccountAmazonPayMerchantDisabled' => [
        'request'   => [
            'url'     => '/contacts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'fund'  => [
                        'account_type'          => 'wallet',
                        'account_phone_number'  => '+919988998897',
                        'account_email'         => 'sample@sample.com',
                        'account_provider'      => 'amazonpay'
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp3',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'xyz123',
                        'place'             => 'Hyderabad',
                        'state'             => 'Telengana'
                    ],
                    'idempotency_key'       => 'batch_abc123'
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'idempotency_key'  =>  'batch_abc123',
                        'http_status_code' => 400,
                        'error'            => [
                            'description'  => 'Creating a Fund Account of wallet type is not permitted',
                            'code'         => 'BAD_REQUEST_ERROR',
                        ]
                    ],
                ]
            ],
        ],
    ],

    'testBulkFundAccountWithoutName' => [
        'request'   => [
            'url'     => '/contacts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => '',
                        'account_IFSC'      => 'SBIN0007106',
                        'account_number'    => '1234567890',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp1',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'abc123',
                        'place'             => 'Bangalore',
                        'state'             => 'Karnataka'
                    ],
                    'idempotency_key'       => 'batch_abc123'
                ],
                [
                    'fund'  => [
                        'account_type'      => 'vpa',
                        'account_name'      => 'Sample rzp2',
                        'account_IFSC'      => '',
                        'account_number'    => '',
                        'account_vpa'       => '123@ybl'
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp2',
                        'email'             => '',
                        'mobile'            => '',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => '',
                        'place'             => '',
                        'state'             => ''
                    ],
                    'idempotency_key'       => 'batch_abc124'
                ],
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => 'Sample rzp3',
                        'account_IFSC'      => 'HDFC0003780',
                        'account_number'    => '1234567891',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp3',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'xyz123',
                        'place'             => 'Hyderabad',
                        'state'             => 'Telengana'
                    ],
                    'idempotency_key'       => 'batch_abc125'
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'entity' => 'collection',
                'count'  => 3,
                'items'  => [
                    [
                        'idempotency_key' =>  'batch_abc123',
                        'http_status_code' => 400,
                        'error' => [
                            'description' => 'The name field is required.',
                            'code' => 'BAD_REQUEST_ERROR',
                        ]
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'vpa',
                        'vpa'                   => [
                            'address'           => '123@ybl',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc124'
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'bank_account',
                        'bank_account'          => [
                            'ifsc'              => 'HDFC0003780',
                            'bank_name'         => 'HDFC Bank',
                            'name'              => 'Sample rzp3',
                            'account_number'    => '1234567891',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc125'
                    ]
                ]
            ],
        ],
    ],

    'testBulkFundAccountWithInvalidBankAccountNumber' => [
        'request'   => [
            'url'     => '/contacts/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => '',
                        'account_IFSC'      => 'SBIN0007106',
                        'account_number'    => '1234567890@12aa1',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp1',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'abc123',
                        'place'             => 'Bangalore',
                        'state'             => 'Karnataka'
                    ],
                    'idempotency_key'       => 'batch_abc123'
                ],
                [
                    'fund'  => [
                        'account_type'      => 'vpa',
                        'account_name'      => 'Sample rzp2',
                        'account_IFSC'      => '',
                        'account_number'    => '',
                        'account_vpa'       => '123@ybl'
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp2',
                        'email'             => '',
                        'mobile'            => '',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => '',
                        'place'             => '',
                        'state'             => ''
                    ],
                    'idempotency_key'       => 'batch_abc124'
                ],
                [
                    'fund'  => [
                        'account_type'      => 'bank_account',
                        'account_name'      => 'Sample rzp3',
                        'account_IFSC'      => 'HDFC0003780',
                        'account_number'    => '1234567891',
                        'account_vpa'       => ''
                    ],
                    'contact'  => [
                        'id'                => '',
                        'type'              => 'customer',
                        'name'              => 'Test rzp3',
                        'email'             => 'sample@example.com',
                        'mobile'            => '9988998897',
                        'reference_id'      => ''
                    ],
                    'notes'  => [
                        'code'              => 'xyz123',
                        'place'             => 'Hyderabad',
                        'state'             => 'Telengana'
                    ],
                    'idempotency_key'       => 'batch_abc125'
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'entity' => 'collection',
                'count'  => 3,
                'items'  => [
                    [
                        'idempotency_key' =>  'batch_abc123',
                        'http_status_code' => 400,
                        'error' => [
                            'description' => 'The account number format is invalid.',
                            'code' => 'BAD_REQUEST_ERROR',
                        ]
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'vpa',
                        'vpa'                   => [
                            'address'           => '123@ybl',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc124'
                    ],
                    [
                        'entity'                => 'fund_account',
                        'account_type'          => 'bank_account',
                        'bank_account'          => [
                            'ifsc'              => 'HDFC0003780',
                            'bank_name'         => 'HDFC Bank',
                            'name'              => 'Sample rzp3',
                            'account_number'    => '1234567891',
                        ],
                        'active'                => true,
                        'idempotency_key'       => 'batch_abc125'
                    ],
                ],
            ],
        ],
    ],

    'testCreateFundAccountForRZPFeesContact' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INTERNAL_FUND_ACCOUNT_CREATION_NOT_PERMITTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNAL_FUND_ACCOUNT_CREATION_NOT_PERMITTED,
        ],
    ],

    'testUpdateFundAccountForRZPFeesContact' => [
        'request'  => [
            'content' => [
                'active' => '0'
            ],
            'method'  => 'PATCH'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INTERNAL_FUND_ACCOUNT_UPDATE_NOT_PERMITTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNAL_FUND_ACCOUNT_UPDATE_NOT_PERMITTED,
        ],
    ],

    'testDuplicateFundAccountCreationOnDashboardForBankAccountWithLowerCaseIfsc' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'sbIn0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
            ],
            'status_code' => 200
        ],
    ],

    'testCreateFundAccountCardWithEmptyArray' => [
        'request'  => [
            'content' => [
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card'      => [],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'  => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   =>  'The card field is required.',
                ],
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateFundAccountVpaWithEmptyArray' => [
        'request'  => [
            'content' => [
                'account_type' => 'vpa',
                'contact_id'   => 'cont_1000000contact',
                'vpa'      => [],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'  => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   =>  'The vpa field is required.',
                ],
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFetchFundAccountsWithContactIdIfFundAccountsExist' => [
        'request'  => [
            'url'    => '/fund_accounts',
            'method' => 'GET'
        ],
        'response' => [
            'content'     => [
                'items' => [
                    [
                        'entity'       => 'fund_account',
                        'contact_id'   => 'cont_1000000contact',
                        'account_type' => 'bank_account',
                    ],

                    [
                        'entity'       => 'fund_account',
                        'contact_id'   => 'cont_1000000contact',
                        'account_type' => 'bank_account',
                    ],
                ],
            ],
            'status_code' => 200
        ],
    ],

    'testFetchFundAccountsWithContactIdIfFundAccountDoesNotExist' => [
        'request'  => [
            'url'    => '/fund_accounts',
            'method' => 'GET'
        ],
        'response' => [
            'content'     => [
                'items' => [],
            ],
            'status_code' => 200
        ],
    ],

    'testFetchFundAccountsWithFundAccountIdIfFundAccountExists' => [
        'request'  => [
            'url'    => '/fund_accounts/{id}',
            'method' => 'GET'
        ],
        'response' => [
            'content'     => [
                'entity'       => 'fund_account',
                'contact_id'   => 'cont_1000000contact',
                'account_type' => 'bank_account',
            ],
            'status_code' => 200
        ],
    ],

    'testFetchFundAccountsWithFundAccountIdIfFundAccountDoesNotExist' => [
        'request'   => [
            'url'    => '/fund_accounts/{id}',
            'method' => 'GET'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testCreateFundAccountWithBankAccountAndUnnecessarySpacesTrimmedInNameAndNumber' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M ',
                    'account_number' => "111000111\n",
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111'
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateFundAccountWithBankAccountAndUnnecessarySpacesTrimmedInNameAndNumberAndProxyAuth' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M ',
                    'account_number' => "111000111\n",
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111'
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateFundAccountBankAccountWithEmptyArrayNewApiError' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'The bank account field is required.',
                    'reason'        => 'input_validation_failed',
                    'source'        => 'business',
                    'step'          => null,
                    'metadata'      => []
                ],
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateFundAccountBankAccountWithEmptyArrayNewApiErrorOnLiveMode' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'The bank account field is required.',
                    'reason'        => 'input_validation_failed',
                    'source'        => 'business',
                    'step'          => null,
                    'metadata'      => []
                ],
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateFundAccountBankAccountWithInvalidNameNewApiError' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'JsJVgnkqpMrNVab9NAZsvd5yDPsZoyO2uZ86b9F65yo84HI3PX0KfcCxss2heTOKtyta6BMJoDuioskMLkck2I3NP1EGIqOzWxABUXPz2ObPECgKj2i5VTgeHZI37',
                    'account_number' => '111000111'
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'  => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   =>  'The name must be between 3 and 120 characters.',
                    'reason'        => 'input_validation_failed',
                    'source'        => 'business',
                    'step'          => null,
                    'metadata'      => []
                ],
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateFundAccountBankAccountWithInvalidNameNewApiErrorOnLiveMode' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'JsJVgnkqpMrNVab9NAZsvd5yDPsZoyO2uZ86b9F65yo84HI3PX0KfcCxss2heTOKtyta6BMJoDuioskMLkck2I3NP1EGIqOzWxABUXPz2ObPECgKj2i5VTgeHZI37',
                    'account_number' => '111000111'
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'  => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   =>  'The name must be between 3 and 120 characters.',
                    'reason'        => 'input_validation_failed',
                    'source'        => 'business',
                    'step'          => null,
                    'metadata'      => []
                ],
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateFundAccountInvalidVpaArray' => [
        'request'   => [
            'content' => [
                'account_type' => 'vpa',
                'contact_id'   => 'cont_1000000contact',
                'vpa'          => 'vpa',
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'vpa must be an object',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateFundAccountInvalidCardArray' => [
        'request'   => [
            'content' => [
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card'         => 'card'
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'card must be an object',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateFundAccountInvalidBankAccountArray' => [
        'request'   => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account' => 'bank_account'
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'bank_account must be an object',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateFundAccountDebitCard' => [
        'request'  => [
            'content' => [
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card' => [
                    'name' => 'Mr. A B',
                    'number' => '340169570990137',
                    'cvv' => '123',
                    'expiry_month' => 8,
                    'expiry_year' => 2025
                ]
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card'      => [
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateFundAccountDebitCardWithoutSupportedModes' => [
        'request'  => [
            'content' => [
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card' => [
                    'name' => 'Mr. A B',
                    'number' => '340169570990137',
                    'cvv' => '123',
                    'expiry_month' => 8,
                    'expiry_year' => 2025
                ]
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Card not supported for fund account creation',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_CARD_NOT_SUPPORTED_FOR_FUND_ACCOUNT,
        ],
    ],

    'testCreateFundAccountSCBLCardwithMastercard' => [
        'request'  => [
            'content' => [
                "account_type" => "card",
                "contact_id"   => "cont_1000000contact",
                "card"         => [
                    "name"         => "Prashanth YV",
                    "number"       => "6521618738419536",
                    "cvv"          => "212",
                    "expiry_month" => 10,
                    "expiry_year"  => 29,
                ]
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card'      => [
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateFundAccountSCBLCardwithVisa' => [
        'request'  => [
            'content' => [
                "account_type" => "card",
                "contact_id"   => "cont_1000000contact",
                "card"         => [
                    "name"         => "Prashanth YV",
                    "number"       => "6521618738419536",
                    "cvv"          => "212",
                    "expiry_month" => 10,
                    "expiry_year"  => 29,
                ]
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response'  => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'card',
                'contact_id'   => 'cont_1000000contact',
                'card'      => [
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateBankAccountFundAccountWithAllowedSpecialCharacters' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account' => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit- &M',
                    'account_number' => '111000111',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content'     => [
                'entity'       => 'fund_account',
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account' => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit- &M',
                    'account_number' => '111000111'
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateDuplicateBankAccountFundAccountWithExtraSpacesInName' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account' => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit-    &M',
                    'account_number' => '111000111',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content'     => [
                'entity'       => 'fund_account',
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account' => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit- &M',
                    'account_number' => '111000111'
                ],
            ],
            'status_code' => 200
        ],
    ],

    'testUpdationOfExistingDuplicateFundAccountWithHash' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111'
                ],
            ],
            'status_code' => 200
        ],
    ],

    'testTaxPaymentFundAccountCreationSuccessAndVerifyHashCreation' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/fund_accounts_internal',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'content' => [
                'account_type' => 'bank_account',
                'bank_account' => [
                    'name'           => 'test name',
                    'ifsc'           => 'ICIC0000020',
                    'account_number' => '000205031288'
                ],
            ],
        ],
        'response' => [
            'content'     => [
                'entity'       => 'fund_account',
                'contact_id'   => '',
                'account_type' => 'bank_account',
                'bank_account' => [
                    'ifsc'           => 'ICIC0000020',
                    'bank_name'      => 'ICICI Bank',
                    'name'           => 'test name',
                    'notes'          => [],
                    'account_number' => '000205031288',
                ]
            ],
            'status_code' => 201,
        ],
    ],

    'createValidationWithFundAccountEntity' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                Validation::FUND_ACCOUNT  => [
                    FundAccount::ACCOUNT_TYPE => 'bank_account',
                    FundAccount::DETAILS      => [
                        BankAccount::ACCOUNT_NUMBER => '123456789',
                        BankAccount::NAME           => 'Rohit Keshwani',
                        BankAccount::IFSC           => 'SBIN0010411',
                    ],
                ],
                Validation::AMOUNT        => '100',
                Validation::CURRENCY      => 'INR',
                Validation::NOTES         => []
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account.validation',
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'account_type' => 'bank_account',
                    'active'       => true,
                    'details'      => [
                        'account_number' => '123456789',
                        'name'           => 'Rohit Keshwani',
                        'ifsc'           => 'SBIN0010411',
                        'bank_name'      => 'State Bank of India',
                    ],
                ],
                'status'       => 'created',
                'amount'       => 100,
                'currency'     => 'INR',
                'notes'        => [],
                'results'      => [
                    'account_status'  => null,
                    'registered_name' => null,
                ],
            ],
        ],
    ],

    'testUpdationOfExistingDuplicateVpaFundAccountWithHashWithDifferentCaseInInputAndDuplicate' => [
        'request'  => [
            'content' => [
                'account_type' => 'vpa',
                'contact_id'   => 'cont_1000000contact',
                'vpa'      => [
                    'address' => 'aMitm@upI',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'vpa',
                'contact_id'   => 'cont_1000000contact',
                'vpa'      => [
                    'address' => 'amitm@upi',
                ],
            ],
            'status_code' => 200
        ],
    ],

    'testFetchFundAccountsDashboardRequestMerchantDisabledForAmazonPay' => [
        'request'  => [
            'url'    => '/fund_accounts',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'entity'            => 'fund_account',
                        'active'            => true,
                        'account_type'      => 'wallet',
                        'merchant_disabled' => true,
                        'wallet'            => [
                            'provider' => 'amazonpay',
                            'phone'    => '+918124632237',
                            'email'    => 'test@gmail.com',
                        ],
                    ],
                    [
                        'entity'            => 'fund_account',
                        'active'            => true,
                        'account_type'      => 'bank_account',
                        'merchant_disabled' => false,
                        'bank_account'      => [
                            'ifsc'           => 'SBIN0007105',
                            'name'           => 'Amit M',
                            'account_number' => '111000111',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchFundAccountsDashboardRequestMerchantEnabledForAmazonPay' => [
        'request'  => [
            'url'    => '/fund_accounts',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'entity'            => 'fund_account',
                        'active'            => true,
                        'account_type'      => 'wallet',
                        'merchant_disabled' => false,
                        'wallet'            => [
                            'provider' => 'amazonpay',
                            'phone'    => '+918124632237',
                            'email'    => 'test@gmail.com',
                        ],
                    ],
                    [
                        'entity'            => 'fund_account',
                        'active'            => true,
                        'account_type'      => 'bank_account',
                        'merchant_disabled' => false,
                        'bank_account'      => [
                            'ifsc'           => 'SBIN0007105',
                            'name'           => 'Amit M',
                            'account_number' => '111000111',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchFundAccountsApiRequestNoMerchantDisabledField' => [
        'request'  => [
            'url'    => '/fund_accounts',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'entity'            => 'fund_account',
                        'active'            => true,
                        'account_type'      => 'wallet',
                        'wallet'            => [
                            'provider' => 'amazonpay',
                            'phone'    => '+918124632237',
                            'email'    => 'test@gmail.com',
                        ],
                    ],
                    [
                        'entity'            => 'fund_account',
                        'active'            => true,
                        'account_type'      => 'bank_account',
                        'bank_account'      => [
                            'ifsc'           => 'SBIN0007105',
                            'name'           => 'Amit M',
                            'account_number' => '111000111',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testCreateFundAccountWalletDashboardRequestMerchantDisabledField' => [
        'request'  => [
            'content' => [
                'account_type'  => 'wallet',
                'contact_id'    => 'cont_1000000contact',
                'wallet'  => [
                    'provider' => 'amazonpay',
                    'phone'    => '+918124632237',
                    'email'    => 'test@gmail.com',
                    'name'     => 'test',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'            => 'fund_account',
                'account_type'      => 'wallet',
                'contact_id'        => 'cont_1000000contact',
                'merchant_disabled' => false,
                'wallet'  => [
                    'provider' => 'amazonpay',
                    'phone'    => '+918124632237',
                    'email'    => 'test@gmail.com',
                    'name'     => 'test',
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateFundAccountBankAccountDashboardRequestMerchantDisabledField' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account' => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'            => 'fund_account',
                'account_type'      => 'bank_account',
                'contact_id'        => 'cont_1000000contact',
                'merchant_disabled' => false,
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111'
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateFundAccountWalletApiRequestNoMerchantDisabledField' => [
        'request'  => [
            'content' => [
                'account_type'  => 'wallet',
                'contact_id'    => 'cont_1000000contact',
                'wallet'  => [
                    'provider' => 'amazonpay',
                    'phone'    => '+918124632237',
                    'email'    => 'test@gmail.com',
                    'name'     => 'test',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'            => 'fund_account',
                'account_type'      => 'wallet',
                'contact_id'        => 'cont_1000000contact',
                'wallet'  => [
                    'provider' => 'amazonpay',
                    'phone'    => '+918124632237',
                    'email'    => 'test@gmail.com',
                    'name'     => 'test',
                ],
            ],
            'status_code' => 201
        ],
    ],

    'testCreateFundAccountBankAccountApiRequestNoMerchantDisabledField' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account' => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'            => 'fund_account',
                'account_type'      => 'bank_account',
                'contact_id'        => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111'
                ],
            ],
            'status_code' => 201
        ],
    ],
    'testFundAccountDetailsPushedToQueue' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/fund_accounts',
            'content' => [
                'account_type' => 'wallet',
                'contact_id'   => 'cont_1000000contact',
                'wallet'       => [
                    'phone'         => '+919988776655',
                    'provider'      => 'amazonpay',
                    'email'         => 'test@gmail.com',
                ],
            ]
        ],
        'response' => [
            'content' => [
                'entity'            => 'fund_account',
                'account_type' => 'wallet',
                'contact_id'   => 'cont_1000000contact',
                'wallet'       => [
                    'phone'         => '+919988776655',
                    'provider'      => 'amazonpay',
                    'email'         => 'test@gmail.com',
                ],
            ],
            'status_code' => 201
        ]
    ],

    'testCapitalCollectionsInternalContactFundAccountCreation' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/fund_accounts_internal',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => '',
                'bank_account' => [
                    'name'           => 'test name',
                    'ifsc'           => 'ICIC0000020',
                    'account_number' => '000205031288'
                ],
            ],
        ],
        'response' => [
            'content'     => [
                'entity'       => 'fund_account',
                'contact_id'   => '',
                'account_type' => 'bank_account',
                'bank_account' => [
                    'ifsc'           => 'ICIC0000020',
                    'bank_name'      => 'ICICI Bank',
                    'name'           => 'test name',
                    'notes'          => [],
                    'account_number' => '000205031288',
                ]
            ],
            'status_code' => 201,
        ],
    ],

    'testCapitalCollectionsInternalContactFundAccountCreationByOtherInternalAppFailure' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/fund_accounts_internal',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => '',
                'bank_account' => [
                    'name'           => 'test name',
                    'ifsc'           => 'ICIC0000020',
                    'account_number' => '000205031288'
                ],
            ],
        ],

        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Creating a fund account for an Internal Razopay contact is not permitted',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNAL_FUND_ACCOUNT_CREATION_NOT_PERMITTED,
        ],
    ],

    'testXpayrollInternalContactFundAccountCreation' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/fund_accounts_internal',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => '',
                'bank_account' => [
                    'name'           => 'test name',
                    'ifsc'           => 'ICIC0000020',
                    'account_number' => '000205031288'
                ],
            ],
        ],
        'response' => [
            'content'     => [
                'entity'       => 'fund_account',
                'contact_id'   => '',
                'account_type' => 'bank_account',
                'bank_account' => [
                    'ifsc'           => 'ICIC0000020',
                    'bank_name'      => 'ICICI Bank',
                    'name'           => 'test name',
                    'notes'          => [],
                    'account_number' => '000205031288',
                ]
            ],
            'status_code' => 201,
        ],
    ],

    'testXpayrollInternalContactFundAccountCreationByOtherInternalAppFailure' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/fund_accounts_internal',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => '',
                'bank_account' => [
                    'name'           => 'test name',
                    'ifsc'           => 'ICIC0000020',
                    'account_number' => '000205031288'
                ],
            ],
        ],

        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Creating a fund account for an Internal Razopay contact is not permitted',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNAL_FUND_ACCOUNT_CREATION_NOT_PERMITTED,
        ],
    ],

    'testDuplicateCreationForFundAccountWithNoHashAndLinkedToABankAccount' => [
        'request'  => [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_J7iImMrzcOhfSi',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Prashanth YV',
                    'account_number' => '111000',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_J7iImMrzcOhfSi',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Prashanth YV',
                    'account_number' => '111000'
                ],
            ],
            'status_code' => 200
        ],
    ],

    'testDuplicateCreationForFundAccountWithNoHashAndLinkedToAVPA' => [
        'request'  => [
            'content' => [
                'account_type' => 'vpa',
                'contact_id'   => 'cont_J7iImMrzcOhfSi',
                'vpa'      => [
                    'address' => 'amitm@upi',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'vpa',
                'contact_id'   => 'cont_J7iImMrzcOhfSi',
                'vpa'      => [
                    'address' => 'amitm@upi',
                ],
            ],
            'status_code' => 200
        ],
    ],

    'testDuplicateCreationForFundAccountWithNoHashAndLinkedToAWallet' => [
        'request'  => [
            'content' => [
                'account_type'  => 'wallet',
                'contact_id'    => 'cont_J7iImMrzcOhfSi',
                'wallet'  => [
                    'provider' => 'amazonpay',
                    'phone'    => '9999988888',
                    'email'    => 'test@gmail.com',
                    'name'     => 'test',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account',
                'account_type' => 'wallet',
                'contact_id'   => 'cont_J7iImMrzcOhfSi',
                'wallet'  => [
                    'provider' => 'amazonpay',
                    'phone'    => '+919999988888',
                    'email'    => 'test@gmail.com',
                    'name'     => 'test',
                ],
            ],
            'status_code' => 200
        ],
    ]
];
