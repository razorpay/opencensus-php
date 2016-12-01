<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testCreateCreditsLog' => [
        'request' => [
            'url' => '/merchants/10000000000000/credits_log/',
            'method' => 'post',
            'content' => [
                'value' => 25,
                'campaign' => 'silent-ads',
            ],
        ],
        'response' => [
            'content' => [
                'value' => 25,
                'campaign' => 'silent-ads',
            ],
        ],
    ],

    'testGetCreditsLog' => [
        'request' => [
            'url' => '/merchants/10000000000000/credits/',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'value' => 150,
                'campaign' => 'silent-ads',
            ],
        ],
    ],

    'testCreditsLogAlreadyExists' => [
        'request' => [
            'url' => '/merchants/10000000000000/credits_log/',
            'method' => 'post',
            'content' => [
                'value' => 25,
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

    'testPositiveUpdateCredits' => [
        'request' => [
            'url' => '/merchants/10000000000000/credits/',
            'method' => 'put',
            'content' => [
                'value' => 190,
            ],
        ],
        'response' => [
            'content' => [
                'value' => 190,
                'campaign' => 'silent-ads',
            ],
            'status_code' => 200,
        ],
    ],

    'testNegativeUpdateCredits' => [
        'request' => [
            'url' => '/merchants/10000000000000/credits/',
            'method' => 'put',
            'content' => [
                'value' => 100,
            ]
        ],
        'response' => [
            'content' => [
                'value' => 100,
                'campaign' => 'silent-ads',
            ],
            'status_code' => 200,
        ],
    ],

    'testNegativeAmountCredits' => [
        'request' => [
            'url' => '/merchants/10000000000000/credits_log',
            'method' => 'post',
            'content' => [
                'value' => -150,
                'campaign' => 'silent-ads',
                'type' => 'amount'
            ],
        ],
        'response' => [
            'content' => [
                'value' => -150,
                'campaign' => 'silent-ads',
                'type'  => 'amount',
            ],
        ],
    ],

    'testNegativeFeeCredits' => [
        'request' => [
            'url' => '/merchants/10000000000000/credits_log',
            'method' => 'post',
            'content' => [
                'value' => -150,
                'campaign' => 'silent-ads',
                'type' => 'fee'
            ],
        ],
        'response' => [
            'content' => [
                'value' => -150,
                'campaign' => 'silent-ads',
                'type'  => 'fee',
            ],
        ],
    ],

    'testFailNegativeUpdateCredits' => [
        'request' => [
            'method' => 'put',
            'content' => [
                'value' => 1,
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

    'testFailDeductCreditsCampaign' => [
        'request' => [
            'method' => 'put',
            'content' => [
                'value' => -150,
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

    'testAmountCreditsGrantedInCampaign' => [
        'request' => [
            'url' => '/credits/?campaign=silent-ads',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => "collection",
                'count' => 2,
                'items' => [
                    [
                        'campaign' => "silent-ads",
                        'value' => 90,
                        'type'  => 'amount',
                    ],
                    [
                        'campaign' => "silent-ads",
                        'value' => 90,
                        'type' => 'amount',
                    ],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testFeeCreditsGrantedInCampaign' => [
        'request' => [
            'url' => '/credits/?campaign=silent-ads',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => "collection",
                'count' => 2,
                'items' => [
                    [
                        'campaign' => "silent-ads",
                        'value' => 90,
                        'type'  => 'fee',
                    ],
                    [
                        'campaign' => "silent-ads",
                        'value' => 90,
                        'type' => 'fee',
                    ],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testFeeCreditsGrantedInCampaign' => [
        'request' => [
            'url' => '/credits/?campaign=silent-ads&type=fee',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => "collection",
                'count' => 1,
                'items' => [
                    [
                        'campaign' => "silent-ads",
                        'value' => 90,
                    ],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testCreditsGrantedToMerchant' => [
        'request' => [
            'url' => '/credits/',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => "collection",
                'count' => 1,
                'items' => [
                    [
                        'campaign' => "silent-ads",
                        'value' => 90,
                    ],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testDeleteCreditsLog' => [
        'request' => [
            'url' => '/merchants/10000000000000/credits/',
            'method' => 'delete',
        ],
        'response' => [
            'content' => [
                'success' => true
            ],
            'status_code' => 200,
        ],
    ],

    'testCreditsTypeCollision' => [
        'request' => [
            'url' => '/merchants/10000000000000/credits_log/',
            'method' => 'post',
            'content' => [
                'value' => 25,
                'campaign' => 'silent-ads',
                'type' => 'amount',
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
        ],
    ],
];
