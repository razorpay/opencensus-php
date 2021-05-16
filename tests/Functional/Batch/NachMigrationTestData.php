<?php

use RZP\Error\ErrorCode;
use RZP\Models\Batch\Header;
use RZP\Error\PublicErrorCode;

return [

    'testCreateBatchOfNachMigrations' => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'   => 'nach_migration',
                'config' => [
                    'emand_term'  => 'testhdfcrandom',
                    'merchant_id' => '10000000000000',
                    'emand_pay_enabled'   => True,
                    'nach_pay_enabled'    => False,
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'nach_migration',
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
            'url'     => '/admin/batches/validate',
            'method'  => 'post',
            'content' => [
                'type' => 'nach_migration',
                'config' => [
                    'merchant_id' => '1cXSLlUU8X8sXl',
                    'emand_term'  => 'testhdfcrandom',
                ]
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 4,
                'error_count'       => 0,
                'emandate_payment_enabled'    => True,
                'nach_payment_enabled'        => True,
            ],
        ],
    ],

    'testValidateBatchWithInvalidHeaders' => [
        'request'   => [
            'url'     => '/admin/batches/validate',
            'method'  => 'post',
            'content' => [
                'type' => 'nach_migration',
                'config' => [
                    'merchant_id' => '1cXSLlUU8X8sXl',
                    'emand_term'  => 'testhdfcrandom',
                ]
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Headers Not matching: umrn, frequency',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_HEADERS,
        ],
    ],

    'testInvlidFeature' => [
        'request'   => [
            'url'     => '/admin/batches/validate',
            'method'  => 'post',
            'content' => [
                'type' => 'nach_migration',
                'config' => [
                    'merchant_id' => '1cXSLlUU8X8sXl',
                    'emand_term'  => 'testhdfcrandom',
                ]
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'feature not enabled for merchant',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_OPERATION_NOT_ALLOWED_IN_LIVE,
        ],
    ],

    'testInvlidTerminal' => [
        'request'   => [
            'url'     => '/admin/batches/validate',
            'method'  => 'post',
            'content' => [
                'type' => 'nach_migration',
                'config' => [
                    'merchant_id' => '1cXSLlUU8X8sXl',
                    'emand_term'  => 'testhdfcrandom',
                ]
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Merchant with Terminal not found',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_ID_NOT_PRESENT,
        ],
    ],

    'testInvlidPricing' => [
        'request'   => [
            'url'     => '/admin/batches/validate',
            'method'  => 'post',
            'content' => [
                'type' => 'nach_migration',
                'config' => [
                    'merchant_id' => '1cXSLlUU8X8sXl',
                    'emand_term'  => 'testhdfcrandom',
                ]
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Merchant does not have a pricing plan for neither NACH nor EMANDATE',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_OPERATION_NOT_ALLOWED_IN_LIVE,
        ],
    ],

    'testSuccessNachMigration' => [
        'request' => [
            'url'     => '/subscription_registration/migration',
            'method'  => 'post',
            'content' => [
                'customer'                  => [
                    'name' => 'JayKumar',
                    'contact' => '9123456780',
                    'email' => 'abc@gmail.com'
                ],
                'start_time'                => 1612894400,
                'method'                    => 'emandate',
                'auth_type'                 => 'migrated',
                'account_number'            => 'HDFC00000000001',
                'ifsc'                      => 'HDFC0000007',
                'bank'                      => 'HDFC',
                'gateway_token'             => 'HDFC0000038433903433',
                'account_type'              => 'savings',
                'expired_at'                => '2088888888',
                'max_amount'                => 1000000,
                'currency'                  => 'INR',
                'terminal_id'               => 'testhdfcrandom',
                'merchant_id'               => '1cXSLlUU8X8sXl',
                'emandate_terminal_enabled' => true,
                'nach_terminal_enabled'     => true,
                'emandate_payment_enabled'  => true,
                'nach_payment_enabled'      => true,
                'debit_type'                => 'max_amount',
                'frequency'                 => 'adhoc',
                'description'               => 'test migration link',
                'notes'                     => ['custom 2' => 'not mandatory']
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'token',
                'method'    => 'emandate',
                'recurring' => true,
                'auth_type' => 'migrated',
            ],
        ],
    ],

    'testFailedNachMigrationInvalidUMRN' => [
        'request' => [
            'url'     => '/subscription_registration/migration',
            'method'  => 'post',
            'content' => [
                'customer'                  => [
                    'name' => 'JayKumar',
                    'contact' => '9123456780',
                    'email' => 'abc@gmail.com'
                ],
                'start_time'                => 1612894400,
                'method'                    => 'emandate',
                'auth_type'                 => 'migrated',
                'account_number'            => 'HDFC00000000001',
                'ifsc'                      => 'HDFC0000007',
                'bank'                      => 'HDFC',
                'gateway_token'             => '',
                'account_type'              => 'savings',
                'expired_at'                => '2088888888',
                'max_amount'                => 1000000,
                'currency'                  => 'INR',
                'terminal_id'               => 'testhdfcrandom',
                'merchant_id'               => '1cXSLlUU8X8sXl',
                'emandate_terminal_enabled' => true,
                'nach_terminal_enabled'     => true,
                'emandate_payment_enabled'  => true,
                'nach_payment_enabled'      => true,
                'debit_type'                => 'max_amount',
                'frequency'                 => 'adhoc',
                'description'               => 'test registration link',
                'notes'                     => ['custom 2' => 'not mandatory']
            ],
        ],

        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid UMRN for token migration',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

];
