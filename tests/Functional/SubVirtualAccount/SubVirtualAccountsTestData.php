<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateSubVirtualAccount' => [
        'request' => [
            'content' => [
                'master_account_number' => '2224440041626905',
                'name'                  => 'sample',
                'sub_account_number'    => '2323230041626906'
            ],
            'url'    => '/admin/sub_virtual_accounts',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'name'                  => 'sample',
                'master_account_number' => '2224440041626905',
                'sub_account_number'    => '2323230041626906',
                'active'                => true
            ],
            'status_code' => '200'
        ],
    ],

    'testCreateDuplicateSubVirtualAccount' => [
        'request' => [
            'content' => [
                'master_account_number' => '2224440041626905',
                'name'                  => 'sample',
                'sub_account_number'    => '2323230041626906'
            ],
            'url'    => '/admin/sub_virtual_accounts',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_ALREADY_EXISTS,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_ALREADY_EXISTS,
        ],

    ],

    'testCreateSubVirtualAccountWhereMasterAccountNumberMissingInDB' => [
        'request' => [
            'content' => [
                'master_account_number' => '2224440041626906',
                'name'                  => 'sample',
                'sub_account_number'    => '2323230041626906'
            ],
            'url'    => '/admin/sub_virtual_accounts',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_NO_RECORDS_FOUND,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND,
        ],
    ],

    'testCreateSubVirtualAccountWhereSubAccountNumberMissingInDB' => [
        'request' => [
            'content' => [
                'master_account_number' => '2224440041626905',
                'name'                  => 'sample',
                'sub_account_number'    => '2323230041626909'
            ],
            'url'    => '/admin/sub_virtual_accounts',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_NO_RECORDS_FOUND,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND,
        ],
    ],

    'testCreateSubVirtualAccountWithInvalidAccountType' => [
        'request' => [
            'content' => [
                'master_account_number' => '2224440041626905',
                'name'                  => 'sample',
                'sub_account_number'    => '2323230041626906'
            ],
            'url'    => '/admin/sub_virtual_accounts',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_NO_RECORDS_FOUND,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND,
        ],
    ],

    'testCreateSubVirtualAccountWithInvalidType' => [
        'request' => [
            'content' => [
                'master_account_number' => '2224440041626905',
                'name'                  => 'sample',
                'sub_account_number'    => '2323230041626906'
            ],
            'url'    => '/admin/sub_virtual_accounts',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_NO_RECORDS_FOUND,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND,
        ],
    ],

    'testFetchSubVirtualAccountsForAdmin' => [
        'request' => [
            'url'    => '/admin/sub_virtual_accounts/merchant/10000000000000',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'id'                    => 'subva_HM8yTa58wo3qRZ',
                        'entity'                => 'sub_virtual_account',
                        'active'                => true,
                        'master_account_number' => '2224440041626905',
                        'sub_account_number'    => '2323230041626906',
                    ],
                    [
                        'id'                    => 'subva_HM8yTa58wo3qRY',
                        'entity'                => 'sub_virtual_account',
                        'active'                => false,
                        'master_account_number' => '2224440041626905',
                        'sub_account_number'    => '2323230041626906',
                    ],
                ],
            ],
        ],
    ],

    'testFetchSubVirtualAccountsForProxy' => [
        'request' => [
            'url'    => '/sub_virtual_accounts',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'id'                    => 'subva_HM8yTa58wo3qRZ',
                        'entity'                => 'sub_virtual_account',
                        'active'                => true,
                        'master_account_number' => '2224440041626905',
                        'sub_account_number'    => '2323230041626906',
                    ],
                ],
            ],
        ],
    ],

    'testDisableSubVirtualAccount' => [
        'request'       => [
            'content'   => [
                'active' => 0,
            ],
            'url'    => '/admin/sub_virtual_accounts/subva_HM8yTa58wo3qRZ',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'id'                    => 'subva_HM8yTa58wo3qRZ',
                'entity'                => 'sub_virtual_account',
                'active'                => false,
                'master_account_number' => '2224440041626905',
                'sub_account_number'    => '2323230041626906',
            ],
        ],
    ],

    'testEnableSubVirtualAccount' => [
        'request'       => [
            'content'   => [
                'active' => true,
            ],
            'url'    => '/admin/sub_virtual_accounts/subva_HM8yTa58wo3qRZ',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'id'                    => 'subva_HM8yTa58wo3qRZ',
                'entity'                => 'sub_virtual_account',
                'active'                => true,
                'master_account_number' => '2224440041626905',
                'sub_account_number'    => '2323230041626906',
            ],
        ],
    ],

    'testEnableSubVirtualAccountWithInvalidId' => [
        'request'       => [
            'content'   => [
                'active' => true,
            ],
            'url'    => '/admin/sub_virtual_accounts/subva_HM8yTa58wo3qRA',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_ID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testSubVirtualAccountTransferWithOtp' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/sub_virtual_account_transfer_with_otp',
            'content' => [
                'master_account_number'  => '2224440041626905',
                'sub_account_number'     => '2323230041626906',
                'amount'                 => 200,
                'currency'               => 'INR',
                'token'                  => 'BUIj3m2Nx2VvVj',
                'otp'                    => '0007',
            ],
        ],
        'response' => [
            'content' => [
                'entity'                 => 'adjustment',
                'amount'                 => -200,
                'currency'               => 'INR',
                'description'            => 'Internal Fund Transfer to 2323230041626906',
            ],
        ],
    ],

    'testSubVirtualAccountTransferWithInvalidOtp' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/sub_virtual_account_transfer_with_otp',
            'content' => [
                'master_account_number'  => '2224440041626905',
                'sub_account_number'     => '2323230041626906',
                'amount'                 => 200,
                'currency'               => 'INR',
                'token'                  => 'BUIj3m2Nx2VvVj',
                'otp'                    => '1234',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INCORRECT_OTP,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INCORRECT_OTP,
        ],
    ],

    'testSubVirtualAccountTransferWithSubMerchantBusinessBankingNotEnabled' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/sub_virtual_account_transfer_with_otp',
            'content' => [
                'master_account_number'  => '2224440041626905',
                'sub_account_number'     => '2323230041626906',
                'amount'                 => 200,
                'currency'               => 'INR',
                'token'                  => 'BUIj3m2Nx2VvVj',
                'otp'                    => '0007',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_BUSINESS_BANKING_NOT_ENABLED_FOR_SUB_MERCHANT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_BUSINESS_BANKING_NOT_ENABLED_FOR_SUB_MERCHANT,
        ],
    ],

    'testSubVirtualAccountTransferWithInactiveSubVirtualAccount' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/sub_virtual_account_transfer_with_otp',
            'content' => [
                'master_account_number'  => '2224440041626905',
                'sub_account_number'     => '2323230041626906',
                'amount'                 => 200,
                'currency'               => 'INR',
                'token'                  => 'BUIj3m2Nx2VvVj',
                'otp'                    => '0007',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_TRANSFER_DISABLED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_TRANSFER_DISABLED,
        ],
    ],

    'testSubVirtualAccountTransferWithMasterFundsOnHold' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/sub_virtual_account_transfer_with_otp',
            'content' => [
                'master_account_number'  => '2224440041626905',
                'sub_account_number'     => '2323230041626906',
                'amount'                 => 200,
                'currency'               => 'INR',
                'token'                  => 'BUIj3m2Nx2VvVj',
                'otp'                    => '0007',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_FUNDS_ON_HOLD,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FUNDS_ON_HOLD,
        ],
    ],

    'testSubVirtualAccountTransferWithInvalidMasterAccountNumber' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/sub_virtual_account_transfer_with_otp',
            'content' => [
                'master_account_number'  => '2224440041626906',
                'sub_account_number'     => '2323230041626906',
                'amount'                 => 200,
                'currency'               => 'INR',
                'token'                  => 'BUIj3m2Nx2VvVj',
                'otp'                    => '0007',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_DOES_NOT_EXIST,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_DOES_NOT_EXIST,
        ],
    ],

    'testSubVirtualAccountTransferWithInvalidSubAccountNumber' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/sub_virtual_account_transfer_with_otp',
            'content' => [
                'master_account_number'  => '2224440041626905',
                'sub_account_number'     => '2323230041626909',
                'amount'                 => 200,
                'currency'               => 'INR',
                'token'                  => 'BUIj3m2Nx2VvVj',
                'otp'                    => '0007',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_DOES_NOT_EXIST,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_DOES_NOT_EXIST,
        ],
    ],

    'testSubVirtualAccountTransferWithInsufficientBalance' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/sub_virtual_account_transfer_with_otp',
            'content' => [
                'master_account_number'  => '2224440041626905',
                'sub_account_number'     => '2323230041626906',
                'amount'                 => 300000000,
                'currency'               => 'INR',
                'token'                  => 'BUIj3m2Nx2VvVj',
                'otp'                    => '0007',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_TRANSFER_NOT_ENOUGH_BANKING_BALANCE,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_TRANSFER_NOT_ENOUGH_BANKING_BALANCE,
        ],
    ],

    'testSubVirtualAccountTransferWithExceedingMaxAmountOfTransfer' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/sub_virtual_account_transfer_with_otp',
            'content' => [
                'master_account_number'  => '2224440041626905',
                'sub_account_number'     => '2323230041626906',
                'amount'                 => 10000001000,
                'currency'               => 'INR',
                'token'                  => 'BUIj3m2Nx2VvVj',
                'otp'                    => '0007',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The amount may not be greater than 10000000000.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSubVirtualAccountTransferWithMasterMerchantNotLive' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/sub_virtual_account_transfer_with_otp',
            'content' => [
                'master_account_number'  => '2224440041626905',
                'sub_account_number'     => '2323230041626906',
                'amount'                 => 200,
                'currency'               => 'INR',
                'token'                  => 'BUIj3m2Nx2VvVj',
                'otp'                    => '0007',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MASTER_MERCHANT_NOT_LIVE_ACTION_DENIED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MASTER_MERCHANT_NOT_LIVE_ACTION_DENIED,
        ],
    ],

    'testSubVirtualAccountTransferWithSubMerchantNotLive' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/sub_virtual_account_transfer_with_otp',
            'content' => [
                'master_account_number'  => '2224440041626905',
                'sub_account_number'     => '2323230041626906',
                'amount'                 => 200,
                'currency'               => 'INR',
                'token'                  => 'BUIj3m2Nx2VvVj',
                'otp'                    => '0007',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_SUB_MERCHANT_NOT_LIVE_ACTION_DENIED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_SUB_MERCHANT_NOT_LIVE_ACTION_DENIED,
        ],
    ],

    'testSubVirtualAccountTransferWithFeatureNotAssigned' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/sub_virtual_account_transfer_with_otp',
            'content' => [
                'master_account_number'  => '2224440041626905',
                'sub_account_number'     => '2323230041626906',
                'amount'                 => 200,
                'currency'               => 'INR',
                'token'                  => 'BUIj3m2Nx2VvVj',
                'otp'                    => '0007',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_FEATURE_NOT_ENABLED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_FEATURE_NOT_ENABLED,
        ],
    ],
];
