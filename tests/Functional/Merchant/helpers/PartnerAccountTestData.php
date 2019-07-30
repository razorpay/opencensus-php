<?php

namespace RZP\Tests\Functional\Merchant\Account;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    // completely filled request
    'testCreateAccountForCompletelyFilledRequest' => [
        'request'  => [
            'url'     => '/accounts',
            'method'  => 'POST',
            'content' => [
                'entity'          => 'account',
                'business_entity' => 'llp',
                'managed'         => 1,
                'email'           => 'testcreateAccountAAA@razorpay.com',
                'phone'           => '9999999999',
                'notes'           => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
                'profile'         => [
                    'addresses' => [
                        [
                            'type'    => 'registered',
                            'line1'   => 'registered',
                            'line2'   => 'near Jamnalal Police Stn',
                            'city'    => 'BENGALURU',
                            'state'   => 'Karnataka',
                            'pin'     => '560032',
                            'country' => 'India',
                        ],
                        [
                            'type'    => 'operation',
                            'line1'   => 'operation',
                            'line2'   => 'near Jamnalal Police Stn',
                            'city'    => 'BENGALURU',
                            'state'   => 'Karnataka',
                            'pin'     => '560032',
                            'country' => 'India',
                        ],
                    ],
                    'name'              => 'Ratnalal Jewellers',
                    'description'       => 'This is a test business',
                    'business_model'    => 'B2B',
                    'mcc'               => 7011,
                    'brand'             => [
                        'icon'  => 'https://rtll.com/file/icon.jpg',
                        'logo'  => 'https://rtll.com/file/logo.jpg',
                        'color' => 'FF5733',
                    ],
                    'dashboard_display' => 'Ratnalal',
                    'website'           => 'https://medium.com',
                    'apps'              => [
                        [
                            'name'  => 'Ratnalal Shopping App',
                            'links' => [
                                'android' => 'https://playstore.google.com/appId/122',
                                'ios'     => 'https://appstore.com/appId/122',
                            ],
                        ],
                    ],
                    'support'           => [
                        'email'  => 'support@rtll.com',
                        'phone'  => '9999999999',
                        'policy' => '24x7 support',
                        'url'    => 'https://rtll.com/support/',
                    ],
                    'chargeback'        => [
                        'email'  => 'support@rtll.com',
                        'phone'  => '9999999999',
                        'policy' => '24x7 support',
                        'url'    => 'https://rtll.com/support/',
                    ],
                    'refund'            => [
                        'email'  => 'support@rtll.com',
                        'phone'  => '9999999999',
                        'policy' => '24x7 support',
                        'url'    => 'https://rtll.com/support/',
                    ],
                    'dispute'           => [
                        'email'  => 'support@rtll.com',
                        'phone'  => '9999999999',
                        'policy' => '24x7 support',
                        'url'    => 'https://rtll.com/support/',
                    ],
                    'billing_label'     => 'Ratnalal',
                ],
                'settlement' => [
                    'balance_reserved' => '100000',
                    'schedules'        => [
                        [
                            'interval' => 3,
                        ],
                    ],
                    'fund_accounts'    => [
                        [
                            'contact_id'    => 'cont00011',
                            'bank_account' => [
                                'name'           => 'Ratnalal Account Name',
                                'account_number' => '1200012391',
                                'ifsc'           => 'ICIC0000031',
                            ],
                        ],
                    ],
                ],
                'tnc' => [
                    'accepted'   => 1,
                    'ip_address' => '201.189.12.23',
                    'time'       => 1561110415,
                    'url'        => 'https://rtll.com/tnc',
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4]',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'  => 'account',
                'managed' => 1,
                'notes'   => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
                'business_entity' => 'llp',
                'email'           => 'testcreateaccountaaa@razorpay.com',
                'phone'           => '9999999999',
                'review_status'   => [
                    'current_state' => [
                        'status'             => 'activated',
                        'payment_enabled'    => true,
                        'settlement_enabled' => true,
                    ],
                ],
                'profile'         => [
                    'addresses' => [
                        [
                            'type'    => 'registered',
                            'line1'   => 'registered',
                            'line2'   => 'near Jamnalal Police Stn',
                            'city'    => 'BENGALURU',
                            'state'   => 'Karnataka',
                            'country' => 'India',
                            'pin'     => '560032',
                        ],
                        [
                            'type'    => 'operation',
                            'line1'   => 'operation',
                            'line2'   => 'near Jamnalal Police Stn',
                            'city'    => 'BENGALURU',
                            'state'   => 'Karnataka',
                            'country' => 'India',
                            'pin'     => '560032',
                        ],
                    ],
                    'name'              => 'Ratnalal Jewellers',
                    'description'       => 'This is a test business',
                    'business_model'    => 'B2B',
                    'mcc'               => 7011,
                    'dashboard_display' => 'Ratnalal',
                    'website'           => 'https://medium.com',
                    'billing_label'     => 'Ratnalal',
                    'brand'             => [
                        'icon'  => 'https://rtll.com/file/icon.jpg',
                        'logo'  => 'https://rtll.com/file/logo.jpg',
                        'color' => '#FF5733',
                    ],
                    'apps'              => [
                        [
                            'name'  => 'Ratnalal Shopping App',
                            'links' => [
                                'android' => 'https://playstore.google.com/appId/122',
                                'ios'     => 'https://appstore.com/appId/122',
                            ],
                        ],
                    ],
                    'chargeback'        => [
                        'email'  => 'support@rtll.com',
                        'phone'  => '9999999999',
                        'policy' => '24x7 support',
                        'url'    => 'https://rtll.com/support/',
                    ],
                    'dispute'           => [
                        'email'  => 'support@rtll.com',
                        'phone'  => '9999999999',
                        'policy' => '24x7 support',
                        'url'    => 'https://rtll.com/support/',
                    ],
                    'refund'            => [
                        'email'  => 'support@rtll.com',
                        'phone'  => '9999999999',
                        'policy' => '24x7 support',
                        'url'    => 'https://rtll.com/support/',
                    ],
                    'support'           => [
                        'email'  => 'support@rtll.com',
                        'phone'  => '9999999999',
                        'policy' => '24x7 support',
                        'url'    => 'https://rtll.com/support/',
                    ],
                ],
                'payment'    => [
                    'flash_checkout' => true,
                    'international'  => false,
                ],
                'settlement' => [
                    'fund_accounts' => [
                        [
                            'bank_account' => [
                                'ifsc'           => 'ICIC0000031',
                                'bank_name'      => 'ICICI Bank',
                                'name'           => 'Ratnalal Account Name',
                                'account_number' => '1200012391',
                            ],
                        ],
                    ],
                ],
                'tnc'        => [
                    'accepted'   => 1,
                    'ip_address' => '201.189.12.23',
                    'time'       => 1561110415,
                    'url'        => 'https://rtll.com/tnc',
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4]',
                ],
            ],
        ],
    ],
    // thin request
    'testCreateAccountForThinRequest' => [
        'request'  => [
            'url'     => '/accounts',
            'method'  => 'POST',
            'content' => [
                'entity'          => 'account',
                'email'           => 'testcreateAccountAAb@razorpay.com',
                'phone'           => '9999999999',
                'profile'         => [
                    'addresses' => [
                        [
                            'type'    => 'registered',
                            'line1'   => 'registered',
                            'line2'   => 'near Jamnalal Police Stn',
                            'city'    => 'BENGALURU',
                            'state'   => 'Karnataka',
                            'pin'     => '560032',
                            'country' => 'India',
                        ],
                    ],
                    'name'              => 'Ratnalal Jewellers',
                    'mcc'               => 7011,
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'  => 'account',
                'email'           => 'testcreateaccountaab@razorpay.com',
                'phone'           => '9999999999',
                'review_status'   => [
                    'current_state' => [
                        'status'             => 'activated',
                        'payment_enabled'    => true,
                        'settlement_enabled' => true,
                    ],
                ],
                'profile'         => [
                    'addresses' => [
                        [
                            'type'    => 'registered',
                            'line1'   => 'registered',
                            'line2'   => 'near Jamnalal Police Stn',
                            'city'    => 'BENGALURU',
                            'state'   => 'Karnataka',
                            'country' => 'India',
                            'pin'     => '560032',
                        ],
                        [
                            'type'    => 'operation',
                            'line1'   => null,
                            'line2'   => null,
                            'city'    => null,
                            'state'   => null,
                            'country' => null,
                            'pin'     => null,
                        ],
                    ],
                    'name'           => 'Ratnalal Jewellers',
                    'description'    => null,
                    'mcc'            => 7011,
                    'business_model' => null,
                    'brand' => [
                        'icon'  => null,
                        'logo'  => null,
                        'color' => null,
                    ],
                ],
                'payment'    => [
                    'flash_checkout' => true,
                    'international'  => false,
                ],
            ],
        ],
    ],

    //duplicate email for merchant
    'testCreateAccountWithDuplicateEmail' => [
        'response' => [
            'content'     => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_EMAIL_ALREADY_EXISTS,
        ],
    ],

    // invalid mcc code
    'testCreateAccountWithInvalidMCCCode' => [
        'response' => [
            'content'     => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_INVALID_MCC_CODE,
        ],
    ],

    'testCreateAccountForInvalidPartner' => [
        'request'  => [
            'url'     => '/accounts',
            'method'  => 'POST',
            'content' => [
                'entity'          => 'account',
                'email'           => 'testcreateAccountAAb@razorpay.com',
                'phone'           => '9999999999',
                'profile'         => [
                    'addresses' => [
                        [
                            'type'    => 'registered',
                            'line1'   => 'registered',
                            'line2'   => 'near Jamnalal Police Stn',
                            'city'    => 'BENGALURU',
                            'state'   => 'Karnataka',
                            'pin'     => '560032',
                            'country' => 'India',
                        ],
                    ],
                    'name'              => 'Ratnalal Jewellers',
                    'mcc'               => 7011,
                ],
                'settlement' => [
                    'fund_accounts'    => [
                        [
                            'bank_account' => [
                                'name'           => 'Ratnalal Account Name',
                                'account_number' => '1200012391',
                                'ifsc'           => 'ICIC0000031',
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_PARTNER_ACTION,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
        ],
    ],

    'testFetchAccount' => [
        'request' => [
            'url'    => '/accounts/{accountId}',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'entity'  => 'account',
                'email'           => 'testcreateaccountaab@razorpay.com',
                'phone'           => '9999999999',
                'review_status'   => [
                    'current_state' => [
                        'status'             => 'activated',
                        'payment_enabled'    => true,
                        'settlement_enabled' => true,
                    ],
                ],
                'profile'         => [
                    'addresses' => [
                        [
                            'type'    => 'registered',
                            'line1'   => 'registered',
                            'line2'   => 'near Jamnalal Police Stn',
                            'city'    => 'BENGALURU',
                            'state'   => 'Karnataka',
                            'country' => 'India',
                            'pin'     => '560032',
                        ],
                    ],
                    'name'              => 'Ratnalal Jewellers',
                    'mcc'               => 7011,
                ],
                'payment'    => [
                    'flash_checkout' => true,
                    'international'  => false,
                ],
            ],
        ],
    ],

    'testFetchAllAccounts' => [
        'request'  => [
            'url'    => '/accounts',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testDisableAccountAction' => [
        'request'  => [
            'url'    => '/accounts/{accountId}/disable',
            'method' => 'PATCH',
        ],
        'response' => [
            'content' => [
                'review_status' => [
                    'current_state' => [
                        'status'             => 'suspended',
                        'payment_enabled'    => false,
                        'settlement_enabled' => false,
                    ],
                ],
            ],
        ],
    ],

    'testEnableAccountAction' => [
        'request'  => [
            'url'    => '/accounts/{accountId}/enable',
            'method' => 'PATCH',
        ],
        'response' => [
            'content' => [
                'review_status' => [
                    'current_state' => [
                        'status'             => 'activated',
                        'payment_enabled'    => true,
                        'settlement_enabled' => true,
                    ],
                ],
            ],
        ],
    ],
];
