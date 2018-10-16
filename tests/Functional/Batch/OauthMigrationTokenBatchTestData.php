<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateOauthMigrationBatch' => [
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type'         => 'oauth_migration_token',
                'user_id'      => '10000000UserId',
                'redirect_uri' => 'http://localhost',
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'oauth_migration_token',
                'status'           => 'created',
                'total_count'      => 2,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'amount'           => 0,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],

    'testCreateOauthMigrationBatchInvalidInput' => [
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type'         => 'oauth_migration_token',
                'redirect_uri' => 'http://localhost',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The user id field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
];