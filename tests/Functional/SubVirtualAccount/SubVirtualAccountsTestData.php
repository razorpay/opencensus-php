<?php

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateSubVirtualAccount' => [
        'request' => [
            'content' => [
                'master_account_number' => '2224440041626905',
                'name'                  => 'sample',
                'sub_account_number'    => '2323230041626906',
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
            'url'    => '/sub_virtual_accounts?count=1',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'                => 'sub_virtual_account',
                        'active'                => true,
                        'master_account_number' => '2323230041626907',
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
                'amount'                 => 50000001000,
                'currency'               => 'INR',
                'token'                  => 'BUIj3m2Nx2VvVj',
                'otp'                    => '0007',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The amount may not be greater than 50000000000.',
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

    'testCreateSubVirtualAccountForAccountSubAccountFlow' => [
        'request' => [
            'content' => [
                'master_account_number' => '2323230041626907',
                'name'                  => 'sample',
                'sub_account_number'    => '2323230041626906',
                'sub_account_type'      => 'sub_direct_account'
            ],
            'url'    => '/admin/sub_virtual_accounts',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'name'                  => 'sample',
                'master_account_number' => '2323230041626907',
                'sub_account_number'    => '2323230041626906',
                'active'                => true
            ],
            'status_code' => '200'
        ],
    ],

    'testCreateSubVirtualAccountWithDuplicateSubAccountNumberForAccountSubAccountFlow' => [
        'request' => [
            'content' => [
                'master_account_number' => '2323230041626900',
                'name'                  => 'sample',
                'sub_account_number'    => '2323230041626906',
                'sub_account_type'      => 'sub_direct_account'
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

    'testCreateSubVirtualAccountForAccountSubAccountFlowWithInvalidType' => [
        'request' => [
            'content' => [
                'master_account_number' => '2224440041626906',
                'name'                  => 'sample',
                'sub_account_number'    => '2323230041626906',
                'sub_account_type'      => 'random'
            ],
            'url'    => '/admin/sub_virtual_accounts',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => TraceCode::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_INVALID_TYPE,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateSubVirtualAccountWhenMasterMerchantHasFeatureEnabled' => [
        'request' => [
            'content' => [
                'master_account_number' => '2323230041626907',
                'name'                  => 'sample',
                'sub_account_number'    => '2323230041626906',
                'sub_account_type'      => 'sub_direct_account'
            ],
            'url'    => '/admin/sub_virtual_accounts',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "Master merchant cannot have sub_virtual_account feature enabled",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateSubVirtualAccountForAccountSubAccountFlowWhenSubMerchantSharedBalanceIsNonZero' => [
        'request' => [
            'content' => [
                'master_account_number' => '2323230041626907',
                'name'                  => 'sample',
                'sub_account_number'    => '2323230041626906',
                'sub_account_type'      => 'sub_direct_account'
            ],
            'url'    => '/admin/sub_virtual_accounts',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => TraceCode::BAD_REQUEST_SUB_MERCHANT_SHARED_BALANCE_NOT_ZERO,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDisableSubVirtualAccountOfTypeSubDirectAccount' => [
        'request'  => [
            'content' => [
                'active' => 0,
            ],
            'url'     => '/admin/sub_virtual_accounts/subva_HM8yTa58wo3qRZ',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'                => 'sub_virtual_account',
                'active'                => false,
                'sub_account_type'      => 'sub_direct_account',
                'master_account_number' => '2323230041626907',
                'sub_account_number'    => '2323230041626906',
            ],
        ],
    ],

    'testEnableSubVirtualAccountOfTypeSubDirectAccount' => [
        'request'  => [
            'content' => [
                'active' => 1,
            ],
            'url'     => '/admin/sub_virtual_accounts/subva_',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'entity'                => 'sub_virtual_account',
                'active'                => true,
                'sub_account_type'      => 'sub_direct_account',
                'master_account_number' => '2323230041626907',
                'sub_account_number'    => '2323230041626906',
            ],
        ],
    ],


    'testAddLimitToSubAccountWithOtpViaCreditTransfer' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/sub_virtual_account_transfer_with_otp',
            'content' => [
                'master_account_number' => '2323230041626907',
                'sub_account_number'    => '2323230041626906',
                'amount'                => 200,
                'currency'              => 'INR',
                'token'                 => 'BUIj3m2Nx2VvVj',
                'otp'                   => '0007',
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'credit_transfer',
                'amount'      => 200,
                'currency'    => 'INR',
                'payer_name'  => 'OG NBFC',
                'status'      => 'processed',
            ],
        ],
    ],

    'testLimitAdditionToSubMerchantWhenMasterMerchantFeatureIsNotEnabled' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/sub_virtual_account_transfer_with_otp',
            'content' => [
                'master_account_number' => '2323230041626907',
                'sub_account_number'    => '2323230041626906',
                'amount'                => 200,
                'currency'              => 'INR',
                'token'                 => 'BUIj3m2Nx2VvVj',
                'otp'                   => '0007',
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

    'testFetchSubVirtualAccountWithClosingBalance' => [
        'request' => [
            'url'    => '/sub_virtual_accounts',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'entity'                => 'sub_virtual_account',
                        'active'                => true,
                        'master_account_number' => '2323230041626907',
                        'sub_account_number'    => '2323230041626906',
                        'sub_account_type'      => 'sub_direct_account',
                        'sub_account_balance'   => 1000,
                        'name'                  => 'Sub Merchant 1',
                    ],
                    [
                        'entity'                => 'sub_virtual_account',
                        'active'                => false,
                        'master_account_number' => '2323230041626907',
                        'sub_account_number'    => '2323230041626908',
                        'sub_account_type'      => 'sub_direct_account',
                        'sub_account_balance'   => 2020,
                        'name'                  => 'Fin Lease'
                    ],
                ],
            ],
        ],
    ],

    'testFetchSubVirtualAccounts_ClosingBalance_Shared' => [
        'request' => [
            'url'    => '/sub_virtual_accounts',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'entity'                => 'sub_virtual_account',
                        'active'                => true,
                        'master_account_number' => '2323230041626907',
                        'sub_account_number'    => '2323230041626906',
                        'sub_account_type'      => 'default',
                        'sub_account_balance'   => 1000,
                        'name'                  => 'Sub VA 1',
                    ],
                    [
                        'entity'                => 'sub_virtual_account',
                        'active'                => false,
                        'master_account_number' => '2323230041626907',
                        'sub_account_number'    => '2323230041626908',
                        'sub_account_type'      => 'default',
                        'sub_account_balance'   => 2020,
                        'name'                  => 'Sub VA 2'
                    ],
                ],
            ],
        ],
    ],

    'testFetchSubVirtualAccounts_Shared_Exception' => [
        'request' => [
            'url'    => '/sub_virtual_accounts',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    'description' => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ServerErrorException',
            'internal_error_code' => ErrorCode::SERVER_ERROR,
        ],
    ],

    'testFetchSubVirtualAccountCreditTransfers' => [
        'request'  => [
            'url'    => '/sub_virtual_accounts/credit_transfers?merchant_id=100abc000abc01&expand[]=payer_user&expand[]=merchant',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 3,
                'items'  => [
                    [
                        'entity'            => 'credit_transfer',
                        'amount'            => 500,
                        'status'            => 'processing',
                        'merchant_id'       => '100abc000abc01',
                        'merchant'    => [
                            'id' => '100abc000abc01',
                        ],
                        'payer_merchant_id' => '10000000000000',
                        'payer_user_id' => 'MerchantUser02',
                        'payer_user' => [
                            'id' => 'MerchantUser02',
                            'email' => 'merchant2@gmail.com'
                        ]

                    ],
                    [
                        'entity'            => 'credit_transfer',
                        'amount'            => 400,
                        'status'            => 'failed',
                        'merchant_id'       => '100abc000abc01',
                        'merchant'    => [
                            'id' => '100abc000abc01',
                        ],
                        'payer_merchant_id' => '10000000000000',
                        'payer_user_id' => 'MerchantUser02',
                        'payer_user' => [
                            'id' => 'MerchantUser02',
                            'email' => 'merchant2@gmail.com'
                        ]
                    ],
                    [
                        'entity'            => 'credit_transfer',
                        'amount'            => 300,
                        'status'            => 'processed',
                        'merchant_id'       => '100abc000abc01',
                        'merchant'    => [
                            'id' => '100abc000abc01',
                        ],
                        'payer_merchant_id' => '10000000000000',
                        'payer_user_id' => 'MerchantUser02',
                        'payer_user' => [
                            'id' => 'MerchantUser02',
                            'email' => 'merchant2@gmail.com',
                        ]
                    ],
                ],
            ],
        ],
    ],
];
