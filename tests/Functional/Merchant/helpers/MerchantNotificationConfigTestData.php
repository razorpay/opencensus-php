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
                'notification_emails'         => ['pullak.barik@razorpay.com', 'pullak10@gmail.com'],
                'notification_mobile_numbers' => ['9468620969'],
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'notification_emails'         => ['pullak.barik@razorpay.com', 'pullak10@gmail.com'],
                'notification_mobile_numbers' => ['9468620969'],
                'config_status'               => 'enabled',
                'notification_type'           => 'bene_bank_downtime',
            ]
        ],
    ],

    'testCreateMerchantNotificationConfigAsAdmin' => [
        'request'  => [
            'url'     => '/admin/merchants/10000000000000/merchant_notification_configs',
            'method'  => 'POST',
            'content' => [
                'notification_emails'         => ['pullak.barik@razorpay.com', 'pullak10@gmail.com'],
                'notification_mobile_numbers' => ['9468620969'],
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'notification_type'           => 'bene_bank_downtime',
                'notification_emails'         => ['pullak.barik@razorpay.com', 'pullak10@gmail.com'],
                'notification_mobile_numbers' => ['9468620969'],
                'config_status'               => 'enabled',
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

    'testCreateMerchantNotificationConfigWithNotificationTypeAndWithoutMobileNumbersAsAdmin' => [
        'request'  => [
            'url'     => '/admin/merchants/10000000000000/merchant_notification_configs',
            'method'  => 'POST',
            'content' => [
                'notification_type'           => 'fund_loading_downtime',
                'notification_emails'         => ['sagnik1@razorpay.com', 'sagnik2@gmail.com'],
                'notification_mobile_numbers' => [],
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'notification_type'           => 'fund_loading_downtime',
                'notification_emails'         => ['sagnik1@razorpay.com', 'sagnik2@gmail.com'],
                'notification_mobile_numbers' => [],
                'config_status'               => 'enabled',
            ]
        ],

    ],

    'testCreateMerchantNotificationConfigWithoutMobileAndEmail' => [
        'request'   => [
            'url'     => '/admin/merchants/10000000000000/merchant_notification_configs',
            'method'  => 'POST',
            'content' => [
                'notification_type'           => 'fund_loading_downtime',
                'notification_emails'         => [],
                'notification_mobile_numbers' => [],
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'BOTH_EMAIL_AND_MOBILE_FIELDS_CANNOT_BE_EMPTY'
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
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
                    'description' => 'A merchant notification config already exists for the given notification type.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_ALREADY_EXISTS_FOR_NOTIFICATION_TYPE,
        ],

    ],
    'testCreateMerchantNotificationConfigWhenConfigAlreadyExists' => [
        'request'   => [
            'url'     => '/merchant_notification_configs',
            'method'  => 'POST',
            'content' => [
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
                    'description' => 'A merchant notification config already exists for the given notification type.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_ALREADY_EXISTS_FOR_NOTIFICATION_TYPE,
        ],
    ],

    'testCreateMerchantNotificationConfigAsAdminWhenConfigAlreadyExists' => [
        'request'   => [
            'url'     => '/admin/merchants/10000000000000/merchant_notification_configs',
            'method'  => 'POST',
            'content' => [
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
                    'description' => 'A merchant notification config already exists for the given notification type.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_ALREADY_EXISTS_FOR_NOTIFICATION_TYPE,
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
                'notification_emails'         => ['tipsByCrizal@razorpay.com', 'chiruyu@xyz.com'],
                'notification_mobile_numbers' => ['9468620969'],
                'config_status'               => 'enabled',
                'notification_type'           => 'bene_bank_downtime',
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
                'notification_type'           => 'bene_bank_downtime',
                'notification_emails'         => ['tipsByCrizal@razorpay.com', 'chiruyu@xyz.com'],
                'notification_mobile_numbers' => ['9468620969'],
                'config_status'               => 'enabled',
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
                'notification_type'           => 'bene_bank_downtime',
                'notification_emails'         => ['pullak.barik@razorpay.com', 'pullak10@gmail.com'],
                'notification_mobile_numbers' => ['9876543012', '9876767121', '8123479788', '7532400000'],
                'config_status'               => 'enabled',
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
                'notification_type'           => 'bene_bank_downtime',
                'notification_emails'         => ['pullak.barik@razorpay.com', 'pullak10@gmail.com'],
                'notification_mobile_numbers' => ['9876543012', '9876767121', '8123479788', '7532400000'],
                'config_status'               => 'enabled',
            ],
        ],
    ],

    'testUpdateMerchantNotificationConfigWithNoEmailIdsAsAdmin' => [
        'request'  => [
            'url'     => '/merchant_notification_configs',
            'method'  => 'PATCH',
            'content' => [
                'notification_emails' => [],
                'notification_mobile_numbers' => ["8145800000"],
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'notification_type'           => 'bene_bank_downtime',
                'notification_emails'         => [],
                'notification_mobile_numbers' => ['8145800000'],
                'config_status'               => 'enabled',
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
                'notification_type'           => 'bene_bank_downtime',
                'notification_emails'         => ['pullak.barik@razorpay.com', 'pullak10@gmail.com'],
                'notification_mobile_numbers' => ['9468620969'],
                'config_status'               => 'enabled',
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
                'notification_type'           => 'bene_bank_downtime',
                'notification_emails'         => ['pullak.barik@razorpay.com', 'pullak10@gmail.com'],
                'notification_mobile_numbers' => ['9468620969'],
                'config_status'               => 'enabled',
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
                        'notification_type'           => 'bene_bank_downtime',
                        'notification_emails'         => ['pullak.barik@razorpay.com', 'pullak10@gmail.com'],
                        'notification_mobile_numbers' => ['9468620969'],
                    ],
                    [
                        'notification_type'           => 'fund_loading_downtime',
                        'notification_emails'         => ['test@razorpay.com', 'test@gmail.com'],
                        'notification_mobile_numbers' => ['9587612341'],
                    ],
                ],
            ],
        ],
    ],

    'testFetchMultipleMerchantNotificationConfigsAsAdmin' => [
        'request'  => [
            'url'     => '/admin/merchants/10000000000000/merchant_notification_configs?notification_type=fund_loading_downtime',
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
                        'notification_type'           => 'fund_loading_downtime',
                        'notification_emails'         => ['test1@razorpay.com', 'test11@gmail.com'],
                        'notification_mobile_numbers' => ['9587612341','814582777'],
                    ],
                    [
                        'notification_type'           => 'fund_loading_downtime',
                        'notification_emails'         => ['test2@razorpay.com', 'test22@gmail.com'],
                        'notification_mobile_numbers' => ['9587612341','6363200000'],
                    ],
                ],
            ],
        ],
    ],

    'testFetchMultipleNotificationConfigsWithQueryParamsAsAdmin' => [
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
                'entity' => 'collection',
                'count'  => 1,
                'items' => [
                    [
                        'id'                          => 'mnc_abcdefghiljkmn',
                        'merchant_id'                 => '10000000000000',
                        'notification_type'           => 'fund_loading_downtime',
                        'notification_emails'         => ['test1@razorpay.com', 'test11@gmail.com'],
                        'notification_mobile_numbers' => ['9587612341', '814582777'],
                    ]
                ],
            ],
        ],
    ],
];
