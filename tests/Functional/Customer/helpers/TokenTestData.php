<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateToken' => [
        'request' => [
            'url' => '/tokens',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'action'  => 'create',
            ],
        ],
    ],

    'testFetchToken' => [
        'request' => [
            'url' => '/tokens/fetch',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'action'  => 'fetch',
            ],
        ],
    ],

    'testFetchCryptogram' => [
        'request' => [
            'url' => '/tokens/cryptogram',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'action'  => 'fetchCryptoGram',
            ],
        ],
    ],

    'testTokenDelete' => [
        'request' => [
            'url' => '/tokens/delete',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'action'  => 'delete',
            ],
        ],
    ],
];
