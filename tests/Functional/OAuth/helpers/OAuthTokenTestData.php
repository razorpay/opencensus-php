<?php

namespace RZP\Tests\Functional\OAuth;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

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
