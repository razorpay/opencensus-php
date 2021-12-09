<?php

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateMerchantNotificationConfig' => [
        'request'  => [
            'url'     => '/merchant_notification_configs',
            'method'  => 'POST',
            'content' => [
                'upper_threshold'             => 120,
                'lower_threshold'             => 12,
                'mode'                        => 'IMPS',
                'notify_after'                => 900,
                'notification_emails'         => ['pullak.barik@razorpay.com', 'pullak10@gmail.com'],
                'notification_mobile_numbers' => ['9468620969'],
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'upper_threshold'             => '120',
                'lower_threshold'             => '12',
                'notification_emails'         => ['pullak.barik@razorpay.com', 'pullak10@gmail.com'],
                'notification_mobile_numbers' => ['9468620969'],
                'config_status'               => 'enabled',
                'mode'                        => 'IMPS',
                'notify_after'                => '900',
            ]
        ],
    ],

    'testCreateMerchantNotificationConfigAsAdmin' => [
        'request'  => [
            'url'     => '/admin/merchants/10000000000000/merchant_notification_configs',
            'method'  => 'POST',
            'content' => [
                'upper_threshold'             => 120,
                'lower_threshold'             => 12,
                'mode'                        => 'IMPS',
                'notify_after'                => 900,
                'notification_emails'         => ['pullak.barik@razorpay.com', 'pullak10@gmail.com'],
                'notification_mobile_numbers' => ['9468620969'],
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'upper_threshold'             => '120',
                'lower_threshold'             => '12',
                'notification_emails'         => ['pullak.barik@razorpay.com', 'pullak10@gmail.com'],
                'notification_mobile_numbers' => ['9468620969'],
                'config_status'               => 'enabled',
                'mode'                        => 'IMPS',
                'notify_after'                => '900',
            ]
        ],
    ],

    'testCreateMerchantNotificationConfigWithNotificationTypeAsAdmin' => [
        'request'  => [
            'url'     => '/admin/merchants/10000000000000/merchant_notification_configs',
            'method'  => 'POST',
            'content' => [
                'notification_type'           => 'fund_loading_downtime',
                'notification_emails'         => ['sagnik1@razorpay.com', 'sagnik2@gmail.com'],
                'notification_mobile_numbers' => ['9468620969'],
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'notification_type'           => 'fund_loading_downtime',
                'notification_emails'         => ['sagnik1@razorpay.com', 'sagnik2@gmail.com'],
                'notification_mobile_numbers' => ['9468620969'],
                'config_status'               => 'enabled',
            ]
        ],

    ],
    'testCreateDuplicateMerchantNotificationConfigWithNotificationTypeAsAdmin' => [
        'request'  => [
            'url'     => '/admin/merchants/10000000000000/merchant_notification_configs',
            'method'  => 'POST',
            'content' => [
                'notification_type'           => 'fund_loading_downtime',
                'notification_emails'         => ['sagnik1@razorpay.com', 'sagnik2@gmail.com'],
                'notification_mobile_numbers' => ['9468620969'],
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'A merchant notification config already exists for the given mode.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_ALREADY_EXISTS_FOR_MODE,
        ],

    ],
    'testCreateMerchantNotificationConfigWhenConfigAlreadyExists' => [
        'request'   => [
            'url'     => '/merchant_notification_configs',
            'method'  => 'POST',
            'content' => [
                'upper_threshold'             => 120,
                'lower_threshold'             => 12,
                'mode'                        => 'IMPS',
                'notify_after'                => 900,
                'notification_emails'         => ['pullak.barik@razorpay.com', 'pullak10@gmail.com'],
                'notification_mobile_numbers' => ['9468620969'],
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'A merchant notification config already exists for the given mode.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_ALREADY_EXISTS_FOR_MODE,
        ],
    ],

    'testCreateMerchantNotificationConfigAsAdminWhenConfigAlreadyExists' => [
        'request'   => [
            'url'     => '/admin/merchants/10000000000000/merchant_notification_configs',
            'method'  => 'POST',
            'content' => [
                'upper_threshold'             => 120,
                'lower_threshold'             => 12,
                'mode'                        => 'IMPS',
                'notify_after'                => 900,
                'notification_emails'         => ['pullak.barik@razorpay.com', 'pullak10@gmail.com'],
                'notification_mobile_numbers' => ['9468620969'],
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'A merchant notification config already exists for the given mode.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_ALREADY_EXISTS_FOR_MODE,
        ],
    ],

    'testUpdateUpperThresholdForMerchantNotificationConfig' => [
        'request'  => [
            'url'     => '/merchant_notification_configs',
            'method'  => 'PATCH',
            'content' => [
                'upper_threshold' => 150,
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'upper_threshold'             => '150',
                'lower_threshold'             => 12,
                'notification_emails'         => ['pullak.barik@razorpay.com', 'pullak10@gmail.com'],
                'notification_mobile_numbers' => ['9468620969'],
                'config_status'               => 'enabled',
                'mode'                        => 'IMPS',
                'notify_after'                => 900,
            ],
        ],
    ],

    'testUpdateUpperThresholdForMerchantNotificationConfigAsAdmin' => [
        'request'  => [
            'url'     => '/merchant_notification_configs',
            'method'  => 'PATCH',
            'content' => [
                'upper_threshold' => 150,
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'upper_threshold'             => '150',
                'lower_threshold'             => 12,
                'notification_emails'         => ['pullak.barik@razorpay.com', 'pullak10@gmail.com'],
                'notification_mobile_numbers' => ['9468620969'],
                'config_status'               => 'enabled',
                'mode'                        => 'IMPS',
                'notify_after'                => 900,
            ],
        ],
    ],

    'testUpdateLowerThresholdForMerchantNotificationConfig' => [
        'request'  => [
            'url'     => '/merchant_notification_configs',
            'method'  => 'PATCH',
            'content' => [
                'lower_threshold' => 10,
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'upper_threshold'             => 120,
                'lower_threshold'             => '10',
                'notification_emails'         => ['pullak.barik@razorpay.com', 'pullak10@gmail.com'],
                'notification_mobile_numbers' => ['9468620969'],
                'config_status'               => 'enabled',
                'mode'                        => 'IMPS',
                'notify_after'                => 900,
            ],
        ],
    ],

    'testUpdateLowerThresholdForMerchantNotificationConfigAsAdmin' => [
        'request'  => [
            'url'     => '/merchant_notification_configs',
            'method'  => 'PATCH',
            'content' => [
                'lower_threshold' => 10,
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'upper_threshold'             => 120,
                'lower_threshold'             => '10',
                'notification_emails'         => ['pullak.barik@razorpay.com', 'pullak10@gmail.com'],
                'notification_mobile_numbers' => ['9468620969'],
                'config_status'               => 'enabled',
                'mode'                        => 'IMPS',
                'notify_after'                => 900,
            ],
        ],
    ],

    'testCreateMerchantNotificationConfigWithWrongThresholds' => [
        'request'   => [
            'url'     => '/merchant_notification_configs',
            'method'  => 'POST',
            'content' => [
                'upper_threshold'             => 12,
                'lower_threshold'             => 120,
                'mode'                        => 'IMPS',
                'notify_after'                => 900,
                'notification_emails'         => ['pullak.barik@razorpay.com', 'pullak10@gmail.com'],
                'notification_mobile_numbers' => ['9468620969'],
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The lower threshold is greater than the upper threshold.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_LOWER_THRESHOLD_GREATER_THAN_UPPER_THRESHOLD,
        ],
    ],

    'testCreateMerchantNotificationConfigAsAdminWithWrongThresholds' => [
        'request'   => [
            'url'     => '/admin/merchants/10000000000000/merchant_notification_configs',
            'method'  => 'POST',
            'content' => [
                'upper_threshold'             => 12,
                'lower_threshold'             => 120,
                'mode'                        => 'IMPS',
                'notify_after'                => 900,
                'notification_emails'         => ['pullak.barik@razorpay.com', 'pullak10@gmail.com'],
                'notification_mobile_numbers' => ['9468620969'],
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The lower threshold is greater than the upper threshold.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_LOWER_THRESHOLD_GREATER_THAN_UPPER_THRESHOLD,
        ],
    ],

    'testUpdateNotificationEmailsForMerchantNotificationConfig' => [
        'request'  => [
            'url'     => '/merchant_notification_configs',
            'method'  => 'PATCH',
            'content' => [
                'notification_emails' => ['tipsByCrizal@razorpay.com', 'chiruyu@xyz.com'],
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'upper_threshold'             => 120,
                'lower_threshold'             => 12,
                'notification_emails'         => ['tipsByCrizal@razorpay.com', 'chiruyu@xyz.com'],
                'notification_mobile_numbers' => ['9468620969'],
                'config_status'               => 'enabled',
                'mode'                        => 'IMPS',
                'notify_after'                => 900,
            ],
        ],
    ],

    'testUpdateNotificationEmailsForMerchantNotificationConfigAsAdmin' => [
        'request'  => [
            'url'     => '/merchant_notification_configs',
            'method'  => 'PATCH',
            'content' => [
                'notification_emails' => ['tipsByCrizal@razorpay.com', 'chiruyu@xyz.com'],
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'upper_threshold'             => 120,
                'lower_threshold'             => 12,
                'notification_emails'         => ['tipsByCrizal@razorpay.com', 'chiruyu@xyz.com'],
                'notification_mobile_numbers' => ['9468620969'],
                'config_status'               => 'enabled',
                'mode'                        => 'IMPS',
                'notify_after'                => 900,
            ],
        ],
    ],

    'testUpdateNotificationMobileNumbersForMerchantNotificationConfig' => [
        'request'  => [
            'url'     => '/merchant_notification_configs',
            'method'  => 'PATCH',
            'content' => [
                'notification_mobile_numbers' => ['9876543012', '9876767121', '8123479788', '7532400000'],
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'upper_threshold'             => 120,
                'lower_threshold'             => 12,
                'notification_emails'         => ['pullak.barik@razorpay.com', 'pullak10@gmail.com'],
                'notification_mobile_numbers' => ['9876543012', '9876767121', '8123479788', '7532400000'],
                'config_status'               => 'enabled',
                'mode'                        => 'IMPS',
                'notify_after'                => 900,
            ],
        ],
    ],

    'testUpdateNotificationMobileNumbersForMerchantNotificationConfigAsAdmin' => [
        'request'  => [
            'url'     => '/merchant_notification_configs',
            'method'  => 'PATCH',
            'content' => [
                'notification_mobile_numbers' => ['9876543012', '9876767121', '8123479788', '7532400000'],
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'upper_threshold'             => 120,
                'lower_threshold'             => 12,
                'notification_emails'         => ['pullak.barik@razorpay.com', 'pullak10@gmail.com'],
                'notification_mobile_numbers' => ['9876543012', '9876767121', '8123479788', '7532400000'],
                'config_status'               => 'enabled',
                'mode'                        => 'IMPS',
                'notify_after'                => 900,
            ],
        ],
    ],

    'testUpdateNotificationMobileNumbersForMerchantNotificationConfigAsAdminWithIncorrectMobileNumber' => [
        'request'  => [
            'url'     => '/merchant_notification_configs',
            'method'  => 'PATCH',
            'content' => [
                'notification_mobile_numbers' => ['94266', '+911000000000'],
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_INVALID_MOBILE_NUMBER',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateNotifyAfterForMerchantNotificationConfig' => [
        'request'  => [
            'url'     => '/merchant_notification_configs',
            'method'  => 'PATCH',
            'content' => [
                'notify_after' => 288
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'upper_threshold'             => 120,
                'lower_threshold'             => 12,
                'notification_emails'         => ['pullak.barik@razorpay.com', 'pullak10@gmail.com'],
                'notification_mobile_numbers' => ['9468620969'],
                'config_status'               => 'enabled',
                'mode'                        => 'IMPS',
                'notify_after'                => '288',
            ],
        ],
    ],

    'testUpdateNotifyAfterForMerchantNotificationConfigAsAdmin' => [
        'request'  => [
            'url'     => '/merchant_notification_configs',
            'method'  => 'PATCH',
            'content' => [
                'notify_after' => 288
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'upper_threshold'             => 120,
                'lower_threshold'             => 12,
                'notification_emails'         => ['pullak.barik@razorpay.com', 'pullak10@gmail.com'],
                'notification_mobile_numbers' => ['9468620969'],
                'config_status'               => 'enabled',
                'mode'                        => 'IMPS',
                'notify_after'                => '288',
            ],
        ],
    ],

    'testDeleteMerchantNotificationConfig' => [
        'request'  => [
            'url'     => '/merchant_notification_configs',
            'method'  => 'DELETE',
            'content' => [],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'deleted' => true
            ]
        ]
    ],

    'testDeleteMerchantNotificationConfigAsAdmin' => [
        'request'  => [
            'url'     => '/merchant_notification_configs',
            'method'  => 'DELETE',
            'content' => [],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'deleted' => true
            ]
        ]
    ],

    'testDisableMerchantNotificationConfig' => [
        'request'  => [
            'url'     => '/merchant_notification_configs',
            'method'  => 'POST',
            'content' => [],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'config_status' => 'disabled'
            ]
        ]
    ],

    'testDisableMerchantNotificationConfigAsAdmin' => [
        'request'  => [
            'url'     => '/merchant_notification_configs',
            'method'  => 'POST',
            'content' => [],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'config_status' => 'disabled'
            ]
        ]
    ],

    'testEnableMerchantNotificationConfig' => [
        'request'  => [
            'url'     => '/merchant_notification_configs',
            'method'  => 'POST',
            'content' => [],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'config_status' => 'enabled'
            ]
        ]
    ],

    'testEnableMerchantNotificationConfigAsAdmin' => [
        'request'  => [
            'url'     => '/merchant_notification_configs',
            'method'  => 'POST',
            'content' => [],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'config_status' => 'enabled'
            ]
        ]
    ],

    'testGetMerchantNotificationConfigById' => [
        'request'  => [
            'url'     => '/merchant_notification_configs',
            'method'  => 'GET',
            'content' => [],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'upper_threshold'             => 120,
                'lower_threshold'             => 12,
                'notification_emails'         => ['pullak.barik@razorpay.com', 'pullak10@gmail.com'],
                'notification_mobile_numbers' => ['9468620969'],
                'config_status'               => 'enabled',
                'mode'                        => 'IMPS',
                'notify_after'                => 900,
            ]
        ],
    ],

    'testGetMerchantNotificationConfigAsAdminById' => [
        'request'  => [
            'url'     => '/merchant_notification_configs',
            'method'  => 'GET',
            'content' => [],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'upper_threshold'             => 120,
                'lower_threshold'             => 12,
                'notification_emails'         => ['pullak.barik@razorpay.com', 'pullak10@gmail.com'],
                'notification_mobile_numbers' => ['9468620969'],
                'config_status'               => 'enabled',
                'mode'                        => 'IMPS',
                'notify_after'                => 900,
            ]
        ],
    ],

    'testFetchMultipleMerchantNotificationConfigs' => [
        'request'  => [
            'url'     => '/merchant_notification_configs',
            'method'  => 'GET',
            'content' => [],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'entity'   => 'collection',
                'count'    => 2,
                'has_more' => false,
                'items'    => [
                    [
                        'upper_threshold'             => 120,
                        'lower_threshold'             => 12,
                        'mode'                        => 'IMPS',
                        'notify_after'                => 900,
                        'notification_emails'         => ['pullak.barik@razorpay.com', 'pullak10@gmail.com'],
                        'notification_mobile_numbers' => ['9468620969'],
                    ],
                    [
                        'upper_threshold'             => 320,
                        'lower_threshold'             => 32,
                        'mode'                        => 'NEFT',
                        'notify_after'                => 1000,
                        'notification_emails'         => ['test@razorpay.com', 'test@gmail.com'],
                        'notification_mobile_numbers' => ['9587612341'],
                    ],
                ],
            ],
        ],
    ],

    'testFetchMultipleMerchantNotificationConfigsAsAdmin' => [
        'request'  => [
            'url'     => '/admin/merchants/10000000000000/merchant_notification_configs',
            'method'  => 'GET',
            'content' => [],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'entity'   => 'collection',
                'count'    => 2,
                'items'    => [
                    [
                        'upper_threshold'             => 120,
                        'lower_threshold'             => 12,
                        'mode'                        => 'IMPS',
                        'notify_after'                => 900,
                        'notification_emails'         => ['pullak.barik@razorpay.com', 'pullak10@gmail.com'],
                        'notification_mobile_numbers' => ['9468620969'],
                    ],
                    [
                        'upper_threshold'             => 320,
                        'lower_threshold'             => 32,
                        'mode'                        => 'NEFT',
                        'notify_after'                => 1000,
                        'notification_emails'         => ['test@razorpay.com', 'test@gmail.com'],
                        'notification_mobile_numbers' => ['9587612341'],
                    ],
                ],
            ],
        ],
    ],
];
