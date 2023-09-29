<?php

use RZP\Error\ErrorCode;
use \RZP\Models\Merchant;
use RZP\Error\PublicErrorCode;
use RZP\Models\FundAccount\Entity as FundAccount;
use RZP\Models\BankAccount\Entity as BankAccount;
use RZP\Models\FundAccount\Validation\Entity as Validation;

return [
    'testGetValidations' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity' => 'fund_account.validation',
                        'fund_account' => [
                            'entity' => 'fund_account',
                            'account_type' => 'bank_account',
                            'details' => [
                                'ifsc' => 'SBIN0010411',
                                'bank_name' => 'State Bank of India',
                                'name' => 'Rohit Keshwani',
                                'account_number' => '123456789',
                            ],
                            'bank_account' => [
                                'ifsc' => 'SBIN0010411',
                                'bank_name' => 'State Bank of India',
                                'name' => 'Rohit Keshwani',
                                'account_number' => '123456789',
                            ],
                            'active' => true,
                        ],
                        'status' => 'completed',
                        'amount' => 100,
                        'currency' => 'INR',
                        'notes' => [],
                        'results' => [
                            'account_status' => 'active',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testCreateValidationWithFundAccountId' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                Validation::FUND_ACCOUNT => [
                    FundAccount::ID => '',
                ],
                Validation::NOTES        => [],
                Validation::RECEIPT      => '12345667',
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account.validation',
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'contact_id'   => 'cont_1000000contact',
                    'account_type' => 'bank_account',
                    'active'       => true,
                    'details'      => [
                        'ifsc'           => 'SBIN0007105',
                        'bank_name'      => 'State Bank of India',
                        'name'           => 'Amit M',
                        'account_number' => '111000111',
                    ],
                ],
                'status'       => 'created',
                'amount'       => 100,
                'currency'     => 'INR',
                'notes'        => [],
                'results'      => [
                    'utr'             => null,
                    'account_status'  => null,
                    'registered_name' => null,
                ],
            ],
        ],
    ],

    'testCreateValidationWithExposeUTRNotSetInResponse' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                Validation::FUND_ACCOUNT => [
                    FundAccount::ID => '',
                ],
                Validation::CURRENCY     => 'INR',
                Validation::NOTES        => [],
                Validation::RECEIPT      => '12345667',
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account.validation',
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'contact_id'   => 'cont_1000000contact',
                    'account_type' => 'bank_account',
                    'active'       => true,
                    'details'      => [
                        'ifsc'           => 'SBIN0007105',
                        'bank_name'      => 'State Bank of India',
                        'name'           => 'Amit M',
                        'account_number' => '111000111',
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

    'testCreateValidationWithWrongFundAccountId' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                Validation::FUND_ACCOUNT => [
                    FundAccount::ID => 'fa_ToteWrongIdLol',
                ],
                Validation::AMOUNT       => '100',
                Validation::CURRENCY     => 'INR',
                Validation::NOTES        => [],
                Validation::RECEIPT      => '12345667',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                    'field'       => 'fund_account',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testCreateValidationForBankNotAllowed' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                Validation::FUND_ACCOUNT  => [
                    FundAccount::ACCOUNT_TYPE => 'bank_account',
                    FundAccount::DETAILS      => [
                        BankAccount::ACCOUNT_NUMBER => '123456789',
                        BankAccount::NAME           => 'Rohit Keshwani',
                        BankAccount::IFSC           => 'PYTM0000001',
                    ],
                ],
                Validation::AMOUNT       => '100',
                Validation::CURRENCY     => 'INR',
                Validation::NOTES        => [],
                Validation::RECEIPT      => '12345667',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Sorry we do not support this bank right now for fund account validation.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FUND_ACCOUNT_VALIDATION_BANK_NOT_ALLOWED,
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

    'createValidationWithFundAccountEntityFromAdmin' => [
        'request' => [
            'url'     => '/fund_accounts/validations/admin',
            'method'  => 'post',
            'server' => [
                'HTTP_' . \RZP\Http\RequestHeader::X_RAZORPAY_ACCOUNT => '100000Razorpay',
                        ],
            'content' => [
                Validation::FUND_ACCOUNT  => [
                    FundAccount::ACCOUNT_TYPE => 'bank_account',
                    "bank_account"            => [
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
    'testCreateValidationWithWrongFundAccountEntity' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                Validation::FUND_ACCOUNT  => [
                    FundAccount::ACCOUNT_TYPE => 'bank_account',
                    FundAccount::DETAILS      => [
                        BankAccount::ACCOUNT_NUMBER => '!!!$$$##',
                        BankAccount::NAME           => 'Rohit Keshwani',
                        BankAccount::IFSC           => 'SBIN0010411',
                    ],
                ],
                Validation::AMOUNT        => '100',
                Validation::CURRENCY      => 'INR',
                Validation::NOTES         => [],
                Validation::RECEIPT       => '12345667',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The account number format is invalid.',
                    'field'       => 'fund_account.account_number',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateValidationWithAmountInDecimalString' => [
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
                Validation::AMOUNT        => '112.999999999999999',
                Validation::CURRENCY      => 'INR',
                Validation::NOTES         => [],
                Validation::RECEIPT       => '12345667',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The amount must be an integer.',
                    'field'       => 'amount',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateValidationWithAmountInDecimalFloat' => [
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
                Validation::AMOUNT        => 112.9999999,
                Validation::CURRENCY      => 'INR',
                Validation::NOTES         => [],
                Validation::RECEIPT       => '12345667',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The amount must be an integer.',
                    'field'       => 'amount',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFundAccValidationOnPrepaidModelWithNoFeeCreditsAndNoBalance' => [
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
                Validation::NOTES         => [],
                Validation::RECEIPT       => '12345667',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The fees calculated for fund account validation is greater than available fee credits or balance.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FUND_ACCOUNT_VALIDATION_INSUFFICIENT_BALANCE,
        ],
    ],

    'testWebhookFiringFundAccountValidationFailed' => [
        'entity' => 'event',
        'event'  => 'fund_account.validation.failed',
        'contains' => [
            'fund_account.validation',
        ],
        'payload' => [
            'fund_account.validation' => [
                'entity' => [
                    'entity'       => 'fund_account.validation',
                    'fund_account' => [
                        'entity'       => 'fund_account',
                        'contact_id'   => 'cont_1000000contact',
                        'account_type' => 'bank_account',
                        'active'       => true,
                        'details'      => [
                            'ifsc'           => 'SBIN0007105',
                            'bank_name'      => 'State Bank of India',
                            'name'           => 'Amit M',
                            'account_number' => '111000111',
                        ],
                    ],
                    'status'       => 'failed',
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
    ],

    'testWebhookFundAccountValidationCompleted' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event'  => 'fund_account.validation.completed',
            'contains' => [
                'fund_account.validation',
            ],
            'payload' => [
                'fund_account.validation' => [
                    'entity' => [
                        'entity'       => 'fund_account.validation',
                        'fund_account' => [
                            'entity'       => 'fund_account',
                            'account_type' => 'bank_account',
                            'active'       => true,
                            'details'      => [
                                'ifsc'           => 'SBIN0010411',
                                'bank_name'      => 'State Bank of India',
                                'name'           => 'Rohit Keshwani',
                                'account_number' => '123456789',
                            ],
                        ],
                        'status'       => 'completed',
                        'amount'       => 100,
                        'currency'     => 'INR',
                        'notes'        => [],
                        'results'      => [
                            'account_status'  => 'active',
                            'registered_name' => 'Someone',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFiringOfWebhookOnFAVCompletionWithStork' => [
        'entity' => 'event',
        'event'  => 'fund_account.validation.completed',
        'contains' => [
            'fund_account.validation',
        ],
        'payload' => [
            'fund_account.validation' => [
                'entity' => [
                    'entity'       => 'fund_account.validation',
                    'fund_account' => [
                        'entity'       => 'fund_account',
                        'contact_id'   => 'cont_1000000contact',
                        'account_type' => 'bank_account',
                        'active'       => true,
                        'details'      => [
                            'ifsc'           => 'SBIN0007105',
                            'bank_name'      => 'State Bank of India',
                            'name'           => 'Amit M',
                            'account_number' => '111000111',
                        ],
                    ],
                    'status'       => 'completed',
                    'amount'       => 100,
                    'currency'     => 'INR',
                    'notes'        => [],
                    'results'      => [
                        'account_status'  => 'active',
                        'registered_name' => 'Razorpay Test',
                    ],
                ],
            ],
        ],
    ],

    'testFundAccValidationWhenFailedDuringRecon' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                Validation::FUND_ACCOUNT => [
                    FundAccount::ID => '',
                ],
                Validation::CURRENCY     => 'INR',
                Validation::NOTES        => [],
                Validation::RECEIPT      => '12345667',
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account.validation',
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'contact_id'   => 'cont_1000000contact',
                    'account_type' => 'bank_account',
                    'active'       => true,
                    'details'      => [
                        'ifsc'           => 'SBIN0007105',
                        'bank_name'      => 'State Bank of India',
                        'name'           => 'Amit M',
                        'account_number' => '111000111',
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
    'testFundAccValidationWhenFailedDuringReconWithNonInternalError' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                Validation::FUND_ACCOUNT => [
                    FundAccount::ID => '',
                ],
                Validation::CURRENCY     => 'INR',
                Validation::NOTES        => [],
                Validation::RECEIPT      => '12345667',
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account.validation',
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'contact_id'   => 'cont_1000000contact',
                    'account_type' => 'bank_account',
                    'active'       => true,
                    'details'      => [
                        'ifsc'           => 'SBIN0007105',
                        'bank_name'      => 'State Bank of India',
                        'name'           => 'Amit M',
                        'account_number' => '111000111',
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
    'testFundAccValidationWhenFailedDuringReconWithInternalError' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                Validation::FUND_ACCOUNT => [
                    FundAccount::ID => '',
                ],
                Validation::CURRENCY     => 'INR',
                Validation::NOTES        => [],
                Validation::RECEIPT      => '12345667',
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account.validation',
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'contact_id'   => 'cont_1000000contact',
                    'account_type' => 'bank_account',
                    'active'       => true,
                    'details'      => [
                        'ifsc'           => 'SBIN0007105',
                        'bank_name'      => 'State Bank of India',
                        'name'           => 'Amit M',
                        'account_number' => '111000111',
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
    'testFundAccValidationWithAccountNumberAndBankAccount' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                FundAccount::ACCOUNT_NUMBER => '2224440041626905',
                Validation::FUND_ACCOUNT => [
                    FundAccount::ID => '',
                ],
                Validation::AMOUNT       => 100,
                Validation::CURRENCY     => 'INR',
                Validation::NOTES        => [],
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account.validation',
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'contact_id'   => 'cont_1000000contact',
                    'account_type' => 'bank_account',
                    'active'       => true,
                    'details'      => [
                        'ifsc'           => 'SBIN0007105',
                        'bank_name'      => 'State Bank of India',
                        'name'           => 'Amit M',
                        'account_number' => '111000111',
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
    'testFundAccValidationWithAccountNumberAndVpa' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                FundAccount::ACCOUNT_NUMBER => '2224440041626905',
                Validation::FUND_ACCOUNT    => [
                    FundAccount::ID => '',
                ],
                Validation::NOTES           => [],
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account.validation',
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'contact_id'   => 'cont_1000000contact',
                    'account_type' => 'vpa',
                    'active'       => true,
                    'details'      => [
                        'address' => "withname@razorpay"
                    ],
                ],
                'amount'       => null,
                'currency'     => null,
                'status'       => 'created',
                'notes'        => [],
                'results'      => [
                    'account_status'  => null,
                    'registered_name' => null,
                ],
            ],
        ],
    ],
    'testFundAccValidationWithAccountNumberAndInvalidVpa' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                FundAccount::ACCOUNT_NUMBER => '2224440041626905',
                Validation::FUND_ACCOUNT    => [
                    FundAccount::ID => '',
                ],
                Validation::NOTES           => [],
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account.validation',
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'contact_id'   => 'cont_1000000contact',
                    'account_type' => 'vpa',
                    'active'       => true,
                    'details'      => [
                        'address' => "invalidvpa@razorpay"
                    ],
                ],
                'amount'       => null,
                'currency'     => null,
                'status'       => 'created',
                'notes'        => [],
                'results'      => [
                    'account_status'  => null,
                    'registered_name' => null,
                ],
            ],
        ],
    ],
    'testFundAccValidationWithAccountNumberAndInvalidVpaHandle' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                FundAccount::ACCOUNT_NUMBER => '2224440041626905',
                Validation::FUND_ACCOUNT    => [
                    FundAccount::ID => '',
                ],
                Validation::NOTES           => [],
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account.validation',
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'contact_id'   => 'cont_1000000contact',
                    'account_type' => 'vpa',
                    'active'       => true,
                    'details'      => [
                        'address' => "invalidhandle@razor"
                    ],
                ],
                'amount'       => null,
                'currency'     => null,
                'status'       => 'created',
                'notes'        => [],
                'results'      => [
                    'account_status'  => null,
                    'registered_name' => null,
                ],
            ],
        ],
    ],
    'testVpaFundAccValidationForFailedStatus' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                FundAccount::ACCOUNT_NUMBER => '2224440041626905',
                Validation::FUND_ACCOUNT    => [
                    FundAccount::ID => '',
                ],
                Validation::NOTES           => [],
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account.validation',
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'contact_id'   => 'cont_1000000contact',
                    'account_type' => 'vpa',
                    'active'       => true,
                    'details'      => [
                        'address' => "vpagatewayerror@razorpay"
                    ],
                ],
                'amount'       => null,
                'currency'     => null,
                'status'       => 'created',
                'notes'        => [],
                'results'      => [
                    'account_status'  => null,
                    'registered_name' => null,
                ],
            ],
        ],
    ],
    'testFixTransactionSettledAt' => [
        'request' => [
            'url'     => '/transactions/fund_account_validation/settled/fix',
            'method'  => 'post',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],
    'testFundAccValidationBankingFailedAccountTypeDirect' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                FundAccount::ACCOUNT_NUMBER => '2224440041626905',
                Validation::FUND_ACCOUNT => [
                    FundAccount::ID => '',
                ],
                Validation::AMOUNT       => 100,
                Validation::CURRENCY     => 'INR',
                Validation::NOTES        => [],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Penny Testing is not supported for the given account number.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FUND_ACCOUNT_VALIDATION_NOT_SUPPORTED_BALANCE,
        ],
    ],
    'testFundAccValidationBankingFailedAmountVpa' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                FundAccount::ACCOUNT_NUMBER => '2224440041626905',
                Validation::FUND_ACCOUNT => [
                    FundAccount::ID => '',
                ],
                Validation::AMOUNT       => 100,
                Validation::NOTES        => [],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'amount is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],
    'testFundAccValidationBankingFailedCurrencyVpa' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                FundAccount::ACCOUNT_NUMBER => '2224440041626905',
                Validation::FUND_ACCOUNT => [
                    FundAccount::ID => '',
                ],
                Validation::CURRENCY     => 'INR',
                Validation::NOTES        => [],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'currency is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],
    'testFundAccValidationBankingFailedMissingFundAccountId' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                FundAccount::ACCOUNT_NUMBER => '2224440041626905',
                Validation::FUND_ACCOUNT  => [
                    FundAccount::ACCOUNT_TYPE => 'bank_account',
                    FundAccount::DETAILS      => [
                        BankAccount::ACCOUNT_NUMBER => '123456789',
                        BankAccount::NAME           => 'Jayesh Pawar',
                        BankAccount::IFSC           => 'SBIN0010432',
                    ],
                ],
                Validation::NOTES        => [],
                Validation::AMOUNT       => '100',
                Validation::CURRENCY     => 'INR',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The fund account.id field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
    'testFundAccValidationFailedFundAccountCardType' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                FundAccount::ACCOUNT_NUMBER => '2224440041626905',
                Validation::FUND_ACCOUNT => [
                    FundAccount::ID => '',
                ],
                Validation::CURRENCY     => 'INR',
                Validation::NOTES        => [],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid fund account type: card',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testGetFavByIdAndMerchantId' => [
        'request'  => [
            'url'    => '/fund_accounts/validations/100000Razorpay/%s',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity'  => 'fund_account.validation',
                'notes'   => [
                    'merchant_id' => '10000000000000'
                ],
                'results' => [
                    'account_status'  => "active",
                    'registered_name' => "random name",
                ]
            ],
        ],
    ],

    'testFundAccValidationWithFailedStatus' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                Validation::FUND_ACCOUNT => [
                    FundAccount::ID => '',
                ],
                Validation::CURRENCY     => 'INR',
                Validation::NOTES        => [],
                Validation::RECEIPT      => '12345667',
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account.validation',
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'contact_id'   => 'cont_1000000contact',
                    'account_type' => 'bank_account',
                    'active'       => true,
                    'details'      => [
                        'ifsc'           => 'SBIN0007105',
                        'bank_name'      => 'State Bank of India',
                        'name'           => 'Amit M',
                        'account_number' => '111000111',
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

    'testFundAccValidationWithFailedStatusForBusinessBanking' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                FundAccount::ACCOUNT_NUMBER => '2224440041626905',
                Validation::FUND_ACCOUNT => [
                    FundAccount::ID => '',
                ],
                Validation::AMOUNT       => 100,
                Validation::CURRENCY     => 'INR',
                Validation::NOTES        => [],
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account.validation',
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'contact_id'   => 'cont_1000000contact',
                    'account_type' => 'bank_account',
                    'active'       => true,
                    'details'      => [
                        'ifsc'           => 'SBIN0007105',
                        'bank_name'      => 'State Bank of India',
                        'name'           => 'Amit M',
                        'account_number' => '111000111',
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

    'testFundAccValidationWithFailedStatusOnPostpaid' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                Validation::FUND_ACCOUNT => [
                    FundAccount::ID => '',
                ],
                Validation::CURRENCY     => 'INR',
                Validation::NOTES        => [],
                Validation::RECEIPT      => '12345667',
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account.validation',
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'contact_id'   => 'cont_1000000contact',
                    'account_type' => 'bank_account',
                    'active'       => true,
                    'details'      => [
                        'ifsc'           => 'SBIN0007105',
                        'bank_name'      => 'State Bank of India',
                        'name'           => 'Amit M',
                        'account_number' => '111000111',
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

    'testFinalStateReachedFundAccValidationNotMarkAsFailed' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                Validation::FUND_ACCOUNT => [
                    FundAccount::ID => '',
                ],
                Validation::CURRENCY     => 'INR',
                Validation::NOTES        => [],
                Validation::RECEIPT      => '12345667',
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account.validation',
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'contact_id'   => 'cont_1000000contact',
                    'account_type' => 'bank_account',
                    'active'       => true,
                    'details'      => [
                        'ifsc'           => 'SBIN0007105',
                        'bank_name'      => 'State Bank of India',
                        'name'           => 'Amit M',
                        'account_number' => '111000111',
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

    'testFundAccValidationMarkAsFailed' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                Validation::FUND_ACCOUNT => [
                    FundAccount::ID => '',
                ],
                Validation::CURRENCY     => 'INR',
                Validation::NOTES        => [],
                Validation::RECEIPT      => '12345667',
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account.validation',
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'contact_id'   => 'cont_1000000contact',
                    'account_type' => 'bank_account',
                    'active'       => true,
                    'details'      => [
                        'ifsc'           => 'SBIN0007105',
                        'bank_name'      => 'State Bank of India',
                        'name'           => 'Amit M',
                        'account_number' => '111000111',
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

    'testFundAccValidationOnPrepaidModelWithNoFeeCreditsAndNoBalanceNewApiError' => [
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
                Validation::NOTES         => [],
                Validation::RECEIPT       => '12345667',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The fees calculated for fund account validation is greater than available fee credits or balance.',
                    'reason'      => 'insufficient_funds',
                    'source'      => 'business',
                    'step'        => null,
                    'metadata'    => []
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FUND_ACCOUNT_VALIDATION_INSUFFICIENT_BALANCE,
        ],
    ],

    'testFundAccValidationOnPrepaidModelWithNoFeeCreditsAndNoBalanceNewApiErrorOnLiveMode' => [
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
                Validation::NOTES         => [],
                Validation::RECEIPT       => '12345667',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The fees calculated for fund account validation is greater than available fee credits or balance.',
                    'reason'      => 'insufficient_funds',
                    'source'      => 'business',
                    'step'        => null,
                    'metadata'    => []
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FUND_ACCOUNT_VALIDATION_INSUFFICIENT_BALANCE,
        ],
    ],

    'testDispatchOfTransactionsJobInLedgerReverseShadow' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                FundAccount::ACCOUNT_NUMBER => '2224440041626905',
                Validation::FUND_ACCOUNT => [
                    FundAccount::ID => '',
                ],
                Validation::AMOUNT       => 100,
                Validation::CURRENCY     => 'INR',
                Validation::NOTES        => [],
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account.validation',
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'contact_id'   => 'cont_1000000contact',
                    'account_type' => 'bank_account',
                    'active'       => true,
                    'details'      => [
                        'ifsc'           => 'SBIN0007105',
                        'bank_name'      => 'State Bank of India',
                        'name'           => 'Amit M',
                        'account_number' => '111000111',
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

    'testCreateFailedFundAccountValidationWithInsufficientBalanceInLedgerReverseShadow' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The fees calculated for fund account validation is greater than available fee credits or balance.',
                    'step'        => null,
                    'metadata'    => []
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FUND_ACCOUNT_VALIDATION_INSUFFICIENT_BALANCE,
        ],
    ],

    'testFundAccountValidationBlockedOnShadowSharedBalance' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                FundAccount::ACCOUNT_NUMBER => '2224440041626905',
                Validation::FUND_ACCOUNT => [
                    FundAccount::ID => '',
                ],
                Validation::AMOUNT       => 100,
                Validation::CURRENCY     => 'INR',
                Validation::NOTES        => [],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Fund account validation not supported for the debit account',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ]
];
