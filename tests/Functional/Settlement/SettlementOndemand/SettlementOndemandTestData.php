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
                'amount'    => 20030000,
                'narration' => 'Demo Narration - optional',
                'notes'     => [
                                'key1' => 'note3',
                                'key2' => 'note5'
                            ],
                'expand'    => true
            ],
        ],
        'response' => [
            'content' => [
//                'id'                   => 'sod_F2hdIcWTkePDcC',
                'merchant_id'           => '10000000000000',
                'amount'                => '20030000',
                'total_fees'            => 472708,
                'total_tax'             => 72108,
                'total_amount_pending'  => 19557292,
                'total_amount_settled'  => 0,
                'total_amount_reversed' => 0,
                'currency'              => 'INR',
                'status'                => 'initiated',
                'narration'             => 'Demo Narration - optional',
                'notes'                 => [
                    'key1' => 'note3',
                    'key2' => 'note5'
                ],
//                'created_at'         => 1582000200,
                'settlement_ondemand_payouts' => [
                    [
//                        'id'            => 'sodp_F2hdIfWGvMza0E',
                        'merchant_id'    => '10000000000000',
//                        'ondemand_id'   => 'F2hdIcWTkePDcC',
                        'fees'           => 472708,
                        'utr'            => null,
                        'tax'            => 72108,
                        'mode'           => 'NEFT',
                        'amount'         => '20030000',
                        'status'         => 'created',
                    ],
                ]
            ]
        ]
    ],

    'testFetchApi' => [
        'request'  => [
            'url'     => '/settlements/ondemand/?from=1582000200&expand=1',
            'method'  => 'get'
        ],
        'response' => [
            'content' => [
                    'entity' => 'collection',
                    'count'  => 2,
                    'items'  =>  [
                        [
//                           'id'                     => 'sod_FF8EosqZtqVKJm',
                            'merchant_id'           => '10000000000000',
                            'amount'                => 110000,
                            'total_amount_settled'  => 0,
                            'total_fees'            => 2596,
                            'total_tax'             => 396,
                            'total_amount_reversed' => 0,
                            'total_amount_pending'  => 107404,
                            'max_balance'           => 0,
                            'currency'              => 'INR',
                            'status'                => 'initiated',
                            'narration'             => 'Demo Narration - optional',
                            'notes'                 => [],
//                            'created_at'            =>  1582000200,
 //                           'updated_at'            =>  1582000200,
                            'settlement_ondemand_payouts' => [
                            'entity'                      => 'collection',
                                'count'                   => 1,
                                'items' =>  [
                                     [
//                                        'id'                   => 'sodp_FF8Eot5uqWw1zg',
                                        'merchant_id'            => '10000000000000',
//                                        'settlement_ondemand_id' => 'FF8EosqZtqVKJm',
                                        'mode'                   => 'NEFT',
//                                        'initiated_at'         => 1582000200,
                                        'processed_at'           => null,
                                        'reversed_at'            => null,
                                        'amount'                 => 110000,
                                        'fees'                   => 2596,
                                        'tax'                    => 396,
                                        'utr'                    => null,
                                        'status'                 => 'initiated',
                                    ]
                                ]
                            ]
                        ],
                        [
//                            'id'                          => 'sod_FF8Eo7MwLIxLbX',
                            'merchant_id'                 => '10000000000000',
                            'amount'                      => 20030000,
                            'total_amount_settled'        => 0,
                            'total_fees'                  => 472708,
                            'total_tax'                   => 72108,
                            'total_amount_reversed'       => 0,
                            'total_amount_pending'        => 19557292,
                            'max_balance'                 => 0,
                            'currency'                    => 'INR',
                            'status'                      => 'initiated',
                            'narration'                   => 'Demo Narration - optional',
                            'notes'                       => [],
 //                           'created_at'                  => 1582000200,
//                            'updated_at'                  => 1582000200,
                            'settlement_ondemand_payouts' => [
                                'entity'                  => 'collection',
                                'count'                   =>  1,
                                'items'                   =>[
                                     [
//                                        'id'                     => 'sodp_FF8EoBbS8gUQPz',
                                        'merchant_id'            => '10000000000000',
 //                                       'settlement_ondemand_id' => 'FF8Eo7MwLIxLbX',
                                        'mode'                   => 'NEFT',
                                        'initiated_at'           => 1582000200,
                                        'processed_at'           => null,
                                        'reversed_at'            => null,
                                        'amount'                 => 20030000,
                                        'fees'                   => 472708,
                                        'tax'                    => 72108,
                                        'utr'                    => null,
                                        'status'                 => 'initiated',
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],      


    'testNoMinLimitFornEsAutomaticMerchants' => [
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
                    'amount'               => '2000',
                    'total_fees'           => 48,
                    'total_tax'            => 8,
                    'total_amount_pending' => 1952,
                    'max_balance'          => 0,
                    'currency'             => 'INR',
                    'status'               => 'initiated',
                    'narration'            => null,
                    'notes'                => [],
//                    'created_at'           => 1582000200,
//                    'updated_at'           => 1582000200,
            ]
        ]
    ],

    'testMinLimitForNonEsAutomaticMerchants' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'content' => [
                'amount'    => 100000,
                'narration' => 'Demo Narration - optional',
                'notes'     => [
                                'key1' => 'note3',
                                'key2' => 'note5'
                            ],
                'expand'    => true
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Minimum settlement amount should Rs 2000. To remove the cap, please enable daily settlements',
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
                'amount' => 20030000,
                'max_balance' => 0,
                'narration' => 'Demo Narration - optional',
                'expand' => true
            ],
        ],
        'response' => [
            'content' => [
//                'id'                    => 'sod_F2hdIcWTkePDcC',
                'merchant_id'           => '10000000000000',
                'amount'                => '20030000',
                'total_fees'            => 472708,
                'total_tax'             => 72108,
                'total_amount_pending'  => 19557292,
                'total_amount_settled'  => 0,
                'total_amount_reversed' => 0,
                'max_balance'           => '0',
                'currency'              => 'INR',
                'status'                => 'initiated',
                'narration'             => 'Demo Narration - optional',
//                'created_at'          => 1582000200,
                'settlement_ondemand_payouts' => [
                    [
//                        'id'             => 'sodp_F2hdIfWGvMza0E',
                        'merchant_id'    => '10000000000000',
//                        'ondemand_id'    => 'F2hdIcWTkePDcC',
                        'utr'            => null,
                        'fees'           => 472708,
                        'tax'            => 72108,
                        'mode'           => 'NEFT',
                        'amount'         => '20030000',
                        'status'         => 'created',
                    ],
                ]
            ]
        ]
    ],

    'testBankingHourOndemandCreationWithReversal' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'content' => [
                'amount'      => 110000,
                'max_balance' => 0,
                'narration'   => 'Demo Narration - optional',
                'expand'      => true
            ],
        ],
        'response' => [
            'content' => [
//                'id'                    => 'sod_F2hdIcWTkePDcC',
                'merchant_id'           => '10000000000000',
                'amount'                => '110000',
                'total_fees'            => 2596,
                'total_tax'             => 396,
                'total_amount_pending'  => 107404,
                'total_amount_settled'  => 0,
                'total_amount_reversed' => 0,
                'max_balance'           => '0',
                'currency'              => 'INR',
                'status'                => 'initiated',
                'narration'             => 'Demo Narration - optional',
//                'created_at'           => 1582000200,
                'settlement_ondemand_payouts' => [
                    [
 //                       'id'            => 'sodp_F2hdIfWGvMza0E',
                        'merchant_id'   => '10000000000000',
//                        'ondemand_id'   => 'F2hdIcWTkePDcC',
                        'fees'          => 2596,
                        'utr'           => null,
                        'tax'           => 396,
                        'mode'          => 'NEFT',
                        'amount'        => '110000',
                        'status'        => 'created',
                    ],
                ]
             ]
        ]
    ],

    'testNonBankingHourOndemandCreation' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'content' => [
                'amount'      => 20030000,
                'max_balance' => 0,
                'narration'   => 'Demo Narration - optional',
                'expand'      => true
            ],
        ],
        'response' => [
            'content' => [
//                'id'                    => 'sod_F2iLNDZn8cODKc',
                'merchant_id'           => '10000000000000',
                'amount'                => '20030000',
                'total_fees'            => 472708,
                'total_tax'             => 72108,
                'total_amount_pending'  => 19557292,
                'total_amount_settled'  => 0,
                'total_amount_reversed' => 0,
                'max_balance'           => '0',
                'currency'              => 'INR',
                'status'                => 'initiated',
                'narration'             => 'Demo Narration - optional',
//                'created_at'            => 1582036200,
                'settlement_ondemand_payouts' => [
                    [
//                        'id'            => 'sodp_F2iLNGiioWkjZm',
                        'merchant_id'   => '10000000000000',
//                        'ondemand_id'   => 'F2iLNDZn8cODKc',
                        'fees'          => 472000,
                        'utr'           => null,
                        'tax'           => 72000,
                        'mode'          => 'IMPS',
                        'amount'        => 20000000,
                        'status'        => 'created',
                    ],
                    [
//                        'id'            => 'sodp_F2iLNHUZcSfTAG',
                        'merchant_id'   => '10000000000000',
//                        'ondemand_id'   => 'F2iLNDZn8cODKc',
                        'fees'          => 708,
                        'utr'           => null,
                        'tax'           => 108,
                        'mode'          => 'IMPS',
                        'amount'        => 30000,
                        'status'        => 'created',
                    ]
                ]
            ]
        ]
    ],

    'testNonBankingHourOndemandCreationWithMockWebhook' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'content' => [
                'amount' => 20030000,
                'max_balance' => 0,
                'narration' => 'Demo Narration - optional',
                'expand' => true
            ],
        ],
        'response' => [
            'content' => [
//                'id'                    => 'sod_F2iLNDZn8cODKc',
                'merchant_id'           => '10000000000000',
                'amount'                => '20030000',
                'total_fees'            => 472708,
                'total_tax'             => 72108,
                'total_amount_pending'  => 19557292,
                'total_amount_settled'  => 0,
                'total_amount_reversed' => 0,
                'max_balance'           => '0',
                'currency'              => 'INR',
                'status'                => 'initiated',
                'narration'             => 'Demo Narration - optional',
//                'created_at'            => 1582036200,
                'settlement_ondemand_payouts' => [
                    [
//                        'id'            => 'sodp_F2iLNGiioWkjZm',
                        'merchant_id'   => '10000000000000',
//                        'ondemand_id'   => 'F2iLNDZn8cODKc',
                        'fees'          => 472000,
                        'utr'           => null,
                        'tax'           => 72000,
                        'mode'          => 'IMPS',
                        'amount'        => 20000000,
                        'status'        => 'created',
                    ],
                    [
//                        'id'            => 'sodp_F2iLNHUZcSfTAG',
                        'merchant_id'   => '10000000000000',
//                        'ondemand_id'   => 'F2iLNDZn8cODKc',
                        'fees'          => 708,
                        'utr'           => null,
                        'tax'           => 108,
                        'mode'          => 'IMPS',
                        'amount'        => 30000,
                        'status'        => 'created',
                    ]
                ]
            ]
        ]
    ],

    'testNonBankingHourOndemandCreationWithPartialReversal' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'content' => [
                'amount' => 20110000,
                'max_balance' => 0,
                'narration' => 'Demo Narration - optional',
                'expand' => true
            ],
        ],
        'response' => [
            'content' => [
//                'id'                    => 'sod_F2iLNDZn8cODKc',
                'merchant_id'           => '10000000000000',
                'amount'                => '20110000',
                'total_fees'            => 474596,
                'total_tax'             => 72396,
                'total_amount_pending'  => 19635404,
                'total_amount_settled'  => 0,
                'total_amount_reversed' => 0,
                'max_balance'           => '0',
                'currency'              => 'INR',
                'status'                => 'initiated',
                'narration'             => 'Demo Narration - optional',
//                'created_at'            => 1582036200,
                'settlement_ondemand_payouts' => [
                    [
//                        'id'            => 'sodp_F2iLNGiioWkjZm',
                        'merchant_id'   => '10000000000000',
//                        'ondemand_id'   => 'F2iLNDZn8cODKc',
                        'fees'          => 472000,
                        'utr'           => null,
                        'tax'           => 72000,
                        'mode'          => 'IMPS',
                        'amount'        => 20000000,
                        'status'        => 'created',
                    ],
                    [
//                        'id'            => 'sodp_F2iLNHUZcSfTAG',
                        'merchant_id'   => '10000000000000',
//                        'ondemand_id'   => 'F2iLNDZn8cODKc',
                        'fees'          => 2596,
                        'tax'           => 396,
                        'utr'           => null,
                        'mode'          => 'IMPS',
                        'amount'        => 110000,
                        'status'        => 'created',
                    ]
                ]
            ]
        ]
    ],

    'testCreateOndemandForMaxBalance' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'content' => [
                'max_balance' => 1,
                'narration' => 'Demo Narration - optional',
                'expand' => true
            ],
        ],
        'response' => [
            'content' => [
//                'id'                  => 'sod_F2hdIcWTkePDcC',
                'merchant_id'          => '10000000000000',
                'amount'               => 20030000,
                'total_fees'           => 472708,
                'total_tax'            => 72108,
                'total_amount_pending' => 19557292,
                'total_amount_settled'  => 0,
                'total_amount_reversed' => 0,
                'currency'             => 'INR',
                'status'               => 'initiated',
                'narration'            => 'Demo Narration - optional',
//                'created_at'         => 1582000200,
                'settlement_ondemand_payouts' => [
                    [
//                        'id'            => 'sodp_F2hdIfWGvMza0E',
                        'merchant_id'    => '10000000000000',
//                        'ondemand_id'   => 'F2hdIcWTkePDcC',
                        'fees'           => 472708,
                        'utr'            => null,
                        'tax'            => 72108,
                        'mode'           => 'NEFT',
                        'amount'         => 20030000,
                        'status'         => 'created',
                    ],
                ]
            ]
        ]
    ],

    'testCreateOndemandOnLowBalance' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'content' => [
                'amount' => 2000,
                'max_balance' => 0,
                'narration' => 'Demo Narration - optional',
                'expand' => true
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
                'max_balance' => 0,
                'narration' => 'Demo Narration - optional',
                'expand' => true
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
            'content' => [
                'amount' => 20000,
                'max_balance' => 1,
                'narration' => 'Demo Narration - optional',
                'expand' => true
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
                'amount' => 20030000,
                'max_balance' => 0,
                'narration' => 'Demo Narration - optional',
                'expand' => true
            ],
        ],
        'response' => [
            'content' => [
//                'id'                    => 'sod_F2iLNDZn8cODKc',
                'merchant_id'           => '10000000000000',
                'amount'                => '20030000',
                'total_fees'            => 1180,
                'total_tax'             => 180,
                'total_amount_pending'  => 20028820,
                'total_amount_settled'  => 0,
                'total_amount_reversed' => 0,
                'max_balance'           => '0',
                'currency'              => 'INR',
                'status'                => 'initiated',
                'narration'             => 'Demo Narration - optional',
//                'created_at'            => 1582036200,
                'settlement_ondemand_payouts' => [
                    [
//                        'id'            => 'sodp_F2iLNGiioWkjZm',
                        'merchant_id'   => '10000000000000',
//                        'ondemand_id'   => 'F2iLNDZn8cODKc',
                        'fees'          => 590,
                        'tax'           => 90,
                        'mode'          => 'IMPS',
                        'utr'           => null,
                        'amount'        => 20000000,
                        'status'        => 'created',
                    ],
                    [
//                        'id'            => 'sodp_F2iLNHUZcSfTAG',
                        'merchant_id'   => '10000000000000',
//                        'ondemand_id'   => 'F2iLNDZn8cODKc',
                        'fees'          => 590,
                        'tax'           => 90,
                        'utr'           => null,
                        'mode'          => 'IMPS',
                        'amount'        => 30000,
                        'status'        => 'created',
                    ]
                ]
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

    'testOndemandFeesForFixedRate' => [
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

    'testAdjustmentAditionToOndemandXMerchant' => [
        'request'  => [
            'url'     => '/settlements/ondemand',
            'method'  => 'post',
            'content' => [
                'amount'    => 20030000,
                'narration' => 'Demo Narration - optional',
                'notes'     => [
                                '1' => 'note1',
                                '2' => 'note2'
                            ],
                'expand'    => true
            ],
        ],
        'response' => [
            'content' => [
//                'id'                   => 'sod_F2hdIcWTkePDcC',
                'merchant_id'           => '10000000000000',
                'amount'                => '20030000',
                'total_fees'            => 472708,
                'total_tax'             => 72108,
                'total_amount_pending'  => 19557292,
                'total_amount_settled'  => 0,
                'total_amount_reversed' => 0,
                'currency'              => 'INR',
                'status'                => 'initiated',
                'narration'             => 'Demo Narration - optional',
                'notes'                 => [
                                            '1' => 'note1',
                                            '2' => 'note2'
                                        ],
//                'created_at'         => 1582000200,
                'settlement_ondemand_payouts' => [
                    [
//                        'id'            => 'sodp_F2hdIfWGvMza0E',
                        'merchant_id'    => '10000000000000',
//                        'ondemand_id'   => 'F2hdIcWTkePDcC',
                        'fees'           => 472708,
                        'tax'            => 72108,
                        'mode'           => 'NEFT',
                        'amount'         => '20030000',
                        'status'         => 'created',
                    ],
                ]
            ]
        ]
    ],
];
