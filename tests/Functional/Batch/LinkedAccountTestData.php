<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testBatchValidationLinkedAccountForMutualFundDistributorMerchant' => [
        'request' => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type' => 'linked_account_create',
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Linked account creation is not allowed for your business type',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_CREATION_NOT_ALLOWED,
        ],
    ],

    'testCreateLinkedAccountCreateBatchForMutualFundDistributorMerchant' => [
        'request' => [
            'url' => '/batches',
            'method' => 'post',
            'content' => [
                'type'  => 'linked_account_create',
                'name'  => 'LA batch',
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Linked account creation is not allowed for your business type',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_CREATION_NOT_ALLOWED,
        ],
    ],
];
