<?php

namespace RZP\Tests\Functional\OAuth;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateApplication' => [
        'request'  => [
            'url'     => '/oauth/applications',
            'method'  => 'POST',
            'content' => [
                'name'     => 'fdsfsd',
                'website'  => 'https://www.example.com',
                'logo_url' => '/logo/app_logo.png'
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testCreatePartnerApplication' => [
        'request'  => [
            'url'     => '/oauth/applications/partner',
            'method'  => 'POST',
            'content' => [
                'name'     => 'fdsfsd',
                'website'  => 'https://www.example.com',
                'logo_url' => '/logo/app_logo.png',
                'type'     => 'fully_managed',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testCreatePartnerApplicationPurePlatform' => [
        'request'   => [
            'url'     => '/oauth/applications/partner',
            'method'  => 'POST',
            'content' => [
                'name'     => 'fdsfsd',
                'website'  => 'https://www.example.com',
                'logo_url' => '/logo/app_logo.png',
                'type'     => 'fully_managed',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_PARTNER_ACTION,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION
        ],
    ],

    'testGetApplication' => [
        'request'  => [
            'url'     => '/oauth/applications/8ckeirnw84ifke',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testGetMultipleApplications' => [
        'request'  => [
            'url'     => '/oauth/applications',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testGetPartnerApplicationPurePlatform' => [
        'request'  => [
            'url'     => '/oauth/applications/partner',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_PARTNER_ACTION,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION
        ],
    ],

    'testGetPartnerApplicationBank' => [
        'request'  => [
            'url'     => '/oauth/applications/partner',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_PARTNER_ACTION,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION
        ],
    ],

    'testUpdateApplication' => [
        'request'  => [
            'url'     => '/oauth/applications/8ckeirnw84ifke',
            'method'  => 'POST',
            'content' => [
                'name' => 'apptestnew',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testDeleteApplication' => [
        'request'  => [
            'url'     => '/oauth/applications/8ckeirnw84ifke',
            'method'  => 'DELETE',
        ],
        'response' => [
            'content' => [],
        ],
    ],
];
