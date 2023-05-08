<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Merchant\Credits\Entity;

return [

    'testCreateCreditsLog' => [
        'request' => [
            'url' => '/merchants/10000000000000/credits_log',
            'method' => 'post',
            'content' => [
                'value'    => 25,
                'campaign' => 'silent-ads',
            ],
        ],
        'response' => [
            'content' => [
                'type'        => 'amount',
                'value'       => 25,
                'campaign'    => 'silent-ads',
                'used'        => 0,
            ],
        ],
    ],

    'testCreateCreditsBulk' => [
        'request'  => [
            'url'     => '/merchants/credits/bulk',
            'method'  => 'post',
            'content' => [
                '10000000000000' => [
                    'value'    => 250000,
                    'type'     => 'amount',
                    'campaign' => 'random_campaign'
                ],
                '1000000000000x' => [
                    'value'    => 250000,
                    'type'     => 'amount',
                    'campaign' => 'random_campaign'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'total_count'  => 2,
                'failed_count' => 1,
                'failed_ids'   => ['1000000000000x']
            ],
        ],
    ],

    'testCreateCreditsBulkInternal' => [
        'request'  => [
            'url'     => '/internal/merchants/credits/bulk',
            'method'  => 'post',
            'content' => [
                '10000000000000' => [
                    'value'    => 250000,
                    'type'     => 'amount',
                    'campaign' => 'random_campaign'
                ],
                '1000000000000x' => [
                    'value'    => 250000,
                    'type'     => 'amount',
                    'campaign' => 'random_campaign'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'total_count'  => 2,
                'failed_count' => 1,
                'failed_ids'   => ['1000000000000x']
            ],
        ],
    ],

    'testGetCreditsLog' => [
        'request' => [
            'url' => '/credits/',
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
                'value' => -2339160,
                'campaign' => 'silent-ads',
                'type' => 'fee'
            ],
        ],
        'response' => [
            'content' => [
                'value' => -2339160,
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

    'testAddRefundCredits' => [
        'request' => [
            'url' => '/merchants/10000000000000/credits_log',
            'method' => 'post',
            'content' => [
                'type'     => 'refund',
                'value'    => 25,
                'campaign' => 'silent-ads',
            ],
        ],
        'response' => [
            'content' => [
                'type'        => 'refund',
                'value'       => 25,
                'campaign'    => 'silent-ads',
                'used'        => 0,
            ],
        ],
    ],
    'testAddRefundCreditsWithoutUpperLimit' => [
        'request' => [
            'url' => '/merchants/10000000000000/credits_log',
            'method' => 'post',
            'content' => [
                'type'     => 'refund',
                'value'    => 1000000000,
                'campaign' => 'silent-ads',
            ],
        ],
        'response' => [
            'content' => [
                'type'        => 'refund',
                'value'       => 1000000000,
                'campaign'    => 'silent-ads',
                'used'        => 0,
            ],
        ],
    ],

    'testBulkCreditRoute' => [
        'request' => [
            'url'       => '/merchants/credits/bulk/batch',
            'method'    => 'post',
            'server' => [],
            'content'   => [
                [
                    Entity::IDEMPOTENCY_KEY => 'bkwydgsZPxiesSRCRKd',
                    Entity::MERCHANT_ID     => '10000000000000',
                    Entity::REMARKS         => 'some test credits',
                    Entity::CAMPAIGN        => 'test credits',
                    Entity::VALUE           => 100,
                    Entity::PRODUCT         => 'banking',
                    Entity::TYPE            => 'reward_fee',
                ],
                [
                    Entity::IDEMPOTENCY_KEY => 'bkwydgsZPxiesSRCRe',
                    Entity::MERCHANT_ID     => '10000000000000',
                    Entity::REMARKS         => 'some test credits',
                    Entity::CAMPAIGN        => 'test credits',
                    Entity::VALUE           => -100,
                    Entity::PRODUCT         => 'banking',
                    Entity::TYPE            => 'reward_fee',
                ],
                [
                    Entity::IDEMPOTENCY_KEY => 'bkwydgsZPxiesSRCRKf',
                    Entity::MERCHANT_ID     => '10000000000000',
                    Entity::REMARKS         => 'some test credits',
                    Entity::CAMPAIGN        => 'test credits',
                    Entity::VALUE           => -100,
                    Entity::PRODUCT         => 'banking',
                    Entity::TYPE            => 'reward_fee',
                ],
                [
                    Entity::IDEMPOTENCY_KEY => 'bkwydgsZPxiesSRCRKg',
                    Entity::MERCHANT_ID     => '10000000000000',
                    Entity::REMARKS         => 'some test credits',
                    Entity::CAMPAIGN        => 'test credits',
                    Entity::VALUE           => 100,
                    Entity::PRODUCT         => 'banking',
                    Entity::TYPE            => 'reward_fee',
                ],
                [
                    Entity::IDEMPOTENCY_KEY => 'bkwydgsZPxiesSRCRKd',
                    Entity::MERCHANT_ID     => '10000000000000',
                    Entity::REMARKS         => 'some test credits',
                    Entity::CAMPAIGN        => 'test credits',
                    Entity::VALUE           => 100,
                    Entity::PRODUCT         => 'banking',
                    Entity::TYPE            => 'reward_fee',
                ],
                [
                    Entity::IDEMPOTENCY_KEY => 'bkwydgsZPxiesSRCRKf',
                    Entity::REMARKS         => 'some test credits',
                    Entity::CAMPAIGN        => 'test credits',
                    Entity::VALUE           => 100,
                    Entity::PRODUCT         => 'banking',
                    Entity::TYPE            => 'reward_fee',
                ],
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 6,
                'items' => [
                    [
                        'merchant_id'           => "10000000000000",
                        'campaign'              =>  "test credits",
                        'value'                 =>  100,
                        'remarks'               => "some test credits",
                        'idempotency_key'       => "bkwydgsZPxiesSRCRKd",
                        'batch_id'              =>  "C0zv9I46W4wiOq",
                        'type'                  =>  "reward_fee",
                        'product'               =>  "banking",
                        'creator_name'          => "test admin",
                    ],
                    [
                        'merchant_id'       => "10000000000000",
                        'campaign'          =>  "test credits",
                        'value'             =>  -100,
                        'remarks'           => "some test credits",
                        'idempotency_key'   => "bkwydgsZPxiesSRCRe",
                        'batch_id'          =>  "C0zv9I46W4wiOq",
                        'type'              =>  "reward_fee",
                        'product'           =>  "banking",
                        'creator_name'      => "test admin",
                    ],

                    [
                        'error'=>
                            [
                                'description' =>  "Cannot update or add -1 reward_fee-credits. Merchant has only 0 reward_fee-credits.",
                                'code'        => "BAD_REQUEST_ERROR",
                            ],
                        'http_status_code'    =>  400,
                        'idempotency_key'     => "bkwydgsZPxiesSRCRKf",
                        'batch_id'            =>  "C0zv9I46W4wiOq",
                    ],

                    [
                        'merchant_id'       => "10000000000000",
                        'campaign'          =>  "test credits",
                        'value'             =>  100,
                        'remarks'           => "some test credits",
                        'idempotency_key'   => "bkwydgsZPxiesSRCRKg",
                        'batch_id'          =>  "C0zv9I46W4wiOq",
                        'type'              =>  "reward_fee",
                        'product'           =>  "banking",
                        'creator_name'      => "test admin",
                    ],

                    [
                        'merchant_id'       => "10000000000000",
                        'campaign'          =>  "test credits",
                        'value'             =>  100,
                        'remarks'           => "some test credits",
                        'idempotency_key'   => "bkwydgsZPxiesSRCRKd",
                        'batch_id'          =>  "C0zv9I46W4wiOq",
                        'type'              =>  "reward_fee",
                        'product'           =>  "banking",
                        'creator_name'      => "test admin",
                    ],

                    [
                        'error'=>
                            [
                                'description' =>  "The merchant id field is required.",
                                'code'        => "BAD_REQUEST_ERROR",
                            ],
                        'http_status_code' =>  400,
                        'idempotency_key'  => "bkwydgsZPxiesSRCRKf",
                        'batch_id'         =>  "C0zv9I46W4wiOq",
                    ],
                ]
            ]
        ]
    ],

    'testBulkCreditLedgerReverseShadowRoute' => [
        'request' => [
            'url'       => '/merchants/credits/bulk/batch',
            'method'    => 'post',
            'server' => [],
            'content'   => [
                [
                    Entity::IDEMPOTENCY_KEY => 'bkwydgsZPxiesSRCRAa',
                    Entity::MERCHANT_ID     => '10000000000000',
                    Entity::REMARKS         => 'some test credits',
                    Entity::CAMPAIGN        => 'test credits',
                    Entity::VALUE           => 100,
                    Entity::PRODUCT         => 'banking',
                    Entity::TYPE            => 'reward_fee',
                ],
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items' => [
                    [
                        'merchant_id'           => "10000000000000",
                        'campaign'              =>  "test credits",
                        'value'                 =>  100,
                        'remarks'               => "some test credits",
                        'idempotency_key'       => "bkwydgsZPxiesSRCRAa",
                        'batch_id'              =>  "C0zv9I46W4wiAa",
                        'type'                  =>  "reward_fee",
                        'product'               =>  "banking",
                        'creator_name'          => "test admin",
                    ],
                ]
            ]
        ]
    ],

    'testBulkCreditRouteInTestMode' => [
        'request' => [
            'url'       => '/merchants/credits/bulk/batch',
            'method'    => 'post',
            'server' => [],
            'content'   => [
                [
                    Entity::IDEMPOTENCY_KEY => 'bkwydgsZPxiesSRCRKd',
                    Entity::MERCHANT_ID     => '10000000000000',
                    Entity::REMARKS         => 'some test credits',
                    Entity::CAMPAIGN        => 'test credits',
                    Entity::VALUE           => 100,
                    Entity::PRODUCT         => 'banking',
                    Entity::TYPE            => 'reward_fee',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items' => [
                    [
                        'error'=>
                            [
                                'description' =>  "Bad request, X credits supported in only live mode",
                                'code'        => "BAD_REQUEST_ERROR",
                            ],
                        'http_status_code' =>  400,
                        'idempotency_key'  => "bkwydgsZPxiesSRCRKd",
                    ],
                ]
            ],
        ]
    ],

    'testBulkCreditRouteEntities' => [
        'request' => [
            'url'       => '/merchants/credits/bulk/batch',
            'method'    => 'post',
            'server' => [],
            'content'   => [
                [
                    Entity::IDEMPOTENCY_KEY => 'bkwydgsZPxiesSRCRKd',
                    Entity::MERCHANT_ID     => '10000000000000',
                    Entity::REMARKS         => 'some test credits',
                    Entity::CAMPAIGN        => 'test credits',
                    Entity::VALUE           => 100,
                    Entity::PRODUCT         => 'banking',
                    Entity::TYPE            => 'reward_fee',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items' => [
                    [
                        'merchant_id'       => "10000000000000",
                        'campaign'          =>  "test credits",
                        'value'             =>  100,
                        'remarks'           => "some test credits",
                        'idempotency_key'   => "bkwydgsZPxiesSRCRKd",
                        'batch_id'          =>  "C0zv9I46W4wiOq",
                        'type'              =>  "reward_fee",
                        'product'           =>  "banking",
                        'creator_name'      => "test admin",
                    ],
                ]
            ]
        ]
    ],

    'testCreditRowsWithNegativeBalance' => [
        'request' => [
            'url' => '/merchants/credits/balance/banking',
            'method' => 'GET',
            'content' => [],
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
        ],
        'response' => [
            'content' => [
                [
                    'product' => 'banking',
                    'merchant_id' => '10000000000000',
                    'balance' => 5000,
                    'type' =>  'reward_fee',
                    'expired_at' => NULL
                ],
            ]
        ]
    ]

];
