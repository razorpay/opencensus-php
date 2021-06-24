<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateSubVirtualAccount' => [
        'request' => [
            'content' => [
                'master_account_number' => '2323230041626905',
                'name' => 'sample',
                'sub_account_number' => '2323230041626906'
            ],
            'url' => '/admin/sub_virtual_accounts',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'name' => 'sample',
                'master_account_number' => '2323230041626905',
                'sub_account_number' => '2323230041626906',
                'active' => true
            ],
            'status_code' => '200'
        ],
    ],

    'testCreateDuplicateSubVirtualAccount' => [
        'request' => [
            'content' => [
                'master_account_number' => '2323230041626905',
                'name' => 'sample',
                'sub_account_number' => '2323230041626906'
            ],
            'url' => '/admin/sub_virtual_accounts',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_ALREADY_EXISTS,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_ALREADY_EXISTS,
        ],

    ],

    'testCreateSubVirtualAccountWhereSubAccountNumberMissingInDB' => [
        'request' => [
            'content' => [
                'master_account_number' => '2323230041626905',
                'name' => 'sample',
                'sub_account_number' => '2323230041626906'
            ],
            'url' => '/admin/sub_virtual_accounts',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_NO_RECORDS_FOUND,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND,
        ],
    ],

    'testCreateSubVirtualAccountWhereMasterAccountNumberMissingInDB' => [
        'request' => [
            'content' => [
                'master_account_number' => '2323230041626905',
                'name' => 'sample',
                'sub_account_number' => '2323230041626906'
            ],
            'url' => '/admin/sub_virtual_accounts',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_NO_RECORDS_FOUND,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND,
        ],
    ],

    'testCreateSubVirtualAccountWithInvalidAccountType' => [
        'request' => [
            'content' => [
                'master_account_number' => '2323230041626905',
                'name' => 'sample',
                'sub_account_number' => '2323230041626906'
            ],
            'url' => '/admin/sub_virtual_accounts',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_NO_RECORDS_FOUND,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND,
        ],
    ],

    'testCreateSubVirtualAccountWithInvalidType' => [
        'request' => [
            'content' => [
                'master_account_number' => '2323230041626905',
                'name' => 'sample',
                'sub_account_number' => '2323230041626906'
            ],
            'url' => '/admin/sub_virtual_accounts',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_NO_RECORDS_FOUND,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND,
        ],
    ],
];
