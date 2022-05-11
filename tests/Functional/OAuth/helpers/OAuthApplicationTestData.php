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

    'testUpdateApplicationTypeFail' => [
        'request'  => [
            'url'     => '/oauth/applications/8ckeirnw84ifke',
            'method'  => 'POST',
            'content' => [
                'name' => 'apptestnew',
                "type" => "tally",
                "merchant_id" => "10000000000000",
                "client_details" => [
                    [
                        "id"    => "HQunkUT2hOwf18",
                        "type"  => "tally"
                    ],
                    [
                        "id"=> "HQunkqAstmVqhk",
                        "type"=> "tally"
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_APPLICATION_TYPE_UPDATE_NOT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_APPLICATION_TYPE_UPDATE_NOT_SUPPORTED
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

    'testRefreshClients' => [
        'request'  => [
            'url'     => '/oauth/applications/8ckeirnw84ifke/clients',
            'method'  => 'PUT',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [
                'id' => '8ckeirnw84ifke',
                "client_details" =>[
                    "dev"=>[
                        "id"=>"rzp_test_partner_randomDev"
                    ],
                    "prod"=>[
                        "id"=>"rzp_live_partner_randomProd"
                    ],
                ]],
        ],
    ],

    'testRefreshClientsWithError' => [
        'request'  => [
            'url'     => '/oauth/applications/8ckeirnw84ifke/clients',
            'method'  => 'PUT',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'   => \Exception::class,
            'message' => 'Error completing the request',
            'internal_error_code' => ErrorCode::SERVER_ERROR_AUTH_SERVICE_FAILURE,
        ],
    ],
];
