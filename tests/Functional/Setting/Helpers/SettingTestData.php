<?php

namespace RZP\Tests\Functional\Setting;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [

    'testGetOpenwalletDefinedSettings' => [
        'request'  => [
            'url'    => '/settings/openwallet/defined',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                "settings" => [
                    'closed.max_limit'       => 'Max Balance',
                    'closed.max_load_value'  => 'Daily Load Limit',
                    'closed.max_load_txns'   => 'Daily Load Transactions Limit',
                    'closed.max_spend_value' => 'Daily Spend Limit',
                    'closed.max_spend_txns'  => 'Daily Spend Transactions Limit',
                ]
            ],
        ],
    ],

    'testGetDefinedSettingsInvalidModule' => [
        'request'   => [
            'url'    => '/settings/invalid/defined',
            'method' => 'get',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'No settings are defined for the module',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSaveOpenwalletSettings' => [
        'request'  => [
            'url'     => '/settings/openwallet',
            'method'  => 'post',
            'content' => [
                'key1'       => 'value1',
                'nested_key' => [
                    'key2' => 'value2'
                ]
            ]
        ],
        'response' => [
            'content' => [
                'success' => true
            ],
        ],
    ]

];
