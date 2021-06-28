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
];
