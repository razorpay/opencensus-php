<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testCreateFreeCreditsLog' => [
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
                'credits' => '25',
                'notes' => [
                    'referred_party' => 'asd',
                ],
                'campaign' => 'silent-ads',
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
            'url' => '/merchants/10000000000000/free_credits/123/',
            'method' => 'put',
            'content' => [
                'credits' => 120,
            ],
        ],
        'response' => [
            'content' => [
                'id' => '123',
                'merchant_id' => '10000000000000',
                'credits' => 270,
                'campaign' => 'silent-ads',
            ],
            'status_code' => 200,
        ],
    ],

    'testDeductFreeCredits' => [
        'request' => [
            'url' => '/merchants/10000000000000/free_credits/123/',
            'method' => 'put',
            'content' => [
                'credits' => -50,
            ]
        ],
        'response' => [
            'content' => [
                'id' => '123',
                'merchant_id' => '10000000000000',
                'credits' => 100,
                'campaign' => 'silent-ads',
            ],
            'status_code' => 200,
        ],
    ],

    'testFailDeductFreeCredits' => [
        'request' => [
            'url' => '/merchants/10000000000000/free_credits/123/',
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
            'url' => '/merchants/10000000000000/free_credits/123/',
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
            'url' => '/free_credits?campaign=silent-ads',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
				'entity' => "collection",
				'count' => 2,
				'items' => [
                    [
						'id' => "125",
						'campaign' => "silent-ads",
						'merchant_id' => "10000000000000",
						'credits' => 90,
						'notes' => [],
					],
                    [
                        'id' => "124",
                        'campaign' => "silent-ads",
                        'merchant_id' => "10000000000000",
                        'credits' => 90,
                        'notes' => [],
				    ],
			    ],
            ],
            'status_code' => 200,
        ],
    ],

    'testFreeCreditsGrantedToMerchant' => [
        'request' => [
            'url' => '/free_credits/',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
				'entity' => "collection",
				'count' => 1,
				'items' => [
                    [
						'id' => "125",
						'campaign' => "silent-ads",
						'merchant_id' => "10000000000000",
						'credits' => 90,
						'notes' => [],
					],
			    ],
            ],
            'status_code' => 200,
        ],
    ],

    // Test for proxy auth access to GET for free credit logs.
];
