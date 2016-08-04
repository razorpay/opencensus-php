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
                'notes' => [
                    'referred_party' => 'asd',
                ],
                'campaign' => 'silent-ads',
            ],
        ],
        'response' => [
            'content' => [
                'value' => '25',
                'notes' => [
                    'referred_party' => 'asd',
                ],
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

    'testGrantCredits' => [
        'request' => [
            'url' => '/merchants/10000000000000/credits/123/',
            'method' => 'put',
            'content' => [
                'value' => 120,
            ],
        ],
        'response' => [
            'content' => [
                'value' => 270,
                'campaign' => 'silent-ads',
            ],
            'status_code' => 200,
        ],
    ],

    'testDeductCredits' => [
        'request' => [
            'url' => '/merchants/10000000000000/credits/123/',
            'method' => 'put',
            'content' => [
                'value' => -50,
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

    'testFailDeductCredits' => [
        'request' => [
            'url' => '/merchants/10000000000000/credits/123/',
            'method' => 'put',
            'content' => [
                'value' => -170,
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
            'url' => '/merchants/10000000000000/credits/123/',
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

    'testCreditsGrantedInCampaign' => [
        'request' => [
            'url' => '/credits?campaign=silent-ads',
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
						'notes' => [],
					],
                    [
                        'campaign' => "silent-ads",
                        'value' => 90,
                        'notes' => [],
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
						'notes' => [],
					],
			    ],
            ],
            'status_code' => 200,
        ],
    ],

    // Test for proxy auth access to GET for free credit logs.
];
