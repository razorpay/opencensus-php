<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testAddFreeCreditsLogRoute' => [
        'request' => [
            'url' => '/merchant/free_credits/2',
            'method' => 'post',
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
            'url' => '/merchant/free_credits/1',
            'method' => 'post',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
            'exception' => [
                'class' => 'RZP\Exception\BadRequestException',
                'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FREE_CREDITS_LOG_FOR_CAMPAIGN_EXISTS,
            ],
        ],
    ],

    'testAddMoreFreeCredits' => [
        'request' => [
            'url' => '/merchant/free_credits/1/add_credits',
            'method' => 'put',
        ],
        'response' => [
            'content' => [
                'success' => false,
                'error' => null,
            ],
        ],
    ],

    'testDeductFreeCredits' => [
        'request' => [
            'url' => '/merchant/free_credits/1/subtract_credits',
            'method' => 'put',
        ],
        'response' => [
            'content' => [
                'success' => false,
                'error' => null,
            ],
        ],
    ],

    'testFailDeductFreeCredits' => [
        'request' => [
            'url' => '/merchant/free_credits/1/subtract_credits',
            'method' => 'put',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
            'exception' => [
                'class' => 'RZP\Exception\BadRequestException',
                'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_TOTAL_CREDITS_LESSER_THAN_CREDITS_TO_SUBTRACT,
            ],
        ]
    ],

    'testFailDeductFreeCreditsCampaign' => [
        'request' => [
            'url' => '/merchant/free_credits/1/subtract_credits',
            'method' => 'put',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
            'exception' => [
                'class' => 'RZP\Exception\BadRequestException',
                'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_CAMPAIGN_CREDITS_LESSER_THAN_CREDITS_TO_SUBTRACT,
            ],
        ]
    ],
];
