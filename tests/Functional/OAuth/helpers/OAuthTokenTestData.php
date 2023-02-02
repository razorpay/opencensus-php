<?php

namespace RZP\Tests\Functional\OAuth;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testGetToken' => [
        'request'  => [
            'url'     => '/oauth/tokens/8ckeirnw84ifkg',
            'method'  => 'GET',
            'content' => [
                'merchant_id' => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testGetAllTokens' => [
        'request'  => [
            'url'     => '/oauth/tokens',
            'method'  => 'GET',
            'content' => [
                'merchant_id' => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testGetAllTokensForBankingRoute' => [
        'request'  => [
            'url'     => '/oauth/tokens',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'method'  => 'GET',
            'content' => [
                'merchant_id' => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ],
    ],

    'testRevokeToken' => [
        'request'  => [
            'url'     => '/oauth/tokens/8ckeirnw84ifkg/revoke',
            'method'  => 'PUT',
            'content' => [
                'merchant_id' => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],
];
