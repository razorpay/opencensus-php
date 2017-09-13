<?php

namespace RZP\Tests\Functional\Setting;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [

    'testGetOpenwalletDefinedSettings' => [
        'request'  => [
            'url'    => '/settings/openwallet/defined_keys',
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
            'url'    => '/settings/invalid/defined_keys',
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

    'testGetForInvalidModule' => [
        'request'   => [
            'url'    => '/settings/invalid',
            'method' => 'get',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The module specified is invalid',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSaveAndRetrieveOpenwalletSettings' => [
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
    ],

    'testGetAllOpenwalletSettings' => [
        'request'  => [
            'url'    => '/settings/openwallet',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'settings' => [
                    'key1'       => 'value1',
                    'nested_key' => [
                        'key2' => 'value2'
                    ]
                ]
            ],
        ],
    ],

    'testGetSingleOpenwalletSetting' => [
        'request'  => [
            'url'    => '/settings/openwallet/nested_key',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'settings' => [
                    'key2' => 'value2'
                ]
            ],
        ],
    ],

    'testDeleteSettingKey' => [
        'request'  => [
            'url'     => '/settings/openwallet/nested_key',
            'method'  => 'delete',
        ],
        'response' => [
            'content' => [
                'success' => true
            ],
        ],
    ]

];
