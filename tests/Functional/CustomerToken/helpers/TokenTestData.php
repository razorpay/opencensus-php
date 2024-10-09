<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateToken' => [
        'request' => [
            'url' => '/tokens',
            'method' => 'post',
            'content' => [
                'method' => 'card',
                'card' => [
                    'number' => '4143660000123456',
                    'cvv' => '123',
                    'expiry_month' => '12',
                    'expiry_year' => '28',
                    'name' => 'Gaurav Kumar',
                ],
                'notes' => [
                    'test' => 'test',
                ],
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testFetchTokenCardTestData' => [
        'request' => [
            'url' => '/internal/tokens/card',
            'method' => 'post',
            'content' => [
                'merchant_id' => '10000000000000',
                'token_id' => 'token_1Aa00000000001',
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'name' => 'Gaurav Kumar',
                'expiry_month' => 12,
                'expiry_year' => 2028,
                'iin' => '414366',
                'length' => '16',
                'network' => 'Visa',
                'emi' => false,
                'vault' => 'rzpvault',
                'trivia' => NULL,
                'country' => NULL,
                'global_card_id' => NULL,
                'token_expiry_month' => NULL,
                'token_expiry_year' => NULL,
                'provider_reference_id' => NULL,
                'network_code' => 'VISA',
                'message_type' => NULL,
            ],
        ],
    ],

    'testFetchTokenCardWithInvalidToken' => [
        'request' => [
            'url' => '/internal/tokens/card',
            'method' => 'post',
            'content' => [
                'merchant_id' => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code'   => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ]
];
