<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [

    'testFundAccountCreationOnEsOndemandAssigning' => [
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['es_on_demand'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ]
        ],
        'response' => [
            'content' => [
                [
                    'name'        => 'es_on_demand',
                    'entity_id'   => '10000000000000',
                    'entity_type' => 'merchant',
//                  'id'          => 'F2hSuJpBtY0J6B',
                ],
            ]
        ]
    ],

    'testFundAccountUpdationOnBankAccountEdit' => [
        'request'  => [
            'url'     => '/bank_accounts/{id}',
            'method'  => 'put',
            'content' => [
                'account_number' => '123456785',
            ]
        ],
        'response' => [
            'content' => [
//                'id'          => 'F2hZwdzOGSZIqJ',
                'merchant_id' =>  '10000000000000',
             ]
        ]
    ],

    'testBankingHourOndemandCreation' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'content' => [
                'amount'    => 50030000,
                'description' => 'Demo Narration - optional',
                'notes'     => [
                                'key1' => 'note3',
                                'key2' => 'note5'
                            ],
            ],
        ],
        'response' => [
            'content' => [
//                'id'                   => 'sod_F2hdIcWTkePDcC',
                'entity'                => 'settlement.ondemand',
                'amount_requested'      => 50030000,
                'fees'                  => 1180708,
                'tax'                   => 180108,
                'amount_pending'        => 48849292,
                'amount_settled'        => 0,
                'amount_reversed'       => 0,
                'currency'              => 'INR',
                'status'                => 'initiated',
                'description'             => 'Demo Narration - optional',
                'notes'                 => [
                    'key1' => 'note3',
                    'key2' => 'note5'
                ],
//                'created_at'         => 1582000200,
            ]
        ]
    ],

    'testFetchApi' => [
        'request'  => [
            'url'     => '/settlements/ondemand/?from=1582000200&expand[]=ondemand_payouts',
            'method'  => 'get'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 2,
                'items' => [
                    [
//                        'id'                    => 'sod_FGi3ZQErsqc9lc',
                        'entity'                => 'settlement.ondemand',
                        'amount_requested'      => 50220000,
                        'amount_settled'        => 0,
                        'fees'                  => 1185192,
                        'tax'                   => 180792,
                        'amount_reversed'       => 0,
                        'amount_pending'        => 49034808,
                        'settle_full_balance'   => false,
                        'currency'              => 'INR',
                        'status'                => 'initiated',
                        'description'           => 'Demo Narration - optional',
                        'notes'                 => [],
//                        'created_at'           => 1595239294,
                        'ondemand_payouts' => [
                            'entity' => 'collection',
                            'count'  => 2,
                            'items'  => [
                                [
//                                    'id'                      => 'sodp_FGi3ZSdqiiG3AP',
                                    'entity'                   => 'settlement.ondemand_payout',
//                                    'initiated_at'            => 1595239294,
                                    'processed_at'            => null,
                                    'reversed_at'             => null,
                                    'amount'                  => 50000000,
                                    'amount_settled'          => 48820000,
                                    'fees'                    => 1180000,
                                    'tax'                     => 180000,
                                    'utr'                     => null,
                                    'status'                  => 'initiated',
//                                    'created_at'              => 1595239293,
                                ],
                                [
//                                    'id'                       => 'sodp_FGi3ZYZjPQhi49',
                                    'entity'                   => 'settlement.ondemand_payout',
//                                    'initiated_at'             => 1595239294,
                                    'processed_at'             => null,
                                    'reversed_at'              => null,
                                    'amount'                   => 220000,
                                    'amount_settled'           => 214808,
                                    'fees'                     => 5192,
                                    'tax'                      => 792,
                                    'utr'                      => null,
                                    'status'                   =>  'initiated',
//                                    'created_at'               => 1595239293,
                                ]
                            ]
                        ]
                    ],
                    [
//                        'id'                    => 'sod_FGi3a0Du84Lr7B',
                        'entity'                => 'settlement.ondemand',
                        'amount_requested'      => 50030000,
                        'amount_settled'        => 0,
                        'fees'                  => 1180708,
                        'tax'                   => 180108,
                        'amount_reversed'       => 0,
                        'amount_pending'        => 48849292,
                        'settle_full_balance'    => false,
                        'currency'              => 'INR',
                        'status'                => 'initiated',
                        'description'             => 'Demo Narration - optional',
                        'notes'                 => [],
//                        'created_at'            => 1595239294,
//                        'updated_at'            => 1595239294,
                        'ondemand_payouts' => [
                            'entity' => 'collection',
                            'count'  => 2,
                            'items'  => [
                                [
//                                    'id'                       => 'sodp_FGi3a0IjIScQNQ',
                                    'entity'                   => 'settlement.ondemand_payout',
//                                    'initiated_at'             => 1595239294,
                                    'processed_at'             => null,
                                    'reversed_at'              => null,
                                    'amount'                   => 50000000,
                                    'amount_settled'           => 48820000,
                                    'fees'                     => 1180000,
                                    'tax'                      => 180000,
                                    'utr'                      => null,
                                    'status'                   => 'initiated',
//                                    'created_at'               => 1595239294,
                                ],
                                [
                                    'entity'                   => 'settlement.ondemand_payout',
//                                    'initiated_at'             => 1595239294,
                                    'processed_at'             => null,
                                    'reversed_at'              => null,
                                    'amount'                   => 30000,
                                    'amount_settled'           => 29292,
                                    'fees'                     => 708,
                                    'tax'                      => 108,
                                    'utr'                      => null,
                                    'status'                   => 'initiated',
//                                    'created_at'               => 1595239294,
                                ]
                            ]
                        ]
                    ],
                ]
            ]
        ]
    ],

    'testFetchApiWithStatus' => [
        'request'  => [
            'url'     => '/settlements/ondemand/?status=processed',
            'method'  => 'get'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'entity'                => 'settlement.ondemand',
                        'amount_requested'      => 50030000,
                        'amount_settled'        => 48849292,
                        'fees'                  => 1180708,
                        'tax'                   => 180108,
                        'amount_reversed'       => 0,
                        'amount_pending'        => 0,
                        'settle_full_balance'   => false,
                        'currency'              => 'INR',
                        'status'                => 'processed',
                        'description'           => 'Demo Narration - optional',
                        'notes'                 => [],
                    ]
                ]
            ]
        ]
    ],

    'testSuccessScenarioForSendingDataToCollectionsForLedgerForCreatingOndemandSettlement' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'content' => [
                'amount'    => 2000,

            ],
        ],
        'response' => [
            'content' => [
                'entity'               => 'settlement.ondemand',
                'amount_requested'     => 2000,
                'fees'                 => 48,
                'tax'                  => 8,
                'amount_pending'       => 1952,
                'settle_full_balance'  => false,
                'currency'             => 'INR',
                'status'               => 'initiated',
                'description'          => null,
                'notes'                => [],
            ]
        ]
    ],

    'testNoMinLimitFormEsAutomaticMerchants' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'content' => [
                'amount'    => 2000,

            ],
        ],
        'response' => [
            'content' => [
//                    'id'                   => 'sod_FFEK3Ne1b6uCXf',
                    'entity'               => 'settlement.ondemand',
                    'amount_requested'     => 2000,
                    'fees'                 => 48,
                    'tax'                  => 8,
                    'amount_pending'       => 1952,
                    'settle_full_balance'  => false,
                    'currency'             => 'INR',
                    'status'               => 'initiated',
                    'description'          => null,
                    'notes'                => [],
//                    'created_at'           => 1582000200,
            ]
        ]
    ],

    'testMinLimitForNonEsAutomaticMerchants' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'content' => [
                'amount'    => 100000,
                'description' => 'Demo Narration - optional',
                'notes'     => [
                                'key1' => 'note3',
                                'key2' => 'note5'
                            ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Minimum amount that can be settled is ₹ 2000.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_AMOUNT_LESS_THAN_MIN_LIMIT_FOR_NON_ES_AUTOMATIC_MERCHANTS,
        ],
    ],

    'testBankingHourOndemandCreationWithMockWebhook' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'content' => [
                'amount' => 50030000,
                'settle_full_balance' => 0,
                'description' => 'Demo Narration - optional',
            ],
        ],
        'response' => [
            'content' => [
//                'id'                    => 'sod_F2hdIcWTkePDcC',
                'entity'                => 'settlement.ondemand',
                'amount_requested'      => 50030000,
                'fees'                  => 1180708,
                'tax'                   => 180108,
                'amount_pending'        => 48849292,
                'amount_settled'        => 0,
                'amount_reversed'       => 0,
                'settle_full_balance'   => false,
                'currency'              => 'INR',
                'status'                => 'initiated',
                'description'           => 'Demo Narration - optional',
//                'created_at'          => 1582000200,
            ]
        ]
    ],

    'testBankingHourOndemandCreationWithReversal' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'content' => [
                'amount'      => 220000,
                'settle_full_balance' => 0,
                'description'   => 'Demo Narration - optional'
            ],
        ],
        'response' => [
            'content' => [
//                'id'                    => 'sod_F2hdIcWTkePDcC',
                'amount_requested'      => 220000,
                'fees'                  => 5192,
                'tax'                   => 792,
                'amount_pending'        => 214808,
                'amount_settled'        => 0,
                'amount_reversed'       => 0,
                'settle_full_balance'   => false,
                'currency'              => 'INR',
                'status'                => 'initiated',
                'description'           => 'Demo Narration - optional',
//                'created_at'           => 1582000200,
            ]
        ]
    ],

    'testBankingHourOndemandCreationWithProcessedPayout' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'content' => [
                'amount'      => 440000,
                'settle_full_balance' => 0,
                'description'   => 'Demo Narration - optional'
            ],
        ],
        'response' => [
            'content' => [
//                'id'                    => 'sod_F2hdIcWTkePDcC',
                'amount_requested'      => 440000,
                'fees'                  => 10384,
                'tax'                   => 1584,
                'amount_pending'        => 429616,
                'amount_settled'        => 0,
                'amount_reversed'       => 0,
                'settle_full_balance'   => false,
                'currency'              => 'INR',
                'status'                => 'initiated',
                'description'           => 'Demo Narration - optional',
//                'created_at'           => 1582000200,
            ]
        ]
    ],

    'testBankingHourOndemandCreationWithReversedPayoutResponse' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'content' => [
                'amount'      => 880000,
                'settle_full_balance' => 0,
                'description'   => 'Demo Narration - optional'
            ],
        ],
        'response' => [
            'content' => [
//                'id'                    => 'sod_F2hdIcWTkePDcC',
                'amount_requested'      => 880000,
                'fees'                  => 20768,
                'tax'                   => 3168,
                'amount_pending'        => 859232,
                'amount_settled'        => 0,
                'amount_reversed'       => 0,
                'settle_full_balance'   => false,
                'currency'              => 'INR',
                'status'                => 'initiated',
                'description'           => 'Demo Narration - optional',
//                'created_at'           => 1582000200,
            ]
        ]
    ],

    'testOndemandCreationBankingHour' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'content' => [
                'amount'      => 50030000,
                'settle_full_balance' => 0,
                'description'   => 'Demo Narration - optional',
            ],
        ],
        'response' => [
            'content' => [
//                'id'                    => 'sod_F2iLNDZn8cODKc',
                'entity'                => 'settlement.ondemand',
                'amount_requested'      => 50030000,
                'fees'                  => 1180708,
                'tax'                   => 180108,
                'amount_pending'        => 48849292,
                'amount_settled'        => 0,
                'amount_reversed'       => 0,
                'settle_full_balance'   => false,
                'currency'              => 'INR',
                'status'                => 'initiated',
                'description'           => 'Demo Narration - optional',
//                'created_at'            => 1582036200,
            ]
        ]
    ],

    'testNonBankingHourOndemandCreationWithMockWebhook' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'content' => [
                'amount' => 50030000,
                'settle_full_balance' => 0,
                'description' => 'Demo Narration - optional',
            ],
        ],
        'response' => [
            'content' => [
//                'id'                    => 'sod_F2iLNDZn8cODKc',
                'entity'                => 'settlement.ondemand',
                'amount_requested'      => 50030000,
                'fees'                  => 1180708,
                'tax'                   => 180108,
                'amount_pending'        => 48849292,
                'amount_settled'        => 0,
                'amount_reversed'       => 0,
                'settle_full_balance'   => false,
                'currency'              => 'INR',
                'status'                => 'initiated',
                'description'           => 'Demo Narration - optional',
//                'created_at'            => 1582036200,
            ]
        ]
    ],

    'testNonBankingHourOndemandCreationWithPartialReversal' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'content' => [
                'amount' => 50220000,
                'settle_full_balance' => 0,
                'description' => 'Demo Narration - optional',
            ],
        ],
        'response' => [
            'content' => [
//                'id'                    => 'sod_F2iLNDZn8cODKc',
                'entity'                => 'settlement.ondemand',
                'amount_requested'      => 50220000,
                'fees'                  => 1185192,
                'tax'                   => 180792,
                'amount_pending'        => 49034808,
                'amount_settled'        => 0,
                'amount_reversed'       => 0,
                'settle_full_balance'   => false,
                'currency'              => 'INR',
                'status'                => 'initiated',
                'description'             => 'Demo Narration - optional',
//                'created_at'            => 1582036200,
            ]
        ]
    ],

    'testCreateOndemandForMaxBalance' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'content' => [
                'settle_full_balance' => true,
                'description' => 'Demo Narration - optional',
            ],
        ],
        'response' => [
            'content' => [
//                'id'                 => 'sod_F2hdIcWTkePDcC',
                'entity'               => 'settlement.ondemand',
                'amount_requested'     => 20030000,
                'fees'                 => 472708,
                'tax'                  => 72108,
                'amount_pending'       => 19557292,
                'amount_settled'       => 0,
                'amount_reversed'      => 0,
                'settle_full_balance'  => true,
                'currency'             => 'INR',
                'status'               => 'initiated',
                'description'            => 'Demo Narration - optional',
//                'created_at'         => 1582000200,
            ]
        ]
    ],

    'testCreateOndemandForMaxBalanceWhenDisabledByCollectionsForLoc' => [
            'request'  => [
                'url'     => '/settlements/ondemand',
                'method'  => 'post',
                'content' => [
                    'settle_full_balance' => true,
                    'description' => 'Demo Narration - optional',
                ],
            ],
            'response' => [
                         'content' => [
                             'error' => [
                                 'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                                 'description' => 'BAD_REQUEST_ES_ON_DEMAND_DISABLED_BY_COLLECTIONS',
                             ],
                         ],
                         'status_code' => 400,
                     ],
            'exception' => [
                 'class' => RZP\Exception\BadRequestValidationFailureException::class,
                 'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
             ]

    ],

    'testCreateOndemandForMaxBalanceWhenDisabledByCollectionsForLoan' => [
            'request'  => [
                'url'     => '/settlements/ondemand',
                'method'  => 'post',
                'content' => [
                    'settle_full_balance' => true,
                    'description' => 'Demo Narration - optional',
                ],
            ],
            'response' => [
                         'content' => [
                             'error' => [
                                 'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                                 'description' => 'BAD_REQUEST_ES_ON_DEMAND_DISABLED_BY_COLLECTIONS',
                             ],
                         ],
                         'status_code' => 400,
                     ],
            'exception' => [
                 'class' => RZP\Exception\BadRequestValidationFailureException::class,
                 'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
             ]

    ],

    'testCreateOndemandForMaxBalanceWhenDisabledByCollectionsForCard' => [
            'request'  => [
                'url'     => '/settlements/ondemand',
                'method'  => 'post',
                'content' => [
                    'settle_full_balance' => true,
                    'description' => 'Demo Narration - optional',
                ],
            ],
            'response' => [
                         'content' => [
                             'error' => [
                                 'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                                 'description' => 'BAD_REQUEST_ES_ON_DEMAND_DISABLED_BY_COLLECTIONS',
                             ],
                         ],
                         'status_code' => 400,
                     ],
            'exception' => [
                 'class' => RZP\Exception\BadRequestValidationFailureException::class,
                 'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
             ]

    ],

    'testCreateOndemandOnLowBalance' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'content' => [
                'amount' => 200000,
                'settle_full_balance' => 0,
                'description' => 'Demo Narration - optional',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Amount requested for the ondemand settlement exceeds the settlement balance.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INSUFFICIENT_BALANCE,
        ],
    ],

    'testCreateOndemandGreaterThanMaxLimit' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'content' => [
                'amount' => 2000000500,
                'settle_full_balance' => 0,
                'description' => 'Demo Narration - optional',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Amount requested is more than the max limit for ondemand settlement',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ONDEMAND_SETTLEMENT_AMOUNT_MAX_LIMIT_EXCEEDED,
        ],
    ],

    'testCreateOndemandForFundsOnHoldMerchant' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'amount' => 20000,
                'settle_full_balance' => 1,
                'description' => 'Demo Narration - optional',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'This operation is not allowed. Please contact Razorpay support for details.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FUNDS_ON_HOLD,
        ],
    ],

    'testOndemandCreationForFixedRatePricing' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'content' => [
                'amount' => 50030000,
                'settle_full_balance' => 0,
                'description' => 'Demo Narration - optional',
            ],
        ],
        'response' => [
            'content' => [
//                'id'                    => 'sod_F2iLNDZn8cODKc',
                'entity'                => 'settlement.ondemand',
                'amount_requested'      => 50030000,
                'fees'                  => 1180,
                'tax'                   => 180,
                'amount_pending'        => 50028820,
                'amount_settled'        => 0,
                'amount_reversed'       => 0,
                'settle_full_balance'   => false,
                'currency'              => 'INR',
                'status'                => 'initiated',
                'description'           => 'Demo Narration - optional',
//                'created_at'            => 1582036200,
            ]
        ]
    ],

    'testOndemandFees' => [
        'request'  => [
            'url'     => '/settlements/ondemand/fees',
            'method'  => 'get',
            'content' => [
                'amount' => 20030000
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 2,
                'items' => [
                    [
                        'name'            => 'settlement_ondemand',
                        'amount'          => 400600,
                        'percentage'      => null,
                        'pricing_rule_id' => '1GuENK6Hl2BWGg',
                        'pricing_rule' => [
                            'percent_rate' => 200,
                            'fixed_rate'   => 0,
                        ],
                    ],
                    [
                        'name'            => 'tax',
                        'amount'          => 72108,
                        'percentage'      =>  1800,
                        'pricing_rule_id' => null,
                    ],
                ],
            ]
        ]
    ],

    'testOndemandDay1FeesWithPricingInConfig' => [
        'request'  => [
            'url'     => '/settlements/ondemand/fees',
            'method'  => 'get',
            'content' => [
                'amount' => 20030000
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 2,
                'items' => [
                    [
                        'name'            => 'settlement_ondemand',
                        'amount'          => 46069,
                        'percentage'      => null,
                        'pricing_rule' => [
                            'percent_rate' => 23,
                            'fixed_rate'   => 0,
                        ],
                    ],
                    [
                        'name'            => 'tax',
                        'amount'          => 8292,
                        'percentage'      => 1800,
                        'pricing_rule_id' => null,
                    ],
                ],
            ]
        ]
    ],

    'testOndemandDay1FeesWithNoPricingInConfig' => [
        'request'  => [
            'url'     => '/settlements/ondemand/fees',
            'method'  => 'get',
            'content' => [
                'amount' => 20030000
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 2,
                'items' => [
                    [
                        'name'            => 'settlement_ondemand',
                        'amount'          => 60090,
                        'percentage'      => null,
                        'pricing_rule' => [
                            'percent_rate' => 30,
                            'fixed_rate'   => 0,
                        ],
                    ],
                    [
                        'name'            => 'tax',
                        'amount'          => 10816,
                        'percentage'      => 1800,
                        'pricing_rule_id' => null,
                    ],
                ],
            ]
        ]
    ],

    'testOndemandFeesWithNoPricing' => [
        'request'  => [
            'url'     => '/settlements/ondemand/fees',
            'method'  => 'get',
            'content' => [
                'amount' => 20030000
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 2,
                'items' => [
                    [
                        'name'            => 'settlement_ondemand',
                        'amount'          => 50075,
                        'percentage'      => null,
                        'pricing_rule' => [
                            'percent_rate' => 25,
                            'fixed_rate'   => 0,
                        ],
                    ],
                    [
                        'name'            => 'tax',
                        'amount'          => 9014,
                        'percentage'      => 1800,
                        'pricing_rule_id' => null,
                    ],
                ],
            ]
        ]
    ],

    'testOndemandFeesForFixedRate' => [
        'request'  => [
            'url'     => '/settlements/ondemand/fees',
            'method'  => 'get',
            'content' => [
                'amount' => 50030000
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 2,
                'items' => [
                    [
                        'name'            => 'settlement_ondemand',
                        'amount'          => 1000,
                        'percentage'      => null,
                        'pricing_rule_id' => '1GuENK6Hl2BWGg',
                        'pricing_rule' => [
                            'percent_rate' => 0,
                            'fixed_rate'   => 500,
                        ],
                    ],
                    [
                        'name'            => 'tax',
                        'amount'          => 180,
                        'percentage'      =>  1800,
                        'pricing_rule_id' => null,
                    ],
                ],
            ]
        ]
    ],

    'testAddOndemandPricingIfAbsent' => [
        'request'  => [
            'url'     => '/settlements/ondemand/add_ondemand_pricing',
            'method'  => 'post',
        ],
        'response' => [
            'content' => [
                'response' => 'AddOndemandPricingIfAbsent job dispatched'
            ]
        ]
    ],

    'testAdjustmentAditionToOndemandXMerchant' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'amount'    => 20030000,
                'description' => 'Demo Narration - optional',
                'notes'     => [
                                '1' => 'note1',
                                '2' => 'note2'
                            ],
            ],
        ],
        'response' => [
            'content' => [
//                'id'                   => 'sod_F2hdIcWTkePDcC',
                'entity'                => 'settlement.ondemand',
                'amount_requested'      => 20030000,
                'fees'                  => 472708,
                'tax'                   => 72108,
                'amount_pending'        => 19557292,
                'amount_settled'        => 0,
                'amount_reversed'       => 0,
                'currency'              => 'INR',
                'status'                => 'initiated',
                'description'             => 'Demo Narration - optional',
                'notes'                 => [
                                            '1' => 'note1',
                                            '2' => 'note2'
                                        ],
//                'created_at'         => 1582000200,
            ]
        ]
    ],

    'testOndemandCreationForMerchantWithXSettlementAccount' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'content' => [
                'amount'    => 20030000,
                'description' => 'Demo Narration - optional',
                'notes'     => [
                    'key1' => 'note3',
                    'key2' => 'note5'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'                => 'settlement.ondemand',
                'amount_requested'      => 20030000,
                'fees'                  => 472708,
                'tax'                   => 72108,
                'amount_pending'        => 0,
                'amount_settled'        => 19557292,
                'amount_reversed'       => 0,
                'currency'              => 'INR',
                'status'                => 'processed',
                'description'             => 'Demo Narration - optional',
                'notes'                 => [
                    'key1' => 'note3',
                    'key2' => 'note5'
                ],
            ]
        ]
    ],

    'testOndemandCreationForMerchantWithXSettlementAccountNonBankingHoursGreaterThanIMPSLimit' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'content' => [
                'amount'    => 60000000,
                'description' => 'Demo Narration - optional',
                'notes'     => [
                    'key1' => 'note3',
                    'key2' => 'note5'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'                => 'settlement.ondemand',
                'amount_requested'      => 60000000,
                'fees'                  => 1416000,
                'tax'                   => 216000,
                'amount_pending'        => 0,
                'amount_settled'        => 58584000,
                'amount_reversed'       => 0,
                'currency'              => 'INR',
                'status'                => 'processed',
                'description'             => 'Demo Narration - optional',
                'notes'                 => [
                    'key1' => 'note3',
                    'key2' => 'note5'
                ],
            ]
        ]
    ],

    'testOndemandCreationForMerchantWithXSettlementAccountNonBankingHoursLessThanIMPSLimit' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'content' => [
                'amount'    => 6000,
                'description' => 'Demo Narration - optional',
                'notes'     => [
                    'key1' => 'note3',
                    'key2' => 'note5'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'                => 'settlement.ondemand',
                'amount_requested'      => 6000,
                'fees'                  => 142,
                'tax'                   => 22,
                'amount_pending'        => 0,
                'amount_settled'        => 5858,
                'amount_reversed'       => 0,
                'currency'              => 'INR',
                'status'                => 'processed',
                'description'             => 'Demo Narration - optional',
                'notes'                 => [
                    'key1' => 'note3',
                    'key2' => 'note5'
                ],
            ]
        ]
    ],

    'testOndemandFeatureValidationSuccess' => [
        'request'  => [
            'url'     => '/settlements/ondemand/feature/validate',
            'method'  => 'get',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                ],
            ],
        'response' => [
            'content' => [
                'settlable_amount'        => 5000,
                'attempts_left'           => 2,
                'settlements_count_limit' => 2,
                'max_amount_limit'        => 7500
            ]
        ]
    ],

    'testOndemandFeatureValidationNoAttemptLeftFailure' => [
        'request'  => [
            'url'     => '/settlements/ondemand/feature/validate',
            'method'  => 'get',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'settlable_amount'        => 5000,
                'attempts_left'           => 0,
                'settlements_count_limit' => 2,
                'max_amount_limit'        => 7500
            ]
        ]
    ],

    'testOndemandFeatureValidationDailyAmountExceededFailure' => [
        'request'  => [
            'url'     => '/settlements/ondemand/feature/validate',
            'method'  => 'get',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'settlable_amount'         => 0,
                'attempts_left'            => 0,
                'settlements_count_limit'  => 3,
                'max_amount_limit'         => 7500
            ]
        ]
    ],

    'testEnableEsOnDemandFullAccessFromBatchRoute' => [
        'request'  => [
            'url'     => '/settlements/ondemand/feature',
            'method'  => 'post',
            'content' => [
               [
                    'merchant_id'                   => '10000000000000',
                    'percentage_of_balance_limit'   => 50,
                    'settlements_count_limit'       => 2,
                    'full_access'                   => 'yes',
                    'pricing_percent'               => 50,
                    'max_amount_limit'              => 2000000,
                    'idempotency_key'               => 'batch_10000000000000'
               ],
                [
                    'merchant_id'                   => '10000000000001',
                    'percentage_of_balance_limit'   => 50,
                    'settlements_count_limit'       => 2,
                    'full_access'                   => 'yes',
                    'pricing_percent'               => 50,
                    'max_amount_limit'              => 2000000,
                    'idempotency_key'               => 'batch_10000000000001'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 2,
                'items'     => [
                    [
                        'success'           => true,
                        'idempotency_key'   => 'batch_10000000000000'
                    ],
                    [
                        'success'           => true,
                        'idempotency_key'   => 'batch_10000000000001'
                    ],
                ],
            ],
        ]
    ],

    'testEnableEsOnDemandFullAccessWithEsAutomaticRestrictedEnabledFromBatchRoute' => [
        'request'  => [
            'url'     => '/settlements/ondemand/feature',
            'method'  => 'post',
            'content' => [
                [
                    'merchant_id'                   => '10000000000000',
                    'percentage_of_balance_limit'   => 50,
                    'settlements_count_limit'       => 2,
                    'full_access'                   => 'yes',
                    'pricing_percent'               => 50,
                    'es_pricing_percent'            => 12,
                    'max_amount_limit'              => 2000000,
                    'idempotency_key'               => 'batch_10000000000000'
                ],
                [
                    'merchant_id'                   => '10000000000001',
                    'percentage_of_balance_limit'   => 50,
                    'settlements_count_limit'       => 2,
                    'full_access'                   => 'yes',
                    'pricing_percent'               => 50,
                    'es_pricing_percent'            => 20,
                    'max_amount_limit'              => 2000000,
                    'idempotency_key'               => 'batch_10000000000001'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 2,
                'items'     => [
                    [
                        'success'           => true,
                        'idempotency_key'   => 'batch_10000000000000'
                    ],
                    [
                        'success'           => true,
                        'idempotency_key'   => 'batch_10000000000001'
                    ],
                ],
            ],
        ]
    ],

    'testDisableOnDemandFullAccessWithEsAutomaticEnabledFromBatchRoute' => [
        'request'  => [
            'url'     => '/settlements/ondemand/feature',
            'method'  => 'post',
            'content' => [
                [
                    'merchant_id'                   => '10000000000000',
                    'percentage_of_balance_limit'   => 50,
                    'settlements_count_limit'       => 2,
                    'full_access'                   => 'no',
                    'pricing_percent'               => 50,
                    'max_amount_limit'              => 2000000,
                    'idempotency_key'               => 'batch_10000000000000'
                ],
                [
                    'merchant_id'                   => '10000000000001',
                    'percentage_of_balance_limit'   => 50,
                    'settlements_count_limit'       => 2,
                    'full_access'                   => 'no',
                    'pricing_percent'               => 50,
                    'max_amount_limit'              => 2000000,
                    'idempotency_key'               => 'batch_10000000000001'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 2,
                'items'     => [
                    [
                        'success'           => true,
                        'idempotency_key'   => 'batch_10000000000000'
                    ],
                    [
                        'success'           => true,
                        'idempotency_key'   => 'batch_10000000000001'
                    ],
                ],
            ],
        ]
    ],

    'testEnableEsOnDemandRestrictedAccessFromBatchRoute' => [
        'request'  => [
            'url'     => '/settlements/ondemand/feature',
            'method'  => 'post',
            'content' => [
                [
                    'merchant_id'                   => '10000000000000',
                    'percentage_of_balance_limit'   => 50,
                    'settlements_count_limit'       => 2,
                    'full_access'                   => 'no',
                    'pricing_percent'               => 50,
                    'max_amount_limit'              => 2000000,
                    'idempotency_key'               => 'batch_10000000000000'
                ],
                [
                    'merchant_id'                   => '10000000000001',
                    'percentage_of_balance_limit'   => 50,
                    'settlements_count_limit'       => 2,
                    'full_access'                   => 'no',
                    'pricing_percent'               => 50,
                    'max_amount_limit'              => 2000000,
                    'idempotency_key'               => 'batch_10000000000001'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 2,
                'items'     => [
                    [
                        'success'           => true,
                        'idempotency_key'   => 'batch_10000000000000'
                    ],
                    [
                        'success'           => true,
                        'idempotency_key'   => 'batch_10000000000001'
                    ],
                ],
            ],
        ]
    ],

    'testUpdateFeatureConfigFromBatchRoute' => [
        'request'  => [
            'url'     => '/settlements/ondemand/feature',
            'method'  => 'post',
            'content' => [
                [
                    'merchant_id'                   => '10000000000000',
                    'percentage_of_balance_limit'   => 50,
                    'settlements_count_limit'       => 2,
                    'full_access'                   => 'yes',
                    'pricing_percent'               => 50,
                    'max_amount_limit'              => 2000000,
                    'idempotency_key'               => 'batch_10000000000000'
                ],
                [
                    'merchant_id'                   => '100DemoAccount',
                    'percentage_of_balance_limit'   => 50,
                    'settlements_count_limit'       => 2,
                    'full_access'                   => 'yes',
                    'pricing_percent'               => 50,
                    'max_amount_limit'              => 2000000,
                    'idempotency_key'               => 'batch_100DemoAccount'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 2,
                'items'     => [
                    [
                        'success'           => true,
                        'idempotency_key'   => 'batch_10000000000000'
                    ],
                    [
                        'success'           => true,
                        'idempotency_key'   => 'batch_100DemoAccount'
                    ],
                ],
            ],
        ]
    ],

    'testEnableEsOnDemandRestrictedAccessFromBatchRouteFailure' => [
        'request'  => [
            'url'     => '/settlements/ondemand/feature',
            'method'  => 'post',
            'content' => [
                [
                    'merchant_id'                   => '10000000000000',
                    'percentage_of_balance_limit'   => 50,
                    'settlements_count_limit'       => 2,
                    'full_access'                   => 'no',
                    'pricing_percent'               => 50,
                    'max_amount_limit'              => 2000000,
                    'idempotency_key'               => 'batch_10000000000000'
                ],
                [
                    'merchant_id'                   => '10000000000001',
                    'percentage_of_balance_limit'   => 50,
                    'settlements_count_limit'       => 2,
                    'full_access'                   => 'no',
                    'pricing_percent'               => 50,
                    'max_amount_limit'              => 2000000,
                    'idempotency_key'               => 'batch_10000000000001'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 2,
                'items'     => [
                    [
                        'success'           => true,
                        'idempotency_key'   => 'batch_10000000000000'
                    ],
                    [
                        'success'           => false,
                        'idempotency_key'   => 'batch_10000000000001',
                        'error'             => [
                            'description' => 'Failed to create pricing rule',
                            'code'        => 'SERVER_ERROR_PRICING_RULE_CREATION_FAILURE'
                        ]
                    ],
                ],
            ],
        ]
    ],

    'testOndemandCreationWithLimitExceededError' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'amount'    => 6000,
                'description' => 'Demo Narration - optional',
                'notes'     => [
                    'key1' => 'note3',
                    'key2' => 'note5'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'No more attempts left for today',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ONDEMAND_SETTLEMENT_LIMIT_EXCEEDED,
        ],
    ],

    'testOndemandCreationWithAmountExceededError' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'amount'    => 10000,
                'description' => 'Demo Narration - optional',
                'notes'     => [
                    'key1' => 'note3',
                    'key2' => 'note5'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Maximum amount that can be settled(in paisa) is 200',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ONDEMAND_SETTLEMENT_AMOUNT_MAX_LIMIT_EXCEEDED,
        ],
    ],

    'testOndemandCreationWithNoError' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'content' => [
                'amount'    => 2500,
                'description' => 'Demo Narration - optional',
                'notes'     => [
                    'key1' => 'note3',
                    'key2' => 'note5'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'                => 'settlement.ondemand',
                'amount_requested'      => 2500,
                'fees'                  => 60,
                'tax'                   => 10,
                'amount_pending'        => 2440,
                'amount_settled'        => 0,
                'amount_reversed'       => 0,
                'currency'              => 'INR',
                'status'                => 'initiated',
            ]
        ]
    ],

    'testEnableEsOnDemandRestrictedAccessForCrossOrgMerchantFromBatchRoute' => [
        'request'  => [
            'url'     => '/settlements/ondemand/feature',
            'method'  => 'post',
            'content' => [
                [
                    'merchant_id'                   => '10000000000000',
                    'percentage_of_balance_limit'   => 50,
                    'settlements_count_limit'       => 2,
                    'full_access'                   => 'no',
                    'pricing_percent'               => 50,
                    'max_amount_limit'              => 2000000,
                    'idempotency_key'               => 'batch_10000000000000'
                ],
                [
                    'merchant_id'                   => '10000000000001',
                    'percentage_of_balance_limit'   => 50,
                    'settlements_count_limit'       => 2,
                    'full_access'                   => 'no',
                    'pricing_percent'               => 50,
                    'max_amount_limit'              => 2000000,
                    'idempotency_key'               => 'batch_10000000000001'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 2,
                'items'     => [
                    [
                        'success'           => true,
                        'idempotency_key'   => 'batch_10000000000000'
                    ],
                    [
                        'success'           => true,
                        'idempotency_key'   => 'batch_10000000000001'
                    ],
                ],
            ],
        ]
    ],

    'testUpdateFeatureConfigForCrossOrgMerchantFromBatchRoute' => [
        'request'  => [
            'url'     => '/settlements/ondemand/feature',
            'method'  => 'post',
            'content' => [
                [
                    'merchant_id'                   => '10000000000000',
                    'percentage_of_balance_limit'   => 50,
                    'settlements_count_limit'       => 2,
                    'full_access'                   => 'yes',
                    'pricing_percent'               => 50,
                    'max_amount_limit'              => 2000000,
                    'idempotency_key'               => 'batch_10000000000000'
                ],
                [
                    'merchant_id'                   => '100DemoAccount',
                    'percentage_of_balance_limit'   => 50,
                    'settlements_count_limit'       => 2,
                    'full_access'                   => 'yes',
                    'pricing_percent'               => 50,
                    'max_amount_limit'              => 2000000,
                    'idempotency_key'               => 'batch_100DemoAccount'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 2,
                'items'     => [
                    [
                        'success'           => true,
                        'idempotency_key'   => 'batch_10000000000000'
                    ],
                    [
                        'success'           => true,
                        'idempotency_key'   => 'batch_100DemoAccount'
                    ],
                ],
            ],
        ]
    ],

    'testOndemandTransferMarkAsProcessed' => [
        'request'  => [
            'url'     => '/settlements/ondemand/transfer/12345678910111/processed',
            'method'  => 'post',
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testOndemandTransferTrigger' =>[
        'request'  => [
            'url'     => '/settlements/ondemand/transfer/trigger',
            'method'  => 'post',
            'content' => [
                    '12345678910111',
                    '12345678910112',
                ]
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 2,
                'items'     => [
                    [
                        'settlement_ondemand_transfer_id' => '12345678910111',
                        'success'                         => true
                    ],
                    [
                        'settlement_ondemand_transfer_id' => '12345678910112',
                        'success'                         => true
                    ],
                ],
            ],
        ],
    ],

    'testUpdateOndemandTransferPayoutId' =>[
        'request'  => [
            'url'     => '/settlements/ondemand/transfer/payout',
            'method'  => 'post',
            'content' => [
                            [
                                'settlement_ondemand_attempt_id'  => 'Hjw8I44gThg3z7',
                                'payout_id'                       => 'pout_Gjswrr4zGv1jpY',
                            ],
                            [
                                'settlement_ondemand_attempt_id' => 'Hjw8I3sWSwlhlD',
                                'payout_id'                      => 'pout_GjtCcoBj338MzV',
                            ]
            ]
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testEnqueueJob' => [
        'request' => [
            'url' => '/settlements/ondemand/enqueue/12345678910234',
            'method' => 'post',
            'content' => [

            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testOndemandFeatureWithoutRequiredPermission' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/admin/batches',
            'content' => [
                'type' => 'settlement_ondemand_feature_config',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'Required permission not found',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_REQUIRED_PERMISSION_NOT_FOUND
        ],
    ],

    'testOndemandFeatureWithPermission' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/admin/batches',
            'content' => [
                'type' => 'settlement_ondemand_feature_config',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'The file field is required when file id is not present.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testOndemandPartialEsScheduledTrigger' =>[
        'request'  => [
            'url'     => '/settlements/ondemand/scheduled/process',
            'method'  => 'post',
            'content' => []
        ],
        'response' => [
            'content' => [
                'response' => 'PartialScheduledSettlementJob job dispatched'
            ],
        ],
    ],

    'testOndemandPartialScheduledSettlement' =>[
        'request'  => [
            'url'     => '/settlements/ondemand/scheduled/process',
            'method'  => 'post',
            'content' => []
        ],
        'response' => [
            'content' => [
                'response' => 'PartialScheduledSettlementJob job dispatched'
            ],
        ],
    ],

    'testOndemandPartialScheduledSettlementWithMaxAmountLimitCrossed' =>[
        'request'  => [
            'url'     => '/settlements/ondemand/scheduled/process',
            'method'  => 'post',
            'content' => []
        ],
        'response' => [
            'content' => [
                'response' => 'PartialScheduledSettlementJob job dispatched'
            ],
        ],
    ],

    'testOndemandPartialScheduledSettlementErrorWithBalanceBelowThreshold' =>[
        'request'  => [
            'url'     => '/settlements/ondemand/scheduled/process',
            'method'  => 'post',
            'content' => []
        ],
        'response' => [
            'content' => [
                'response' => 'PartialScheduledSettlementJob job dispatched'
            ],
        ],
    ],

    'testOndemandPartialScheduledSettlementWithBalanceLessThanSettleableBalance' =>[
        'request'  => [
            'url'     => '/settlements/ondemand/scheduled/process',
            'method'  => 'post',
            'content' => []
        ],
        'response' => [
            'content' => [
                'response' => 'PartialScheduledSettlementJob job dispatched'
            ],
        ],
    ],

    'testEarlySettlementFeaturePeriodCreateFullAccess' => [
        'request'  => [
            'url'     => '/es/feature/period',
            'method'  => 'post',
            'content' => [
                [
                    'merchant_id'                   => '10000000000000',
                    'full_access'                   => 'yes',
                    'amount_limit'                  => 2000000,
                    'idempotency_key'               => 'batch_10000000000000',
                    'disable_date'                  => '3/2/2022',
                    'es_pricing'                    => 17
                ],
                [
                    'merchant_id'                   => '10000000000001',
                    'full_access'                   => 'yes',
                    'amount_limit'                  => 2000000,
                    'idempotency_key'               => 'batch_100DemoAccount',
                    'disable_date'                  => '4/2/2022',
                    'es_pricing'                    => 18
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 2,
                'items'     => [
                    [
                        'success'           => true,
                        'idempotency_key'   => 'batch_10000000000000'
                    ],
                    [
                        'success'           => true,
                        'idempotency_key'   => 'batch_100DemoAccount'
                    ],
                ],
            ],
        ],
    ],

    'testEarlySettlementFeaturePeriodCreateRestrictedAccess' => [
        'request'  => [
            'url'     => '/es/feature/period',
            'method'  => 'post',
            'content' => [
                [
                    'merchant_id'                   => '10000000000000',
                    'full_access'                   => 'no',
                    'amount_limit'                  => 2000000,
                    'idempotency_key'               => 'batch_10000000000000',
                    'disable_date'                  => '3/2/2022',
                    'es_pricing'                    => 15
                ],
                [
                    'merchant_id'                   => '10000000000001',
                    'full_access'                   => 'no',
                    'amount_limit'                  => 5000000,
                    'idempotency_key'               => 'batch_10000000000001',
                    'disable_date'                  => '4/2/2022',
                    'es_pricing'                    => 15
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 2,
                'items'     => [
                    [
                        'success'           => true,
                        'idempotency_key'   => 'batch_10000000000000'
                    ],
                    [
                        'success'           => true,
                        'idempotency_key'   => 'batch_10000000000001'
                    ]
                ],
            ],
        ],
    ],

    'testEarlySettlementFeaturePeriodFullAccessUpdate' => [
        'request'  => [
            'url'     => '/es/feature/period',
            'method'  => 'post',
            'content' => [
                [
                    'merchant_id'                   => '10000000000000',
                    'full_access'                   => 'yes',
                    'amount_limit'                  => 2000000,
                    'idempotency_key'               => 'batch_10000000000000',
                    'disable_date'                  => '3/2/2022',
                    'es_pricing'                    => 15
                ],
                [
                    'merchant_id'                   => '10000000000001',
                    'full_access'                   => 'yes',
                    'amount_limit'                  => 5000000,
                    'idempotency_key'               => 'batch_10000000000001',
                    'disable_date'                  => '4/2/2022',
                    'es_pricing'                    => 15
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 2,
                'items'     => [
                    [
                        'success'           => true,
                        'idempotency_key'   => 'batch_10000000000000'
                    ],
                    [
                        'success'           => true,
                        'idempotency_key'   => 'batch_10000000000001'
                    ]
                ],
            ],
        ],
    ],

    'testEarlySettlementFeaturePeriodRestrictedAccessUpdate' => [
        'request'  => [
            'url'     => '/es/feature/period',
            'method'  => 'post',
            'content' => [
                [
                    'merchant_id'                   => '10000000000000',
                    'full_access'                   => 'no',
                    'amount_limit'                  => 2000000,
                    'idempotency_key'               => 'batch_10000000000000',
                    'disable_date'                  => '3/2/2022',
                    'es_pricing'                    => 15
                ],
                [
                    'merchant_id'                   => '10000000000001',
                    'full_access'                   => 'no',
                    'amount_limit'                  => 5000000,
                    'idempotency_key'               => 'batch_10000000000001',
                    'disable_date'                  => '4/2/2022',
                    'es_pricing'                    => 15
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 2,
                'items'     => [
                    [
                        'success'           => true,
                        'idempotency_key'   => 'batch_10000000000000'
                    ],
                    [
                        'success'           => true,
                        'idempotency_key'   => 'batch_10000000000001'
                    ]
                ],
            ],
        ],
    ],

    'testEarlySettlementFeaturePeriodDisableFullES' =>[
        'request'  => [
            'url'     => '/es/feature/disable',
            'method'  => 'post',
            'content' => []
        ],
        'response' => [
            'content' => [
                'response' => 'DisableES job dispatched'
            ],
        ],
    ],

    'testEarlySettlementFeaturePeriodDisableRestrictedES' =>[
        'request'  => [
            'url'     => '/es/feature/disable',
            'method'  => 'post',
            'content' => []
        ],
        'response' => [
            'content' => [
                'response' => 'DisableES job dispatched'
            ],
        ],
    ],

    'testEnableRestrictedOndemandViaCron' =>[
        'request'  => [
            'url'     => '/settlements/ondemand/restricted',
            'method'  => 'post',
            'content' => []
        ],
        'response' => [
            'content' => [
                'response' => 'AddOndemandRestrictedFeature job dispatched'
            ],
        ],
    ],

    'testEnableFullOndemandViaCron' =>[
        'request'  => [
            'url'     => '/settlements/ondemand/full',
            'method'  => 'post',
            'content' => []
        ],
        'response' => [
            'content' => [
                'response' => 'AddFullES job dispatched'
            ],
        ],
    ],

    'testEnableFullOndemandAndESViaCron' =>[
        'request'  => [
            'url'     => '/settlements/ondemand/full',
            'method'  => 'post',
            'content' => []
        ],
        'response' => [
            'content' => [
                'response' => 'AddFullES job dispatched'
            ],
        ],
    ],

    'testOndemandBlocked' =>[
        'request'  => [
            'url'     => '/settlements/ondemand/merchant/config',
            'method'  => 'get',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => []
        ],
        'response' => [
            'content' => [
                'blocked' => true
            ],
        ],
    ],

    'testOndemandNotBlocked' =>[
        'request'  => [
            'url'     => '/settlements/ondemand/merchant/config',
            'method'  => 'get',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => []
        ],
        'response' => [
            'content' => [
                'blocked' => false
            ],
        ],
    ],

    'testCreateOndemandBlockedError' =>[
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'amount'              => 20000,
                'settle_full_balance' => 0,
                'description'         => 'Demo Narration - optional',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Ondemand settlement has been blocked for a while',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ONDEMAND_SETTLEMENT_BLOCKED,
        ],
    ],

    'testCreateOndemandSettlementForLinkedAccountSuccess' => [
        'request'  => [
            'url'     => '/settlements/ondemand/linked_account_settlements',
            'method'  => 'post',
            'content' => [
                  'merchant_id'                    => '10000000000000',
                  'mode'                           => 'test',
                  'amount'                         => 1000000,
                  'settlement_ondemand_trigger_id' => 'qaghswtyuiwsgh'
            ]
        ],
        'response' => [
            'content' => [
                'entity'              => 'settlement.ondemand',
                'amount_requested'    => 1000000,
                'amount_settled'      => 0,
                'amount_pending'      => 1000000,
                'amount_reversed'     => 0,
                'fees'                => 0,
                'tax'                 => 0,
                'currency'            => 'INR',
                'settle_full_balance' => false,
                'status'              => 'initiated',
                'description'         => null,
                'notes'               => [],
                'scheduled'           => false
            ],
        ],
    ],


    'testLinkedOndemandSettlementWithCapitalIntegrationForLedgerSuccess' => [
        'request'  => [
            'url'     => '/settlements/ondemand/linked_account_settlements',
            'method'  => 'post',
            'content' => [
                'merchant_id'                    => '10000000000000',
                'mode'                           => 'test',
                'amount'                         => 1000000,
                'settlement_ondemand_trigger_id' => 'qaghswtyuiwsgh'
            ]
        ],
        'response' => [
            'content' => [
                'entity'              => 'settlement.ondemand',
                'amount_requested'    => 1000000,
                'amount_settled'      => 0,
                'amount_pending'      => 1000000,
                'amount_reversed'     => 0,
                'fees'                => 0,
                'tax'                 => 0,
                'currency'            => 'INR',
                'settle_full_balance' => false,
                'status'              => 'initiated',
                'description'         => null,
                'notes'               => [],
                'scheduled'           => false
            ],
        ],
    ],

    'testLinkedAccountSettlementWithCapitalIntegrationForLedgerWithException' => [
        'request'  => [
            'url'     => '/settlements/ondemand/linked_account_settlements',
            'method'  => 'post',
            'content' => [
                'merchant_id'                    => '10000000000000',
                'mode'                           => 'test',
                'amount'                         => 1000000,
                'settlement_ondemand_trigger_id' => 'qaghswtyuiwsgh'
            ]
        ],
        'response' => [
            'content' => [
                'entity'              => 'settlement.ondemand',
                'amount_requested'    => 1000000,
                'amount_settled'      => 0,
                'amount_pending'      => 1000000,
                'amount_reversed'     => 0,
                'fees'                => 0,
                'tax'                 => 0,
                'currency'            => 'INR',
                'settle_full_balance' => false,
                'status'              => 'initiated',
                'description'         => null,
                'notes'               => [],
                'scheduled'           => false
            ],
        ],
    ],

    'testCreatePrepaidOndemandSettlementForLinkedAccountSuccess' => [
        'request'  => [
            'url'     => '/settlements/ondemand/linked_account_settlements',
            'method'  => 'post',
            'content' => [
                'merchant_id'                    => '10000000000000',
                'mode'                           => 'test',
                'amount'                         => 1000000,
                'settlement_ondemand_trigger_id' => 'qaghswtyuiwsgh'
            ]
        ],
        'response' => [
            'content' => [
                'entity'              => 'settlement.ondemand',
                'amount_requested'    => 1000000,
                'amount_settled'      => 0,
                'amount_pending'      => 976400,
                'amount_reversed'     => 0,
                'fees'                => 23600,
                'tax'                 => 3600,
                'currency'            => 'INR',
                'settle_full_balance' => false,
                'status'              => 'initiated',
                'description'         => null,
                'notes'               => [],
                'scheduled'           => false
            ],
        ],
    ],

    'testCreateOndemandSettlementForLinkedAccountValidationError' => [
        'request'  => [
            'url'     => '/settlements/ondemand/linked_account_settlements',
            'method'  => 'post',
            'content' => [
                'merchant_id'                    => '10000000000000',
                'mode'                           => 'test',
                'amount'                         => 1000000,
                'settlement_ondemand_trigger_id' => 'qaghswtyuiwsgh'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'BAD_REQUEST_NON_ONDEMAND_ROUTE_MERCHANTS_NOT_ALLOWED',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateOndemandSettlementForLinkedAccountWithMockWebhook' => [
        'request'  => [
            'url'     => '/settlements/ondemand/linked_account_settlements',
            'method'  => 'post',
            'content' => [
                'merchant_id'                    => '10000000000000',
                'mode'                           => 'test',
                'amount'                         => 1000000,
                'settlement_ondemand_trigger_id' => 'qaghswtyuiwsgh'
            ]
        ],
        'response' => [
            'content' => [
                'entity'              => 'settlement.ondemand',
                'amount_requested'    => 1000000,
                'amount_settled'      => 0,
                'amount_pending'      => 1000000,
                'amount_reversed'     => 0,
                'fees'                => 0,
                'tax'                 => 0,
                'currency'            => 'INR',
                'settle_full_balance' => false,
                'status'              => 'initiated',
                'description'         => null,
                'notes'               => [],
                'scheduled'           => false
            ],
        ],
    ],

    'testReverseOndemandSettlement' => [
        'request'  => [
            'url'     => '/settlements/ondemand/reverse',
            'method'  => 'post',
            'content' => [
                'merchant_id'                    => '10000000000000',
                'settlement_ondemand_id'         => 'KQ8VzkjC27pS3v',
                'reversal_reason'                => 'job failure'
            ]
        ],
        'response' => [
            'content' => [
                'success' => true
            ],
        ],
    ],

];
