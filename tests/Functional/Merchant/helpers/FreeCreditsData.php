<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testAddFreeCreditsLog' => [
        'request' => [
            'url' => '/merchants/10000000000000/free_credits/',
            'method' => 'post',
            'content' => [
                'credits' => 25,
                'notes' => [
                    'referred_party' => 'asd',
                ],
                'campaign' => 'silent-ads',
            ],
        ],
        'response' => [
            'content' => [
                'success' =>  true,
                'error'=> null,
            ],
        ],
    ],

    'testFreeCreditsLogAlreadyExists' => [
        'request' => [
            'url' => '/merchants/10000000000000/free_credits/',
            'method' => 'post',
            'content' => [
                'credits' => 25,
                'notes' => [
                    'referred_party' => 'asd',
                ],
                'campaign' => 'silent-ads',
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
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            'error_description' => 'The record already exists for given campaign and merchant.',
        ],
    ],

    'testGrantFreeCredits' => [
        'request' => [
            'url' => '/merchants/1000000000000/free_credits/123/',
            'method' => 'put',
            'content' => [
                'credits' => 120,
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
                'error' => null,
            ],
            'status_code' => true,
        ],
    ],

    'testDeductFreeCredits' => [
        'request' => [
            'url' => '/merchants/1000000000000/free_credits/123/',
            'method' => 'put',
            'content' => [
                'credits' => -50,
            ]
        ],
        'response' => [
            'content' => [
                'success' => true,
                'error' => null,
            ],
            'status_code' => true,
        ],
    ],

    'testFailDeductFreeCredits' => [
        'request' => [
            'url' => '/merchants/1000000000000/free_credits/123/',
            'method' => 'put',
            'content' => [
                'credits' => -170,
            ]
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
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFailDeductFreeCreditsCampaign' => [
        'request' => [
            'url' => '/merchants/1000000000000/free_credits/123/',
            'method' => 'put',
            'content' => [
                'credits' => -150,
            ]
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
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
];
