<?php

use RZP\Error\ErrorCode;
use RZP\Models\Batch\Header;
use RZP\Error\PublicErrorCode;

return [

    'testCreateBatchOfAuthLinks' => [
        'request'  => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'auth_link',
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'auth_link',
                'status'           => 'created',
                'total_count'      => 4,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'amount'           => 0,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],

    'testBatchFileValidation' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type' => 'auth_link',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 4,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Header::AUTH_LINK_CUSTOMER_NAME   => 'test',
                        Header::AUTH_LINK_CUSTOMER_EMAIL  => 'test@test.test',
                        Header::AUTH_LINK_CUSTOMER_PHONE  => '9999998888',
                        Header::AUTH_LINK_AMOUNT_IN_PAISE => 0,
                        Header::AUTH_LINK_CURRENCY        => "INR",
                        Header::AUTH_LINK_METHOD          => 'emandate',
                        Header::AUTH_LINK_TOKEN_EXPIRE_BY => '20-10-2020',
                        Header::AUTH_LINK_MAX_AMOUNT      => "100000",
                        Header::AUTH_LINK_EXPIRE_BY       => '20-10-2020',
                        Header::AUTH_LINK_AUTH_TYPE       => 'netbanking',
                        Header::AUTH_LINK_BANK            => "HDFC",
                        Header::AUTH_LINK_NAME_ON_ACCOUNT => "Test",
                        Header::AUTH_LINK_IFSC            => "HDFC0001233",
                        Header::AUTH_LINK_ACCOUNT_NUMBER  => "1233100023891",
                        Header::AUTH_LINK_ACCOUNT_TYPE    => "savings",
                        Header::AUTH_LINK_RECEIPT         => '#1',
                        Header::AUTH_LINK_DESCRIPTION     => 'test auth link',

                    ],
                    [
                        Header::AUTH_LINK_CUSTOMER_NAME   => 'test',
                        Header::AUTH_LINK_CUSTOMER_EMAIL  => 'test@test.test',
                        Header::AUTH_LINK_CUSTOMER_PHONE  => '9999998888',
                        Header::AUTH_LINK_AMOUNT_IN_PAISE => 1000,
                        Header::AUTH_LINK_CURRENCY        => "INR",
                        Header::AUTH_LINK_METHOD          => 'emandate',
                        Header::AUTH_LINK_TOKEN_EXPIRE_BY => null,
                        Header::AUTH_LINK_MAX_AMOUNT      => "100000",
                        Header::AUTH_LINK_EXPIRE_BY       => null,
                        Header::AUTH_LINK_AUTH_TYPE       => null,
                        Header::AUTH_LINK_BANK            => "HDFC",
                        Header::AUTH_LINK_NAME_ON_ACCOUNT => "Test",
                        Header::AUTH_LINK_IFSC            => "HDFC0001233",
                        Header::AUTH_LINK_ACCOUNT_NUMBER  => "1233100023891",
                        Header::AUTH_LINK_ACCOUNT_TYPE    => "savings",
                        Header::AUTH_LINK_RECEIPT         => '#2',
                        Header::AUTH_LINK_DESCRIPTION     => 'test auth link',
                    ],
                    [
                        Header::AUTH_LINK_CUSTOMER_NAME   => 'test',
                        Header::AUTH_LINK_CUSTOMER_EMAIL  => 'test@test.test',
                        Header::AUTH_LINK_CUSTOMER_PHONE  => '9999998888',
                        Header::AUTH_LINK_AMOUNT_IN_PAISE => 1000,
                        Header::AUTH_LINK_CURRENCY        => "INR",
                        Header::AUTH_LINK_METHOD          => 'card',
                        Header::AUTH_LINK_TOKEN_EXPIRE_BY => null,
                        Header::AUTH_LINK_MAX_AMOUNT      => "100000",
                        Header::AUTH_LINK_EXPIRE_BY       => null,
                        Header::AUTH_LINK_AUTH_TYPE       => null,
                        Header::AUTH_LINK_BANK            => null,
                        Header::AUTH_LINK_NAME_ON_ACCOUNT => null,
                        Header::AUTH_LINK_IFSC            => null,
                        Header::AUTH_LINK_ACCOUNT_NUMBER  => null,
                        Header::AUTH_LINK_ACCOUNT_TYPE    => null,
                        Header::AUTH_LINK_RECEIPT         => '#3',
                        Header::AUTH_LINK_DESCRIPTION     => 'test auth link',
                    ],
                ],
            ],
        ],
    ],

    'testCheckAuthLinkBatchStatus' => [
        'request'  => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'auth_link',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testCreateBatchOfNachAuthLinks' => [
        'request'  => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'auth_link',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testValidateBatchWithInvalidHeaders' => [
        'request'   => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type' => 'auth_link',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The uploaded file has invalid headers',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_HEADERS,
        ],
    ],

    'testCheckAuthLinkBatchWithBlankSpaceInput' => [
        'request'  => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'auth_link',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testCheckAuthLinkBatchWitIntegerDateInputForExcel' => [
        'request'  => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'auth_link',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testFetchAuthLinkForAuthLinkSupervisorRole' => [
        'request'  => [
            'url'     => '/batches',
            'X-Dashboard-User-Id' => '100AgentUserId',
            'X-Dashboard-User-Role' => 'auth_link_supervisor',
            'method'  => 'get',
            'content' => [
                'type' => 'auth_link',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testFetchAuthLinkForAuthLinkAgentRole' => [
        'request'  => [
            'url'     => '/batches',
            'X-Dashboard-User-Id' => '100AgentUserId',
            'X-Dashboard-User-Role' => 'auth_link_supervisor',
            'method'  => 'get',
            'content' => [
                'type' => 'auth_link',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Authentication failed',
                ],
            ],
            'status_code' => 400,
        ]
    ],
];
