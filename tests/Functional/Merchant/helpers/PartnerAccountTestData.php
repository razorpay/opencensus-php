<?php

namespace RZP\Tests\Functional\Merchant\Account;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Merchant\Account\Constants;

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
                            'type'          => 'registered',
                            'line1'         => 'registered',
                            'line2'         => 'near Jamnalal Police Stn',
                            'city'          => 'BENGALURU',
                            'district_name' => 'BENGALURU',
                            'state'         => 'KARNATAKA',
                            'pin'           => '560032',
                            'country'       => 'India',
                        ],
                        [
                            'type'          => 'operation',
                            'line1'         => 'operation',
                            'line2'         => 'near Jamnalal Police Stn',
                            'city'          => 'BENGALURU',
                            'district_name' => 'BENGALURU',
                            'state'         => 'KARNATAKA',
                            'pin'           => '560032',
                            'country'       => 'India',
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
                    'identification'    => [
                        [
                            'type'                  => 'company_pan',
                            'identification_number' => 'apsdf1234a',
                        ],
                        [
                            'type'                  => 'gstin',
                            'identification_number' => '27APIPM9598J1ZW',
                        ],
                    ],
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
                            'type'          => 'registered',
                            'line1'         => 'registered',
                            'line2'         => 'near Jamnalal Police Stn',
                            'city'          => 'BENGALURU',
                            'district_name' => 'BENGALURU',
                            'state'         => 'KARNATAKA',
                            'country'       => 'India',
                            'pin'           => '560032',
                        ],
                        [
                            'type'          => 'operation',
                            'line1'         => 'operation',
                            'line2'         => 'near Jamnalal Police Stn',
                            'city'          => 'BENGALURU',
                            'district_name' => 'BENGALURU',
                            'state'         => 'KARNATAKA',
                            'country'       => 'India',
                            'pin'           => '560032',
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
                    'identification'    => [
                        [
                            'type' => 'company_pan',
                            'identification_number' => 'apsdf1234a',
                        ],
                        [
                            'type'                  => 'gstin',
                            'identification_number' => '27APIPM9598J1ZW',
                        ],
                    ],
                ],
                'payment'    => [
                    'flash_checkout' => true,
                    'international'  => true,
                ],
                'settlement' => [
                    'fund_accounts' => [
                        [
                            'bank_account' => [
                                'ifsc'           => 'ICIC0000031',
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
                            'type'          => 'registered',
                            'line1'         => 'registered',
                            'line2'         => 'near Jamnalal Police Stn',
                            'city'          => 'BENGALURU',
                            'district_name' => 'BENGALURU',
                            'state'         => 'KARNATAKA',
                            'pin'           => '560032',
                            'country'       => 'India',
                        ],
                    ],
                    'name'              => 'Ratnalal Jewellers',
                    'mcc'               => 7011,
                    'billing_label'     => 'Ratnalal',
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
                            'type'          => 'registered',
                            'line1'         => 'registered',
                            'line2'         => 'near Jamnalal Police Stn',
                            'city'          => 'BENGALURU',
                            'district_name' => 'BENGALURU',
                            'state'         => 'KARNATAKA',
                            'country'       => 'India',
                            'pin'           => '560032',
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
                    'billing_label'     => 'Ratnalal',
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
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_EMAIL_ALREADY_EXISTS,
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
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_INVALID_MCC_CODE,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_INVALID_MCC_CODE,
        ],
    ],

    'testCreateAccountWithoutRegisteredAddress' => [
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ACCOUNT_REGISTRATION_ADDRESS_REQUIRED,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCOUNT_REGISTRATION_ADDRESS_REQUIRED,
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
                            'type'          => 'registered',
                            'line1'         => 'registered',
                            'line2'         => 'near Jamnalal Police Stn',
                            'city'          => 'BENGALURU',
                            'district_name' => 'BENGALURU',
                            'state'         => 'KARNATAKA',
                            'pin'           => '560032',
                            'country'       => 'India',
                        ],
                    ],
                    'name'              => 'Ratnalal Jewellers',
                    'mcc'               => 7011,
                    'billing_label'     => 'Ratnalal',
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

    'testEditAccount' => [
        'request'  => [
            'url'     => '/accounts/{id}',
            'method'  => 'PATCH',
            'content' => [
                'phone'           => '8888888888',
                'notes'           => [
                    'new_key' => 'new value',
                ],
                'profile'         => [
                    'addresses' => [
                        [
                            'type'          => 'registered',
                            'line1'         => 'new registered',
                            'line2'         => 'new near Jamnalal Police Stn',
                            'city'          => 'new BENGALURU',
                            'state'         => 'ANDHRA PRADESH',
                            'pin'           => '560031',
                            'country'       => 'Malaysia',
                        ],
                        [
                            'type'          => 'operation',
                            'line1'         => 'new operation',
                            'line2'         => 'new near Jamnalal Police Stn',
                            'city'          => 'new BENGALURU',
                            'district_name' => 'new BENGALURU',
                            'state'         => 'ANDHRA PRADESH',
                            'pin'           => '560031',
                            'country'       => 'Malaysia',
                        ],
                    ],
                    'name'              => 'New Ratnalal Jewellers',
                    'description'       => 'New This is a test business',
                    'business_model'    => 'B2C',
                    'mcc'               => 8931,
                    'brand'             => [
                        'icon'  => 'https://newrtll.com/file/icon.jpg',
                        'logo'  => 'https://newrtll.com/file/logo.jpg',
                        'color' => 'FF5734',
                    ],
                    'dashboard_display' => 'New Ratnalal',
                    'website'           => 'https://Newmedium.com',
                    'apps'              => [
                        [
                            'name'  => 'New Ratnalal Shopping App',
                            'links' => [
                                'android' => 'https://playstore.google.com/appId/123',
                                'ios'     => 'https://appstore.com/appId/123',
                            ],
                        ],
                    ],
                    'support'           => [
                        'email'  => 'newsupport@rtll.com',
                        'phone'  => '8888888888',
                        'policy' => '24x7 support',
                        'url'    => 'https://rtll.com/support/',
                    ],
                    'chargeback'        => [
                        'email'  => 'support@rtll.com',
                        'phone'  => '8888888888',
                        'policy' => '24x7 support',
                        'url'    => 'https://rtll.com/support/',
                    ],
                    'refund'            => [
                        'email'  => 'support@rtll.com',
                        'phone'  => '8888888888',
                        'policy' => '24x7 support',
                        'url'    => 'https://rtll.com/support/',
                    ],
                    'dispute'           => [
                        'email'  => 'support@rtll.com',
                        'phone'  => '8888888888',
                        'policy' => '24x7 support',
                        'url'    => 'https://rtll.com/support/',
                    ],
                    'billing_label'     => 'New Ratnalal',
                ],
                'tnc' => [
                    'accepted'   => 1,
                    'ip_address' => '201.189.12.23',
                    'time'       => 1561110415,
                    'url'        => 'https://rtll.com/newtnc',
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4]',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'  => 'account',
                'managed' => 1,
                'notes'           => [
                    'new_key' => 'new value',
                ],
                'business_entity' => 'llp',
                'email'           => 'testcreateaccountaaa@razorpay.com',
                'phone'           => '8888888888',
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
                            'line1'   => 'new registered',
                            'line2'   => 'new near Jamnalal Police Stn',
                            'city'    => 'new BENGALURU',
                            'state'   => 'ANDHRA PRADESH',
                            'pin'     => '560031',
                            'country' => 'Malaysia',
                        ],
                        [
                            'type'    => 'operation',
                            'line1'   => 'new operation',
                            'line2'   => 'new near Jamnalal Police Stn',
                            'city'    => 'new BENGALURU',
                            'district_name' => 'new BENGALURU',
                            'state'   => 'ANDHRA PRADESH',
                            'pin'     => '560031',
                            'country' => 'Malaysia',
                        ],
                    ],
                    'name'              => 'New Ratnalal Jewellers',
                    'description'       => 'New This is a test business',
                    'business_model'    => 'B2C',
                    'mcc'               => 8931,
                    'brand'             => [
                        'icon'  => 'https://newrtll.com/file/icon.jpg',
                        'logo'  => 'https://newrtll.com/file/logo.jpg',
                        'color' => '#FF5734',
                    ],
                    'dashboard_display' => 'New Ratnalal',
                    'website'           => 'https://Newmedium.com',
                    'apps'              => [
                        [
                            'name'  => 'New Ratnalal Shopping App',
                            'links' => [
                                'android' => 'https://playstore.google.com/appId/123',
                                'ios'     => 'https://appstore.com/appId/123',
                            ],
                        ],
                    ],
                    'support'           => [
                        'email'  => 'newsupport@rtll.com',
                        'phone'  => '8888888888',
                        'policy' => '24x7 support',
                        'url'    => 'https://rtll.com/support/',
                    ],
                    'chargeback'        => [
                        'email'  => 'support@rtll.com',
                        'phone'  => '8888888888',
                        'policy' => '24x7 support',
                        'url'    => 'https://rtll.com/support/',
                    ],
                    'refund'            => [
                        'email'  => 'support@rtll.com',
                        'phone'  => '8888888888',
                        'policy' => '24x7 support',
                        'url'    => 'https://rtll.com/support/',
                    ],
                    'dispute'           => [
                        'email'  => 'support@rtll.com',
                        'phone'  => '8888888888',
                        'policy' => '24x7 support',
                        'url'    => 'https://rtll.com/support/',
                    ],
                    'billing_label'     => 'New Ratnalal',
                ],
                'payment'    => [
                    'flash_checkout' => true,
                    'international'  => true,
                ],
                'tnc'        => [
                    'accepted'   => 1,
                    'ip_address' => '201.189.12.23',
                    'time'       => 1561110415,
                    'url'        => 'https://rtll.com/newtnc',
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4]',
                ],
            ],
        ],
    ],

    'testEditThinAccount' => [
        'request'  => [
            'url'     => '/accounts/{id}',
            'method'  => 'PATCH',
            'content' => [
                'phone'           => '8888888888',
                'notes'           => [],
                'profile'         => [
                    'addresses' => [
                        [
                            'type'    => 'registered',
                            'city'    => 'Mangalore',
                        ],
                    ],
                    'brand'             => [
                        'icon'  => 'https://rtll.com/file/icon.jpg',
                        'logo'  => 'https://rtll.com/file/logo.jpg',
                        'color' => 'FF5733',
                    ],
                    'dashboard_display' => null,
                    'website'           => 'https://www.freecharge.in',
                    'apps'              => [],
                    'support'           => [
                        'phone'  => '9999999999',
                        'policy' => null,
                        'url'    => null,
                    ],
                ],
                'tnc' => [
                    'accepted'   => 1,
                    'ip_address' => '201.189.12.23',
                    'time'       => 1561110415,
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'  => 'account',
                'managed' => 1,
                'phone'   => '8888888888',
                'notes'   => [],
                'profile' => [
                    'addresses' => [
                        [
                            'type'          => 'registered',
                            'line1'         => 'registered',
                            'line2'         => 'near Jamnalal Police Stn',
                            'city'          => 'Mangalore',
                            'district_name' => 'BENGALURU',
                            'state'         => 'KARNATAKA',
                            'country'       => 'India',
                            'pin'           => '560032',
                        ],
                        [
                            'type'          => 'operation',
                            'line1'         => 'operation',
                            'line2'         => 'near Jamnalal Police Stn',
                            'city'          => 'BENGALURU',
                            'district_name' => 'BENGALURU',
                            'state'         => 'KARNATAKA',
                            'country'       => 'India',
                            'pin'           => '560032',
                        ],
                    ],
                    'name'              => 'Ratnalal Jewellers',
                    'description'       => 'This is a test business',
                    'business_model'    => 'B2B',
                    'mcc'               => 7011,
                    'brand'             => [
                        'icon'  => 'https://rtll.com/file/icon.jpg',
                        'logo'  => 'https://rtll.com/file/logo.jpg',
                        'color' => '#FF5733',
                    ],
                    'dashboard_display' => null,
                    'website'           => 'https://www.freecharge.in',
                    'apps'              => [],
                    'support'           => [
                        'email'  => 'support@rtll.com',
                        'phone'  => '9999999999',
                        'policy' => null,
                        'url'    => null,
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
                'tnc' => [
                    'accepted'   => 1,
                    'ip_address' => '201.189.12.23',
                    'time'       => 1561110415,
                ],
            ],
        ],
    ],

    'testEditPhoneNumber' => [
        'request'  => [
            'url'     => '/accounts/{id}',
            'method'  => 'PATCH',
            'content' => [
                'phone'           => '8888888888',
            ],
        ],
        'response' => [
            'content' => [
                'entity'  => 'account',
                'phone'   => '8888888888',
            ],
        ],
    ],

    'testEditProfileData' => [
        'request'  => [
            'url'     => '/accounts/{id}',
            'method'  => 'PATCH',
            'content' => [
                'profile' => [
                    'business_model' => 'B2C',
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'  => 'account',
                'profile' => [
                    'business_model' => 'B2C',
                ]
            ],
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
                            'type'          => 'registered',
                            'line1'         => 'registered',
                            'line2'         => 'near Jamnalal Police Stn',
                            'city'          => 'BENGALURU',
                            'district_name' => 'BENGALURU',
                            'state'         => 'KARNATAKA',
                            'country'       => 'India',
                            'pin'           => '560032',
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

    'testSimulateActivationForPartner' => [
        'request'  => [
            'url'    => '/partner/merchant/{id}/activation/status',
            'method' => 'PATCH',
            'content' => [
                'activation_status' => 'activated',
            ],
        ],
        'response' => [
            'content' => [
                'activation_status' => 'activated',
            ],
        ],
    ],

    'testSimulateUpdate' => [
        'request'  => [
            'url'    => '/partner/merchant/{id}/activation/update',
            'method' => 'PUT',
            'content' => [
                'business_name' => 'New Ratnalal Jewellers',
            ],
        ],
        'response' => [
            'content' => [
                'business_name' => 'New Ratnalal Jewellers',
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

    'submitKyc' => [
        'request'  => [
            'content' => [
                'submit' => true,
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
                'submitted'         => true,
                'activation_status' => 'under_review',
                'can_submit'        => true,
            ],
        ],
    ],

    'updateClarificationReason' => [
        'request'  => [
            'content' => [
                'kyc_clarification_reasons' => [
                    'clarification_reasons' => [
                        'address_proof_url' => [[
                                         'reason_type' => 'predefined',
                                         'field_type'  => 'document',
                                         'reason_code' => 'unable_to_validate_acc_number',
                                     ]],
                    ],
                ],
            ],
            'method'  => 'PUT',
        ],
        'response' => [
            'content'     => [
                'kyc_clarification_reasons' => [
                    'clarification_reasons' => [
                        'address_proof_url' => [[
                                         'reason_type' => 'predefined',
                                         'field_type'  => 'document',
                                         'reason_code' => 'unable_to_validate_acc_number',
                                     ]],
                    ],
                ],
            ],
        ],
    ],

    'changeActivationStatus' => [
        'request' => [
            'content' => [
                'activation_status'  => 'needs_clarification',
            ],
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'activation_status'  => 'needs_clarification',
            ],
        ],
    ],

    'testCreateAccountCompletelyFilledRequestWithKycNotHandled' => [
        'request'  => [
            'url'     => '/accounts',
            'method'  => 'POST',
            'content' => [
                'entity'          => 'account',
                'business_entity' => 'llp',
                'managed'         => 1,
                'email'           => 'testcreateAccountAAA@razorpay.com',
                'notes'           => [
                    'business_details' => 'This is a test business',
                    'key2'             => 'value2',
                    'account_access'   => 1,
                ],
                'contact_info' => [
                    'name'  => 'contact name',
                    'email' => 'contactemail@gmail.com',
                    'phone' => '9999999999',
                ],
                'profile'         => [
                    'addresses' => [
                        [
                            'type'          => 'REGISTERED',
                            'line1'         => 'registered',
                            'line2'         => 'near Jamnalal Police Stn',
                            'city'          => 'BENGALURU',
                            'state'         => 'KARNATAKA',
                            'pin'           => '560032',
                            'country'       => 'India',
                        ],
                        [
                            'type'          => 'operation',
                            'line1'         => 'operation',
                            'line2'         => 'near Jamnalal Police Stn',
                            'city'          => 'BENGALURU',
                            'state'         => 'KARNATAKA',
                            'pin'           => '560032',
                            'country'       => 'India',
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
                    'identification'    => [
                        [
                            'type'                  => 'company_pan',
                            'identification_number' => 'apsdf1234a',
                        ],
                        [
                            'type'                  => 'gstin',
                            'identification_number' => '27APIPM9598J1ZW',
                        ],
                    ],
                    'owner_info' => [
                        'name'           => 'owner name',
                        'identification' => [
                            [
                                'type'                  => 'owner_pan',
                                'identification_number' => 'asdfg1234a',
                            ],
                        ],
                    ],
                ],
                'settlement' => [
                    'fund_accounts'    => [
                        [
                            'bank_account' => [
                                'name'           => 'Ratnalal Account Name',
                                'account_number' => '1200012391',
                                'ifsc'           => 'ICIC0000031',
                                'notes'          => [
                                    'key1' => 'value1',
                                    'key2' => 'value2',
                                ],
                            ],
                        ],
                    ],
                ],
                'settings' => [
                    'payment' => [
                        'international' => true,
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
                'contact_info' => [
                    'name'  => 'contact name',
                    'email' => 'contactemail@gmail.com',
                    'phone' => '9999999999',
                ],
                'review_status'   => [
                    'current_state' => [
                        'status'             => null,
                        'payment_enabled'    => false,
                        'settlement_enabled' => false,
                    ],
                    'requirements' => [
                        'businesses' => [
                            'fields'    => [],
                            'documents' => [
                                [
                                    'type' => 'address_proof_url',
                                ],
                                [
                                    'type' => 'business_pan_url',
                                ],
                                [
                                    'type' => 'business_proof_url',
                                ],
                                [
                                    'type' => 'promoter_address_url',
                                ],
                            ],
                        ],
                    ],
                ],
                'profile'         => [
                    'addresses' => [
                        [
                            'type'          => 'registered',
                            'line1'         => 'registered',
                            'line2'         => 'near Jamnalal Police Stn',
                            'city'          => 'BENGALURU',
                            'state'         => 'KARNATAKA',
                            'country'       => 'India',
                            'pin'           => '560032',
                        ],
                        [
                            'type'          => 'operation',
                            'line1'         => 'operation',
                            'line2'         => 'near Jamnalal Police Stn',
                            'city'          => 'BENGALURU',
                            'state'         => 'KARNATAKA',
                            'country'       => 'India',
                            'pin'           => '560032',
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
                    'identification'    => [
                        [
                            'type' => 'company_pan',
                            'identification_number' => 'apsdf1234a',
                        ],
                        [
                            'type'                  => 'gstin',
                            'identification_number' => '27APIPM9598J1ZW',
                        ],
                    ],
                    'owner_info' => [
                        'name'           => 'owner name',
                        'identification' => [
                            [
                                'type'                  => 'owner_pan',
                                'identification_number' => 'asdfg1234a',
                            ],
                        ],
                    ],
                ],
                'settlement' => [
                    'fund_accounts'    => [
                        [
                            'bank_account' => [
                                'name'           => 'Ratnalal Account Name',
                                'account_number' => '1200012391',
                                'ifsc'           => 'ICIC0000031',
                                'notes'          => [
                                    'key1' => 'value1',
                                    'key2' => 'value2',
                                ],
                            ],
                            'status' => 'pending_verification',
                        ],
                    ],
                ],
                'settings' => [
                    'payment' => [
                        'international' => true,
                    ],
                ],
                'payment'    => [
                    'flash_checkout' => true,
                    'international'  => false,
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

    'testCreateAccountForThinRequestWithKycNotHandled' => [
        'request'  => [
            'url'     => '/accounts',
            'method'  => 'POST',
            'content' => [
                'entity'          => 'account',
                'business_entity' => 'ngo',
                'contact_info' => [
                    'name'  => 'contact name',
                    'email' => 'contactemail@gmail.com',
                    'phone' => '9999999999',
                ],
                'profile'         => [
                    'addresses' => [
                        [
                            'type'          => 'registered',
                            'line1'         => 'registered',
                            'line2'         => 'near Jamnalal Police Stn',
                            'city'          => 'BENGALURU',
                            'state'         => 'KARNATAKA',
                            'pin'           => '560032',
                            'country'       => 'India',
                        ],
                    ],
                    'name'              => 'Ratnalal Jewellers',
                    'mcc'               => 8398,
                    'billing_label'     => 'Ratnalal',
                    'identification'    => [
                        [
                            'type'                  => 'company_pan',
                            'identification_number' => 'apsdf1234a',
                        ],
                    ],
                    'owner_info' => [
                        'name'           => 'owner name',
                        'identification' => [
                            [
                                'type'                  => 'owner_pan',
                                'identification_number' => 'asdfg1234a',
                            ],
                        ],
                    ],
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
                'settings' => [
                    'payment' => [
                        'international' => true,
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'account',
                'email'           => 'test@razorpay.com',
                'business_entity' => 'ngo',
                'contact_info' => [
                    'name'  => 'contact name',
                    'email' => 'contactemail@gmail.com',
                    'phone' => '9999999999',
                ],
                'review_status'   => [
                    'current_state' => [
                        'status'             => null,
                        'payment_enabled'    => false,
                        'settlement_enabled' => false,
                    ],
                    'requirements' => [
                        'businesses' => [
                            'fields'    => [
                                [
                                    'field_name' => 'business_operation_address',
                                    'reason'     => Constants::REQUIRED_FIELD_MISSING,
                                ],
                                [
                                    'field_name' => 'business_operation_city',
                                ],
                                [
                                    'field_name' => 'business_operation_pin',
                                ],
                                [
                                    'field_name' => 'business_operation_state',
                                ],
                            ],
                            'documents' => [
                                [
                                    'type' => 'address_proof_url',
                                    'reason'     => Constants::REQUIRED_DOCUMENT_MISSING,
                                ],
                                [
                                    'type' => 'business_pan_url',
                                ],
                                [
                                    'type' => 'business_proof_url',
                                ],
                                [
                                    'type' => 'promoter_address_url',
                                ],
                                [
                                    'type' => 'form_12a_url',
                                ],
                                [
                                    'type' => 'form_80g_url',
                                ],
                            ],
                        ],
                    ],
                ],
                'profile'         => [
                    'addresses' => [
                        [
                            'type'          => 'registered',
                            'line1'         => 'registered',
                            'line2'         => 'near Jamnalal Police Stn',
                            'city'          => 'BENGALURU',
                            'state'         => 'KARNATAKA',
                            'country'       => 'India',
                            'pin'           => '560032',
                        ],
                    ],
                    'name'           => 'Ratnalal Jewellers',
                    'description'    => null,
                    'mcc'            => 8398,
                    'business_model' => null,
                    'brand' => [
                        'icon'  => null,
                        'logo'  => null,
                        'color' => null,
                    ],
                    'billing_label'     => 'Ratnalal',
                    'identification'    => [
                        [
                            'type' => 'company_pan',
                            'identification_number' => 'apsdf1234a',
                        ],
                    ],
                    'owner_info' => [
                        'name'           => 'owner name',
                        'identification' => [
                            [
                                'type'                  => 'owner_pan',
                                'identification_number' => 'asdfg1234a',
                            ],
                        ],
                    ],
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
                'settings' => [
                    'payment' => [
                        'international' => true,
                    ],
                ],
                'payment'    => [
                    'flash_checkout' => true,
                    'international'  => false,
                ],
            ],
        ],
    ],

    'testFetchAccountForKycNotHandledAndNeedsClarification' => [
        'request'  => [
            'url'    => '/accounts/{accountId}',
            'method' => 'GET',
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
                'contact_info' => [
                    'name'  => 'contact name',
                    'email' => 'contactemail@gmail.com',
                    'phone' => '9999999999',
                ],
                'review_status'   => [
                    'current_state' => [
                        'status'             => 'needs_clarification',
                        'payment_enabled'    => false,
                        'settlement_enabled' => false,
                    ],
                    'requirements' => [
                        'businesses' => [
                            'documents' => [
                                [
                                    'field_name' => 'address_proof_url',
                                    'reason'     => 'unable_to_validate_acc_number',
                                ],
                            ],
                        ],
                    ],
                ],
                'profile'         => [
                    'addresses' => [
                        [
                            'type'          => 'registered',
                            'line1'         => 'registered',
                            'line2'         => 'near Jamnalal Police Stn',
                            'city'          => 'BENGALURU',
                            'state'         => 'KARNATAKA',
                            'country'       => 'India',
                            'pin'           => '560032',
                        ],
                        [
                            'type'          => 'operation',
                            'line1'         => 'operation',
                            'line2'         => 'near Jamnalal Police Stn',
                            'city'          => 'BENGALURU',
                            'state'         => 'KARNATAKA',
                            'country'       => 'India',
                            'pin'           => '560032',
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
                    'identification'    => [
                        [
                            'type' => 'company_pan',
                            'identification_number' => 'apsdf1234a',
                        ],
                    ],
                    'owner_info' => [
                        'name'           => 'owner name',
                        'identification' => [
                            [
                                'type'                  => 'owner_pan',
                                'identification_number' => 'asdfg1234a',
                            ],
                        ],
                    ],
                ],
                'payment'    => [
                    'flash_checkout' => true,
                    'international'  => false,
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

    'testAddAccountUnderLegalEntityWithKycNotHandled' => [
        'request'  => [
            'url'     => '/accounts',
            'method'  => 'POST',
            'content' => [
                'entity'          => 'account',
                'business_entity' => 'llp',
                'contact_info' => [
                    'name'  => 'contact name',
                    'email' => 'contactemail@gmail.com',
                    'phone' => '9999999999',
                ],
                'profile'         => [
                    'addresses' => [
                        [
                            'type'          => 'registered',
                            'line1'         => 'registered',
                            'line2'         => 'near Jamnalal Police Stn',
                            'city'          => 'BENGALURU',
                            'state'         => 'KARNATAKA',
                            'pin'           => '560032',
                            'country'       => 'India',
                        ],
                        [
                            'type'          => 'operation',
                            'line1'         => 'registered',
                            'line2'         => 'near Jamnalal Police Stn',
                            'city'          => 'BENGALURU',
                            'state'         => 'KARNATAKA',
                            'pin'           => '560032',
                            'country'       => 'India',
                        ],
                    ],
                    'name'              => 'Ratnalal Jewellers',
                    'mcc'               => 7011,
                    'billing_label'     => 'Ratnalal',
                    'identification'    => [
                        [
                            'type'                  => 'company_pan',
                            'identification_number' => 'apsdf1234a',
                        ],
                    ],
                    'owner_info' => [
                        'name'           => 'owner name',
                        'identification' => [
                            [
                                'type'                  => 'owner_pan',
                                'identification_number' => 'asdfg1234a',
                            ],
                        ],
                    ],
                ],
                'settlement' => [
                    'fund_accounts'    => [
                        [
                            'bank_account' => [
                                'name'           => 'Ratnalal Account Name',
                                'account_number' => '1200012391',
                                'ifsc'           => 'ICIC0000031',
                                'notes'          => [
                                    'key1' => 'value1',
                                    'key2' => 'value2',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'account',
                'email'           => 'test@razorpay.com',
                'business_entity' => 'llp',
                'contact_info' => [
                    'name'  => 'contact name',
                    'email' => 'contactemail@gmail.com',
                    'phone' => '9999999999',
                ],
                'review_status'   => [
                    'current_state' => [
                        'status'             => 'under_review',
                        'payment_enabled'    => false,
                        'settlement_enabled' => false,
                    ],
                    'requirements' => [],
                ],
                'profile'         => [
                    'addresses' => [
                        [
                            'type'          => 'registered',
                            'line1'         => 'registered',
                            'line2'         => 'near Jamnalal Police Stn',
                            'city'          => 'BENGALURU',
                            'state'         => 'KARNATAKA',
                            'country'       => 'India',
                            'pin'           => '560032',
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
                    'billing_label'     => 'Ratnalal',
                    'identification'    => [
                        [
                            'type' => 'company_pan',
                            'identification_number' => 'apsdf1234a',
                        ],
                    ],
                    'owner_info' => [
                        'name'           => 'owner name',
                        'identification' => [
                            [
                                'type'                  => 'owner_pan',
                                'identification_number' => 'asdfg1234a',
                            ],
                        ],
                    ],
                ],
                'payment'    => [
                    'flash_checkout' => true,
                    'international'  => false,
                ],
            ],
        ],
    ],

    'testFetchAccountWitKycNotHandledAfterActivation' => [
        'request'  => [
            'url'     => '/accounts/{id}',
            'method'  => 'GET',
            'content' => [],
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
                'contact_info' => [
                    'name'  => 'contact name',
                    'email' => 'contactemail@gmail.com',
                    'phone' => '9999999999',
                ],
                'review_status'   => [
                    'current_state' => [
                        'status'             => 'activated',
                        'payment_enabled'    => true,
                        'settlement_enabled' => true,
                    ],
                    'requirements' => [],
                ],
                'profile'         => [
                    'addresses' => [
                        [
                            'type'          => 'registered',
                            'line1'         => 'registered',
                            'line2'         => 'near Jamnalal Police Stn',
                            'city'          => 'BENGALURU',
                            'state'         => 'KARNATAKA',
                            'country'       => 'India',
                            'pin'           => '560032',
                        ],
                        [
                            'type'          => 'operation',
                            'line1'         => 'operation',
                            'line2'         => 'near Jamnalal Police Stn',
                            'city'          => 'BENGALURU',
                            'state'         => 'KARNATAKA',
                            'country'       => 'India',
                            'pin'           => '560032',
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
                    'identification'    => [
                        [
                            'type'                  => 'company_pan',
                            'identification_number' => 'apsdf1234a',
                        ],
                        [
                            'type'                  => 'gstin',
                            'identification_number' => '27APIPM9598J1ZW',
                        ],
                    ],
                    'owner_info' => [
                        'name'           => 'owner name',
                        'identification' => [
                            [
                                'type'                  => 'owner_pan',
                                'identification_number' => 'asdfg1234a',
                            ],
                        ],
                    ],
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
                'payment'    => [
                    'flash_checkout' => true,
                    'international'  => true,
                ],
            ],
        ],
    ],
];
