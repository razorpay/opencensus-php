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

    'testGetFreeCreditsLog' => [
        'request' => [
            'url' => '/merchants/10000000000000/free_credits/123',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'id' => '123',
                'credits' => 90,
                'notes' => [],
                'merchant_id'=> '10000000000000',
                'campaign' => 'silent-ads',
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

    'testFreeCreditsGrantedInCampaign' => [
        'request' => [
            'url' => '/merchants/free_credits/campaign/silent-ads/',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                    'credits' => 270,
            ],
            'status_code' => 200,
        ],
    ],

    'testFreeCreditsGrantedToMerchant' => [
        'request' => [
            'url' => '/merchants/10000000000000/free_credits/all',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                [
                    'id' => '123',
                    'credits' => 90,
                    'notes' => [],
                    'merchant_id'=> '10000000000000',
                    'campaign' => 'silent-ads',
                ],
                [
                    'id' => '125',
                    'credits' => 90,
                    'notes' => [],
                    'merchant_id'=> '10000000000000',
                    'campaign' => 'silent-ads',
                ],
            ],
            'status_code' => 200,
        ],
    ],
];
