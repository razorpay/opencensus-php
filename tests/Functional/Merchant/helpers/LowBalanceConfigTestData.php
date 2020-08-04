<?php

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testUpdateNotificationEmailsForLowBalanceConfig' => [
        'request' => [
            'url'     => '/low_balance_configs',
            'method'  => 'PATCH',
            'content' => [
                'notification_emails' => ['kachra.seth@razorpay.com','dhariya.babu@xyz.com'],
            ],
        ],
        'response' => [
            'content' => [
                'notification_emails' => ['kachra.seth@razorpay.com','dhariya.babu@xyz.com'],
            ]
        ]
    ],

    'testCreateLowBalanceConfigWhenAConfigAlreadyExists' => [
        'request' => [
            'url'     => '/low_balance_configs',
            'method'  => 'POST',
            'content' => [
                'account_number'      => '2224440041626905',
                'threshold_amount'    => 1000,
                'notification_emails' => ['kunal.sikri@razorpay.com', 'abcd@razorpay.com'],
                'notify_after'        => 6
            ],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Low balance config already exists for account number',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_LOW_BALANCE_CONFIG_ALREADY_EXISTS_FOR_ACCOUNT_NUMBER,
        ],
    ],

    'testUpdateThresholdAmountForLowBalanceConfig' => [
        'request' => [
            'url'     => '/low_balance_configs',
            'method'  => 'PATCH',
            'content' => [
                'threshold_amount' => 900,
            ],
        ],
        'response' => [
            'content' => [
                'threshold_amount' => '900',
            ]
        ]
    ],

    'testUpdateNotifyAfterForLowBalanceConfig' => [
        'request' => [
            'url'     => '/low_balance_configs',
            'method'  => 'PATCH',
            'content' => [
                'notify_after' => 8
            ],
        ],
        'response' => [
            'content' => [
                'notify_after' => '8'
            ]
        ]
    ],

    'testUpdateNotifyAfterForLowBalanceConfigForNonOwnerUser' => [
        'request' => [
            'url'     => '/low_balance_configs',
            'method'  => 'PATCH',
            'content' => [
                'notify_after' => 8
            ],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Authentication failed',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testCreateLowBalanceConfigForNonOwnerUser' => [
        'request' => [
            'url'     => '/low_balance_configs',
            'method'  => 'POST',
            'content' => [
                'account_number'      => '2224440041626905',
                'threshold_amount'    => 1000,
                'notification_emails' => ['kunal.sikri@razorpay.com', 'abcd@razorpay.com'],
                'notify_after'        => 6
            ],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Authentication failed',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testDeleteLowBalanceConfig' => [
        'request' => [
            'url'     => '/low_balance_configs',
            'method'  => 'DELETE',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'deleted' => true
            ]
        ]
    ],

    'testDeleteLowBalanceConfigForNonOwnerUser' => [
        'request' => [
            'url'     => '/low_balance_configs',
            'method'  => 'DELETE',
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Authentication failed',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testDisableLowBalanceConfig' => [
        'request' => [
            'url'     => '/low_balance_configs',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'disabled'
            ]
        ]
    ],

    'testDisableLowBalanceConfigForNonOwnerUser' => [
        'request' => [
            'url'     => '/low_balance_configs',
            'method'  => 'DELETE',
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Authentication failed',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testEnableLowBalanceConfig' => [
        'request' => [
            'url'     => '/low_balance_configs',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'enabled'
            ]
        ]
    ],

    'testEnableLowBalanceConfigForNonOwnerUser' => [
        'request' => [
            'url'     => '/low_balance_configs',
            'method'  => 'DELETE',
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Authentication failed',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testGetLowBalanceConfigById' => [
        'request' => [
            'url'     => '/low_balance_configs',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'account_number'      => '2224440041626905',
                'threshold_amount'    => 1000,
                'notification_emails' => ['kunal.sikri@razorpay.com','abcd@razorpay.com'],
                'notify_after'        => 6,
                'status'              => 'enabled',
            ]
        ]
    ],

    'testFetchMultipleLowBalanceConfigs' => [
        'request' => [
            'url'     => '/low_balance_configs',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity'   => 'collection',
                'count'    => 2,
                'has_more' => false,
                'items' => [
                    [
                        'threshold_amount'    => 100,
                        'notification_emails' => ['rtz@razorpay.com','xyz@razorpay.com'],
                        'notify_after'        => 9,
                        'status'              => 'enabled',
                    ],
                    [
                        'notification_emails' => ['kunal.sikri@razorpay.com','abcd@razorpay.com'],
                        'status'              => 'enabled',
                        'threshold_amount'    => 1000,
                        'notify_after'        => 6,
                    ],
                ],
            ],
        ],
    ],

    'testCreateLowBalanceConfigInTestMode' => [
        'request' => [
            'url'     => '/low_balance_configs',
            'method'  => 'POST',
            'content' => [
                'account_number'      => '2224440041626905',
                'threshold_amount'    => 1000,
                'notification_emails' => ['kunal.sikri@razorpay.com', 'abcd@razorpay.com'],
                'notify_after'        => 6
            ],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Low balance config is not supported in test mode',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_LOW_BALANCE_CONFIG_IS_NOT_SUPPORTED_IN_TEST_MODE,
        ],
    ],
];
