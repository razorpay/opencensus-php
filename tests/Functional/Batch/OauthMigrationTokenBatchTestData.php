<?php

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testProcessOauthMigrationBatch' => [
        'request'  => [
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
                'total_count'      => 3,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'amount'           => 0,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],

    'testProcessOauthMigrationBatchWithTokenFailures' => [
        'request'  => [
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
                'total_count'      => 3,
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
        'request'   => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type'         => 'oauth_migration_token',
                'redirect_uri' => 'http://localhost',
            ],
        ],
        'response'  => [
            'content'     => [
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

    'testProcessOauthMigrationBatchInvalidInput' => [
        'request'   => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type'         => 'oauth_migration_token',
                'user_id'      => '10000000UserIp',
                'redirect_uri' => 'http://localhost',
            ],
        ],
        'response'  => [
            'content'     => [
                'entity'           => 'batch',
                'type'             => 'oauth_migration_token',
                'status'           => 'created',
                'total_count'      => 3,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'amount'           => 0,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
            'status_code' => 200,
        ]
    ],

    'successfulReturn' => [
        [
            'public_token'  => 'dummy_public1',
            'access_token'  => 'dummy_access1',
            'expires_in'    => Carbon::now()->getTimestamp(),
            'merchant_id'   => '10000000000000',
            'refresh_token' => 'dummy_refresh1',
        ],
        [
            'public_token'  => 'dummy_public2',
            'access_token'  => 'dummy_access2',
            'expires_in'    => Carbon::now()->getTimestamp(),
            'merchant_id'   => '10000000000000',
            'refresh_token' => 'dummy_refresh2',
        ],
        [
            'public_token'  => 'dummy_public3',
            'access_token'  => 'dummy_access3',
            'expires_in'    => Carbon::now()->getTimestamp(),
            'merchant_id'   => '10000000000000',
            'refresh_token' => 'dummy_refresh3',
        ]
    ],
];
