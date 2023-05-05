<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreatePayoutModeConfig' => [
        'request'  => [
            'url'     => '/payouts/da_payout_mode_config',
            'method'  => 'post',
            'content' => [
                'merchant_id' => 10000000000000,
                'fields' => [
                    'allowed_upi_channels' => ['axis']
                ],
            ]
        ],
        'response' => [
            'content' => [
                'success' => true
            ],
            'status_code' => 200,
        ],
    ],

    'testCreatePayoutModeConfigFailureFromDcsLayer' => [
        'request'  => [
            'url'     => '/payouts/da_payout_mode_config',
            'method'  => 'post',
            'content' => [
                'merchant_id' => 10000000000000,
                'fields' => [
                    'allowed_upi_channels' => ['axis']
                ],
            ]
        ],
        'response' => [
            'content' => [
                "success" => false,
            ],
            'status_code' => 200,
        ],
    ],

    'testCreatePayoutModeConfigValidationFailure' => [
        'request'  => [
            'url'     => '/payouts/da_payout_mode_config',
            'method'  => 'post',
            'content' => [
                'merchant_id' => 10000000000000,
                'fields' => [
                    'allowed_upi_channels' => 'axis'
                ],
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testEditPayoutModeConfig' => [
        'request'  => [
            'url'     => '/payouts/da_payout_mode_config',
            'method'  => 'patch',
            'content' => [
                'merchant_id' => 10000000000000,
                'fields' => [
                    'allowed_upi_channels' => ['yesbank']
                ],
            ]
        ],
        'response' => [
            'content' => [
                'success' => true
            ],
            'status_code' => 200,
        ],
    ],

    'testEditPayoutModeConfigValidationFailure' => [
        'request'  => [
            'url'     => '/payouts/da_payout_mode_config',
            'method'  => 'patch',
            'content' => [
                'merchant_id' => 10000000000000,
                'fields' => [
                    'allowed_upi_channels' => 'axis'
                ],
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testFetchPayoutModeConfig' => [
        'request'  => [
            'url'     => '/payouts/da_payout_mode_config',
            'method'  => 'get',
            'content' => [
                'merchant_id' => 10000000000000,
            ]
        ],
        'response' => [
            'content' => [
                "allowed_upi_channels" => ["axis"]
            ],
            'status_code' => 200,
        ],
    ],

    'testFetchPayoutModeConfigValidationFailure' => [
        'request'  => [
            'url'     => '/payouts/da_payout_mode_config',
            'method'  => 'get',
            'content' => [
                'dummy' => 10000000000000,
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'dummy is/are not required and should not be sent'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\ExtraFieldsException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED
        ],
    ],
];
