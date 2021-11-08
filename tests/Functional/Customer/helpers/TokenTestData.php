<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateToken' => [
        'request' => [
            'url' => '/tokens',
            'method' => 'post',
            'content' => [
                'method' => 'card',
                'card' => [
                    'number' => '6073849700004947',
                    'cvv' => '123',
                    'expiry_month' => '12',
                    'expiry_year' => '23',
                    'name' => 'Gaurav Kumar',
                ],
            ],
        ],
        'response' => [
            'content' => [
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
            ],
        ],
    ],

    'testFetchCryptogram' => [
        'request' => [
            'url' => '/tokens/get_payment_data',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
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
            ],
        ],
    ],

    'testCreateTokenAndTokenizeCard' => [
        'request' => [
            'url' => '/tokens',
            'method' => 'post',
            'content' => [
                'method' => 'card',
                'card' => [
                    'number' => '4143667057540458',
                    'cvv' => '123',
                    'expiry_month' => '12',
                    'expiry_year' => '23',
                    'name' => 'Gaurav Kumar',
                ],
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCreateTokenAndTokenizeCardValidationFailure' => [
        'request' => [
            'url' => '/tokens',
            'method' => 'post',
            'content' => [
                'method' => 'card',
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code'   => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testCreateTokenAndTokenizeCardVaultFailure' => [
        'request' => [
            'url' => '/tokens',
            'method' => 'post',
            'content' => [
                'method' => 'card',
                'card' => [
                    'number' => '4143667057540458',
                    'cvv' => '123',
                    'expiry_month' => '12',
                    'expiry_year' => '23',
                    'name' => 'Gaurav Kumar',
                ],
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'                 => \RZP\Exception\RuntimeException::class,
            'internal_error_code'   => 'SERVER_ERROR_RUNTIME_ERROR',
        ],
    ],

    'testFetchCryptogramLive' => [
        'request' => [
            'url' => '/tokens/get_payment_data',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testFetchCryptogramLiveInvalidTokenId' => [
        'request' => [
            'url' => '/tokens/get_payment_data',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code'   => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testFetchCryptogramLiveVaultFailure' => [
        'request' => [
            'url' => '/tokens/get_payment_data',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'                 => \RZP\Exception\RuntimeException::class,
            'internal_error_code'   => 'SERVER_ERROR_RUNTIME_ERROR',
        ],
    ],

    'testFetchTokenLive' => [
        'request' => [
            'url' => '/tokens/fetch',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testFetchTokenLiveInvalidToken' => [
        'request' => [
            'url' => '/tokens/fetch',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code'   => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testFetchTokenLiveVaultFailure' => [
        'request' => [
            'url' => '/tokens/fetch',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'                 => \RZP\Exception\RuntimeException::class,
            'internal_error_code'   => 'SERVER_ERROR_RUNTIME_ERROR',
        ],
    ],

    'testTokenDeleteLive' => [
        'request' => [
            'url' => '/tokens/delete',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testTokenDeleteLiveVaultFailure' => [
        'request' => [
            'url' => '/tokens/delete',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'                 => \RZP\Exception\RuntimeException::class,
            'internal_error_code'   => 'SERVER_ERROR_RUNTIME_ERROR',
        ],
    ],

    'testTokenDeleteLiveInvalidToken' => [
        'request' => [
            'url' => '/tokens/delete',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code'   => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],
];
