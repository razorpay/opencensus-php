<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateAdminLead' => [
        'request' => [
            'url' => '/admin-lead',
            'method' => 'post',
            'content' => [
                'channel_code'  => 'RZP001',
                'contact_email' => 'abc@xyz.com',
                'contact_name'  => 'test user'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED,
            'error_description' => PublicErrorDescription::BAD_REQUEST_ACCESS_DENIED,
        ],
    ],

    'testCreateAllowedAdminLead' => [
        'request' => [
            'url' => '/admin-lead',
            'method' => 'post',
            'content' => [
                'channel_code'  => 'RZP001',
                'contact_email' => 'abc@xyz.com',
                'contact_name'  => 'test user',
                "merchant_type"  => "Regular Test Merchant"
            ],
        ],
        'response' => [
            'content' => [
                'email' => 'abc@xyz.com',
                'form_data' => [
                    "channel_code"  => "RZP001",
                    "contact_email" => "abc@xyz.com",
                    "contact_name"  => "test user",
                    "merchant_type"  => "Regular Test Merchant"
                ],
            ],
            'status_code' => 200,
        ],
    ],
    'testCreateIsDsMerchantAdminLead' => [
        'request' => [
            'url' => '/admin-lead',
            'method' => 'post',
            'content' => [
                'channel_code'  => 'RZP001',
                'contact_email' => 'abc@xyz.com',
                'contact_name'  => 'test user',
                "is_ds_merchant"  => "1"
            ],
        ],
        'response' => [
            'content' => [
                'email' => 'abc@xyz.com',
                'form_data' => [
                    "channel_code"  => "RZP001",
                    "contact_email" => "abc@xyz.com",
                    "contact_name"  => "test user",
                    "merchant_type"  => "DS Only Merchant"
                ],
            ],
            'status_code' => 200,
        ],
    ],
    'testCreateCurlecAdminLead' => [
        'request' => [
            'url' => '/admin-lead',
            'method' => 'post',
            'content' => [
                'channel_code'  => 'RZP001',
                'contact_email' => 'abc@xyz.com',
                'contact_name'  => 'test user',
                'country_code'  => 'MY'
            ],
        ],
        'response' => [
            'content' => [
                'email' => 'abc@xyz.com',
                'form_data' => [
                    "channel_code"  => "RZP001",
                    "contact_email" => "abc@xyz.com",
                    "contact_name"  => "test user",
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testCreatePartnerAdminLead' => [
        'request' => [
            'url' => '/admin-lead',
            'method' => 'post',
            'content' => [
                'channel_code'  => 'RZP001',
                'contact_email' => 'haihello@xyz.com',
                'contact_name'  => 'test partner',
                "merchant_type"  => "Regular Test Partner"
            ],
        ],
        'response' => [
            'content' => [
                'email' => 'haihello@xyz.com',
                'form_data' => [
                    "channel_code"  => "RZP001",
                    "contact_email" => "haihello@xyz.com",
                    "contact_name"  => "test partner",
                    "merchant_type"  => "Regular Test Partner"
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testSelfInviteProhibited' => [
        'request' => [
            'url' => '/admin-lead',
            'method' => 'post',
            'content' => [
                'channel_code'  => 'RZP001',
                'contact_name'  => 'test user',
                "merchant_type"  => "Regular Test Merchant"
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ADMIN_SELF_INVITE_PROHIBITED,
            'error_description' => PublicErrorDescription::BAD_REQUEST_ADMIN_SELF_INVITE_PROHIBITED,
        ],
    ],
    'testExistingEmailInviteProhibited' => [
        'request' => [
            'url' => '/admin-lead',
            'method' => 'post',
            'content' => [
                'channel_code'  => 'RZP001',
                'contact_name'  => 'test user',
                "merchant_type"  => "Regular Test Merchant"
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EMAIL_ALREADY_EXISTS,
            'error_description' => PublicErrorDescription::BAD_REQUEST_EMAIL_ALREADY_EXISTS,
        ],
    ],

    'testVerifyAdminLead' => [
        'request' => [
            'url' => '/admin-lead/verify/%s',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'email' => 'abc@xyz.com',
                'form_data' => [
                    "channel_code"  => "RZP001",
                    "contact_email" => "abc@xyz.com",
                    "contact_name"  => "test user"
                ],
            ],
        ],
    ],
    'testVerifyMerchantInvitation' => [
        'request' => [
            'url' => '/merchant-invitation/verify/%s',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'email' => 'abc@xyz.com',
                'form_data' => [
                    "channel_code"  => "RZP001",
                    "contact_email" => "abc@xyz.com",
                    "contact_name"  => "test user"
                ],
            ],
        ],
    ],
    'testPutAdminLead' => [
        'request' => [
            'url' => '/admin-lead/%s',
            'method' => 'put',
            'content' => [
                'signed_up' => true,
            ],
        ],
        'response' => [
            'content' => [
                'email' => 'abc@xyz.com',
                'form_data' => [
                    "channel_code"  => "RZP001",
                    "contact_email" => "abc@xyz.com",
                    "contact_name"  => "test user"
                ],
            ],
            'status_code' => 200,
        ],
    ],
];
