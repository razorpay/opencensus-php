<?php

namespace RZP\Tests\Functional\OAuth;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

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
